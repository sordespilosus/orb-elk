<template>
  <div class="page-my-clients container">

    <h2
    v-text="'Мои системы'"
    class="heading-2 page-my-clients__title" />

    <div class="page-my-clients__list">
      <div
      v-for="client in clients"
      :key="client.id.value"
      class="page-my-clients__item">

        <div
        v-if="client.logo.value.preview"
        class="page-my-clients__image"
        :style="`background-image: url(${client.logo.value.preview});`"/>
        <icon v-else name="photo" class="page-my-clients__image-placeholder"/>

        <a
        v-text="client.name.value"
        :href="client.url_site.value"
        target="_blank"
        class="link page-my-clients__name"/>

        <div
        class="page-my-clients__description">
          <div
          class="page-my-clients__status"
          v-text="`${client.status.value} (${client.status_changed_at.value})`" />

          <div
          v-if="client.status.system_value === 'rejected'"
          @click="showReason(client)"
          class="page-my-clients__reason link"
          v-text="'Причина отклонения от ' + client.reasons_for_rejections.value[0].created_at" />

          <div
          v-if="client.last_changes.value"
          @click="showChanges(client, 'last_changes')"
          class="page-my-clients__changes link">
            Изменения на модерации ({{client.last_changes.value.changeTime}})
          </div>
          <div
          v-if="client.last_changes_rejected.value"
          @click="showChanges(client, 'last_changes_rejected')"
          class="page-my-clients__changes link">
            Изменения отклонены ({{client.last_changes_rejected.value.changeTime}})
          </div>
          <div
          v-if="client.last_changes_accepted.value"
          class="page-my-clients__changes">
            Изменения приняты ({{client.last_changes_accepted.value.changeTime}})
          </div>
        </div>

        <button
        v-text="client.status.system_value === 'sent' || client.last_changes.value ? 'На проверке' : 'Редактировать'"
        :disabled="client.status.system_value === 'sent' || client.last_changes.value"
        @click="showEditForm(client)"
        class="button button--invert page-my-clients__button"/>

        <!--<div class="page-my-clients__card-description">
          <div
          class="page-my-clients__card-name"
          v-text="client.name.value"/>
          <div class="page-my-clients__card-actions">
            <button @click="showEditForm(client)" class="button" >Редактировать</button>
            <button @click="deleteClient(client)" class="button button--negative" >Удалить</button>
          </div>
        </div>-->
      </div>

      <div
      @click="isCreateModalShown = true"
      class="page-my-clients__add-button button button--positive">
        <icon name="plus" class="page-my-clients__add-button-icon"/>
        <div
        v-text="'Создать'"
        class="page-my-clients__add-button-text"/>
      </div>
    </div>

    <admin-client-edit
    v-if="isEditModalShown"
    @client:update="editClient"
    @modal:close="isEditModalShown = false"
    :client="currentClient"
    :scope="clientScope"
    :esia-scope="esiaScopes"
    :server-errors="serverErrors"
    title="Редактирование системы"
    is-edit/>

    <admin-client-edit
    v-if="isCreateModalShown"
    @client:update="createClient"
    @modal:close="isCreateModalShown = false"
    :client="emptyClient"
    :scope="clientScope"
    :esia-scope="esiaScopes"
    :server-errors="serverErrors"
    :is-moder="false"
    title="Создание системы"/>

    <client-changes
    v-if="isChangesModalShown"
    @modal:close="isChangesModalShown = false"
    @changes:update="setCancelChanges"
    :client="currentClient"
    :scope="clientScope"
    :esia-scope="esiaScopes"
    :field-changes="fieldChanges"
    :server-errors="serverErrors"
    title="Изменения системы"
    />

    <modal
    v-if="isReasonModalShow"
    @modal:close="isReasonModalShow = false"
    max-width="480"
    class="page-my-clients__modal"
    :title="'Причина отклонения от ' + currentClient.reasons_for_rejections.value[0].created_at"
    :text="currentClient.reasons_for_rejections.value[0].reasons" />

  </div>
</template>
<script>
const handleClientData = client => {
  const emptyHandler = val => (val ? val : 'Не указано');
  const booleanHandler = val => (val ? 'Да' : 'Нет');
  const imageHandler = val => ({ preview: val, file: null });
  const scopeHandler = val => ({ label: scope[val], value });
  const statusHandler = val => ({
    sent: 'На проверке',
    rejected: 'Отклонена',
    approved: 'Подтверждена',
  }[val] || 'На проверке');

  const fields = [
    { key: 'name', label: 'Название системы' },
    { key: 'id', label: 'ID' },
    { key: 'created_at', label: 'Дата создания' },
    { key: 'updated_at', label: 'Дата последнего изменения' },
    { key: 'secret', label: 'Секретный ключ' },
    { key: 'url_site', label: 'Адрес системы' },
    { key: 'url_login', label: 'Адрес входа в систему' },
    { key: 'url_notifications', label: 'Адрес получения нотификаций' },
    {
      key: 'need_esia',
      label: 'Необходим профиль ЕСИА',
      handler: booleanHandler
    },
    {
      key: 'dont_remember',
      label: 'Не запоминать пользователя после входа',
      handler: booleanHandler
    },
    {
      key: 'only_esia',
      label: 'Авторизация только через ЕСИА',
      handler: booleanHandler
    },
    {
      key: 'need_organization',
      label: 'Нужна авторизация как юр. лица или ИП',
      handler: booleanHandler
    },
    { key: 'is_hidden', label: 'Система скрыта', handler: booleanHandler },
    { key: 'logo', label: 'Логотип', handler: imageHandler },
    { key: 'ord', label: 'Порядок' },
    { key: 'scope', label: 'Разрешения для системы' },
    { key: 'scope_required', label: 'Обязательное наличие данных для входа в систему' },
    { key: 'esia_level', label: 'Необходимый уровень достоверности ESIA' },
    { key: 'client_hits_esia_scope', label: 'Данные из ЕСИА' },
    {
      key: 'redirect',
      label: 'Адрес редиректа системы (несколько элементов через запятую)'
    },
    { key: 'description', label: 'Краткое описание системы' },
    { key: 'agreement', label: 'Пользовательское соглашение системы' },
    { key: 'update_agreement' },
    { key: 'status', label: 'Подтверждённая система', handler: statusHandler },
    { key: 'last_changes', label: 'Последние изменения' },
    { key: 'last_changes_accepted', label: 'Последние принятые изменения' },
    { key: 'last_changes_rejected', label: 'Последние отклонённые изменения' },
    { key: 'reasons_for_rejections', label: 'Причины отклонения' },
    { key: 'status_changed_at', label: 'Дата изменения статуса системы' },
  ];
  return fields.reduce((result, field) => {
    const key = field.key;

    if (key in client) {
      const system_value = client[key];
      const value = field.handler ? field.handler(system_value) : system_value;
      result[key] = { ...field, system_value, value };
    }
    return result;
  }, {}); //@LODASH!!!!!
};

import AdminClientEdit from './AdminClientEdit';
import BaseTable from './BaseTable';
import ClientChanges from "./ClientChanges";
import Modal from '@/js/components/Modal';
import { set, cloneDeep } from 'lodash';

export default {
  name: 'PageMyClients',
  components: {
    AdminClientEdit,
    BaseTable,
    ClientChanges,
    Modal
  },

  props:{
    clientsServer: { type: Array, required: true },
    clientScope: { type: Object, required: true },
    esiaScopes: { type: Object, required: true },
  },

  data: () => ({
    clients: [],
    currentClient: {},
    emptyClient: handleClientData({
      name: null,
      url_site: null,
      url_login: null,
      url_notifications: null,
      need_esia: false,
      only_esia: false,
      esia_level: null,
      dont_remember: false,
      need_organization: false,
      is_hidden: false,
      logo: null,
      ord: null,
      scope: {
        rsaag_id: true,
        email: true,
        esia_user_id: false,
        fb_user_id: false,
        organizations_user: false,
        esia_address: false,
        passport: false,
        personal_data: true,
        phone: true,
        snils: false,
        vk_user_id: false,
      },
      scope_required: {
        email: true,
        esia_user_id: false,
        fb_user_id: false,
        esia_address: false,
        passport: false,
        phone: true,
        snils: false,
        vk_user_id: false,
      },
      client_hits_esia_scope: {
        drivers_licence_doc: false,
        birth_cert_doc: false,
        temporary_residence_doc: false,
        vehicles: false,
        ext_org_info: false,
        kids: false,
      },
      description: null,
      redirect: [],
      agreement: null,
      status: null,
    }),

    isEditModalShown: false,
    isCreateModalShown: false,
    isChangesModalShown: false,
    fieldChanges: null,
    isReasonModalShow: false,
    serverErrors: {}
  }),

  computed: {

  },

  methods: {
    async init() {

      this.clients = cloneDeep(this.clientsServer).map(handleClientData);
      /*const { commit } = this.$store;
      commit('setLoader', true);
      await this.fetchClients();
      commit('setLoader', false);*/
    },

    async fetchClients() {
      const { dispatch } = this.$store;
      const clientData = await dispatch('fetchMyClients');

      this.clients = clientData.clients.map(handleClientData);
      this.clientScope = clientData.scope;
      this.esiaScopes = clientData.esiaScopes;

      this.currentClient = this.clients[0];
    },

    async editClient(params) {
      const { commit, dispatch } = this.$store;
      let isReset = this.currentClient.status.system_value === 'rejected';

      commit('setLoader', true);
      const {status, data, form, errors} = await dispatch(isReset ? 'resendMyClient' : 'editMyClient', params);

      if (status) {
        this.isEditModalShown = false;
        await this.fetchClients();
        this.$notify({
          type: 'success',
          text: isReset ? 'Система отправлена на проверку' : 'Система успешно отредактирована!'
        });
      } else {
        if (errors)
          this.serverErrors = errors;
        this.$notify({
          type: 'error',
          text: form || 'Отредактировать систему не удалось!'
        });
      }
      commit('setLoader', false);
    },

    async createClient(params) {
      const { commit, dispatch } = this.$store;

      commit('setLoader', true);
      const {status, data, form, errors} = await dispatch('editMyClient', params);

      if (status) {
        this.isCreateModalShown = false;
        await this.fetchClients();
        this.$notify({ type: 'success', text: 'Система успешно создана!' });
      } else {
        if (errors)
          this.serverErrors = errors;
        this.$notify({ type: 'error', text: form || 'Создать систему не удалось!' });
      }
      commit('setLoader', false);
    },

    showEditForm(client){
      this.currentClient = client;
      this.isEditModalShown = true;
    },

    getStatus(client){
      return {
        sent: 'На проверке',
        rejected: 'Отклонена',
        approved: 'Подтверждена',
      }[client.status.value] || 'На проверке';
    },

    showChanges(client, fieldChanges){
      this.currentClient = client;
      this.fieldChanges = fieldChanges;
      this.isChangesModalShown = true;
    },

    showReason(client){
      this.currentClient = client;
      this.isReasonModalShow = true;
    },

    async setCancelChanges(params){
      const { commit, dispatch } = this.$store;
      params.client_id = this.currentClient.id.value;

      commit('setLoader', true);
      const {status, errors} = await dispatch('cancelMyClient', params);

      if (status) {
        this.isChangesModalShown = false;
        this.$notify({ type: 'success', text: 'Изменения отменены' });
        await this.fetchClients();
      } else {
        if (errors)
          this.serverErrors = errors;
        this.$notify({ type: 'error', text: 'Отменить изменения не удалось' });
      }

      commit('setLoader', false);
    }
  },

  created() {
    this.init();
  }
};
</script>
<style lang="scss">
.page-my-clients {
  padding-bottom: 40px;
  &__title {
    margin-bottom: 20px;
  }

  &__list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    grid-auto-rows: 1fr;
    gap: 20px;
  }

  &__item {
    position: relative;

    padding: 20px;
    padding-bottom: 90px;

    background-color: #fff;
    border-radius: 10px;
  }

  &__image {
    width: 60px;
    height: 60px;

    border-radius: 100%;

    background-size: contain;
    background-repeat: no-repeat;
    background-position: center;
  }

  &__image-placeholder {
    width: 60px;
    height: 60px;
    padding: 5px;
    color: #7b898d;
  }

  &__name {
    display: block;
    margin-top: 20px;
    font-size: 13px;
    line-height: 20px;
    font-weight: bold;
    text-transform: uppercase;
  }

  &__description {
    margin-top: 20px;

    position: relative;

    font-size: 13px;
    line-height: 20px;

  }

  &__button {
    display: block;

    position: absolute;
    left: 20px;
    right: 20px;
    bottom: 20px;

    width: calc(100% - 40px);
  }

  &__add-button {
    position: relative;
    border-radius: 10px;
    padding: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
  }

  &__add-button-icon {
    width: 40px;
    height: 40px;
  }

  &__add-button-text {
    font-size: 13px;
    line-height: 20px;
    font-weight: bold;
    text-transform: uppercase;
  }


}
</style>
