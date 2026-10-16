<template>
  <div class="container page-home">
    <h2
    v-text="'Доступные системы'"
    class="heading-1 page-home__title"/>

    <base-tabs
    :data="tabs"
    :value="activeTab"
    value-name="tab_id"
    label-name="client_name"
    @input="setTab"
    class="page-home__tabs"/>

    <transition enter-active-class="animated fast fadeIn">
      <component
          :is="activeTab"
          v-bind="attrs"
          @notices:update="updateNotices"
          class="page-home__content"/>
    </transition>

  </div>
</template>
<script>
import BaseTabs from './BaseTabs';
import ClientsList from './ClientsList';
import NoticesList from './NoticesList';
import { pick, cloneDeep, findIndex } from 'lodash';

export default {
  name: 'PageHome',
  components: { BaseTabs, ClientsList, NoticesList },
  props: {
    clientsData: { type: Array, required: true },
    isUserESIA: { type: Boolean, default: false },
    hasUserESIAFields: { type: Boolean, default: false },
    hasUserOrgFields: { type: Boolean, default: false }
  },

  data: () => ({
    clients: [],
    tabs: [
      { tab_id: 'ClientsList', client_name: 'ПЕРЕЧЕНЬ ДОСТУПНЫХ СИСТЕМ' },
    ],
    activeTab: 'ClientsList'
  }),

  computed: {
    attrs(){
      return this.$route.name === 'NoticesList'
          ? pick(this, ['clients'])
          : pick(this, [
            'clients',
            'isUserESIA',
            'hasUserESIAFields',
            'hasUserOrgFields'
          ]);
    }
  },

  methods: {
    init(){
      this.activeTab = this.$route.name === 'pageHome' ? 'ClientsList' : 'NoticesList';
      this.clients = cloneDeep(this.clientsData);
    },

    setTab(newTab){
      this.activeTab = newTab;
      this.$router.push({ name: newTab === 'ClientsList' ? 'pageHome' : 'pageNotices' })
    },

    updateNotices(params) {
      const index = findIndex(this.clients, item => item.id == params.id);
      if (index !== -1){
        this.clients[index].notice_cnt = params.cnt;
      }
    },

    changeRoute(){
      this.activeTab = this.$route.name === 'pageHome' ? 'ClientsList' : 'NoticesList';
      this.fetchClients();
    },

    async fetchClients(){
      const { dispatch, commit } = this.$store;
      commit('setLoader', true);
      const response = await dispatch('getClients');
      commit('setLoader', false);
      if (response && response.status){
        this.clients = response.data.clients;
      }
      else{
        this.$notify({type: 'error', text: 'Получить подключённые системы не удалось'});
      }
    }
  },

  created() {
    this.init();
  },

  watch: {
    '$route.name': { handler: 'changeRoute' }
  }
};
</script>
<style lang="scss">
@import '~@/sass/variables';

.page-home {
  padding-bottom: 40px;

  &__title {
    margin-bottom: 20px;
  }

  &__tabs {
    margin-bottom: 2px; //XD
  }

  &__content {
    .clients-list__list {
      margin-top: 40px;
    }

    .notices-list__inner {
      height: calc(100vh - 304px);
    }
  }
}
</style>
