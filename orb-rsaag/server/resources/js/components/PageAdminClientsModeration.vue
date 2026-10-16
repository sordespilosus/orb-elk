<template>
  <div class="page-admin-clients-moderation">

    <h2 class="heading-2 page-admin-clients-moderation__title">Системы на модерации</h2>
    <base-tabs
    :value="currentTab"
    @input="setTab"
    v-model="currentTab"
    :data="tabs"
    value-name="id"
    label-name="name"
    class="page-admin-clients-moderation__tabs"/>

    <admin-clients-moderation
    v-if="currentTab === 'clients' && newClients.length"
    :clients-data="newClients"
    :client-scope="clientScope"
    :esia-scopes="esiaScopes"
    @fetch-clients="init"
    />

    <admin-clients-changes
    v-if="currentTab === 'changes' && changesClients.length"
    :clients-data="changesClients"
    :client-scope="clientScope"
    :esia-scopes="esiaScopes"
    @fetch-clients="init"/>

    <div
    v-if="(currentTab === 'changes' && !changesClients.length) || (currentTab === 'clients' && !newClients.length)"
    class="heading-2 page-admin-clients-moderation__none-clients"
    v-text="'Нет систем на модерации'"/>

  </div>
</template>
<script>

import AdminActions from './AdminActions';
import AdminClientsModeration from './AdminClientsModeration';
import AdminClientsChanges from './AdminClientsChanges';
import BaseTabs from '@/js/components/BaseTabs';

export default {
  name: 'PageAdminClientsModeration',
  components: {
    AdminActions,
    AdminClientsModeration,
    AdminClientsChanges,
    BaseTabs,
  },

  data: () => ({
    newClients: [],
    changesClients: [],
    clientScope: {},
    esiaScopes: {},
    currentTab: 'clients',
    tabs: [
      {id: 'clients', name: 'Новые системы'},
      {id: 'changes', name: 'Изменения систем'},
    ]
  }),

  computed: {

  },

  methods: {
    async init() {
      const { commit } = this.$store;
      commit('setLoader', true);
      await this.fetchClients();
      commit('setLoader', false);
    },

    async fetchClients() {
      const { dispatch } = this.$store;
      const {status, data} = await dispatch('fetchClientsModeration', {tab: this.currentTab});

      if (!status){
        this.$notify({type: 'error', text: 'Получить данные не удалось'});
        return;
      }

      this.newClients = data.newClients || [];
      this.changesClients = data.changesClients || [];
      this.clientScope = data.scope;
      this.esiaScopes = data.esiaScopes;
    },

    setTab(tab){
      this.currentTab = tab;
      this.init();
    }

  },

  created() {
    this.init();
  }
};
</script>
<style lang="scss">
.page-admin-clients-moderation {
  &__title {
    margin-bottom: 20px;
  }

  &__logo {
    width: 60px;
    height: 60px;

    border-radius: 100%;

    background-size: contain;
    background-repeat: no-repeat;
    background-position: center;
  }

  &__placeholder {
    width: 60px;
    height: 60px;
    padding: 5px;
    color: #7b898d;
  }

  &__none-clients {
    text-align: center;
    margin: 15px 0;
  }
}
</style>
