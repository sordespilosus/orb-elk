<template>
  <div class="page-admin-clients">

    <admin-actions>
      <button @click="isCreateModalShown = true" class="button">Создать</button>
      <button @click="isEditModalShown = true" class="button button--neutral" >Редактировать</button>
      <button @click="deleteClient" class="button button--negative" >Удалить</button>
    </admin-actions>

    <h2 class="heading-2 page-admin-clients__title">Доступные системы</h2>
    <base-table
    v-if="this.clients.length"
    v-model="currentClient"
    v-bind="tableData"
    class="page-admin-clients__table">
      <template #td="{ data: { key, cell }}">
        <td class="base-table__td" v-if="cell.key === 'logo'">
          <div
          v-if="cell.value.preview"
          class="page-admin-clients__logo"
          :style="`background-image: url(${cell.value.preview})`"/>
          <icon v-else name="photo" class="page-admin-clients__placeholder"/>
        </td>

        <td class="base-table__td" v-else>
          {{cell.value}}
        </td>
      </template>

    </base-table>

    <admin-client-edit
    v-if="isEditModalShown"
    @client:update="editClient"
    @modal:close="isEditModalShown = $event === 'overlay'"
    :client="currentClient"
    :scope="clientScope"
    :esia-scope="esiaScopes"
    :server-errors="serverErrors"
    title="Редактирование системы"
    is-edit
    is-moder/>

    <admin-client-edit
    v-if="isCreateModalShown"
    @client:update="createClient"
    @modal:close="isCreateModalShown = $event === 'overlay'"
    :client="emptyClient"
    :scope="clientScope"
    :esia-scope="esiaScopes"
    :server-errors="serverErrors"
    title="Создание системы"
    is-moder/>

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
    {
      key: 'only_login',
      label: 'Открвать форму ввода логина и пароля',
      handler: booleanHandler
    },
    { key: 'scope', label: 'Разрешения для системы' },
    { key: 'scope_required', label: 'Обязательное наличие данных для входа в систему' },
    { key: 'esia_level', label: 'Необходимый уровень достоверности ESIA' },
    { key: 'client_hits_esia_scope', label: 'Данные из ЕСИА' },
    {
      key: 'redirect',
      label: 'Адрес редиректа системы (несколько элементов через запятую)'
    },
    { key: 'description', label: 'Краткое описание системы' },
    {
      key: 'auth_methods',
      label: 'Методы входа'
    },
    { key: 'description', label: 'Красткое описание системы' },
    { key: 'agreement', label: 'Пользовательское соглашение системы' },
    { key: 'update_agreement' },
    { key: 'status', label: 'Подтверждённая система', handler: statusHandler },
    { key: 'reasons_for_rejections', label: 'Причины отклонения' },
    { key: 'reasons', label: 'Причина отклонения', nullable: true },
    { key: 'update_agreement' },

    {
      key: 'without_oauth_notify',
      label: 'Не показывать окно с разрешениями системы',
      handler: booleanHandler
    },
  ];

  return fields.reduce((result, field) => {
    const key = field.key;

    if (key in client || field.nullable) {
      const system_value = client[key];
      const value = field.handler ? field.handler(system_value) : system_value;
      result[key] = { ...field, system_value, value };
    }

    return result;
  }, {}); //@LODASH!!!!!
};

import AdminActions from './AdminActions';
import AdminClientEdit from './AdminClientEdit';
import FormField from './FormField';
import BaseTable from './BaseTable';
import BaseEditor from './BaseEditor';
import BaseCheckbox from './BaseCheckbox';

export default {
  name: 'PageAdminClients',
  components: {
    AdminActions,
    AdminClientEdit,
    BaseTable
  },

  data: () => ({
    serverErrors: {},
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
      only_login: false,
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
        auth_method: false,
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
      auth_methods: ['login', 'esia', 'vk', 'fb'],
      description: null,
      redirect: [],
      agreement: null,
      status: false,
      reasons_for_rejections: null,
      reasons: null,
      without_oauth_notify: false
    }),

    isEditModalShown: false,
    isCreateModalShown: false,
    clientScope: {},
    esiaScopes: {},
  }),

  computed: {
    tableData() {
      const header = [
        { label: 'ID', field: 'id' },
        { label: 'Логотип', field: 'logo' },
        { label: 'Название системы', field: 'name' },
        { label: 'Необходима ЕСИА', field: 'need_esia' },
        { label: 'Система скрыта', field: 'is_hidden' },
        { label: 'Подтверждённая система', field: 'status' }
      ];

      return { header, body: this.clients, hasRadio: true };
    }
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
      const clientData = await dispatch('fetchClients');

      this.clients = clientData.clients.map(handleClientData);
      this.clientScope = clientData.scope;
      this.esiaScopes = clientData.esiaScopes;

      this.currentClient = this.clients[0];
    },

    async editClient(params) {
      const { commit, dispatch } = this.$store;

      commit('setLoader', true);
      const {status, data, form, errors} = await dispatch('editClient', params);

      if (status) {
        this.isEditModalShown = false;
        await this.fetchClients();
        this.$notify({
          type: 'success',
          text: 'Система успешно отредактирована!'
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
      const {status, data, form, errors} = await dispatch('createClient', params);

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

    async deleteClient() {
      const { commit, dispatch } = this.$store;
      const client_id = this.currentClient.id.value;

      commit('setLoader', true);
      const isSuccess = await dispatch('deleteClient', { client_id });

      if (isSuccess) {
        this.$notify({ type: 'success', text: 'Система успешно удалена!' });
        await this.fetchClients();
      } else {
        this.$notify({ type: 'success', text: 'Удалить систему не удалось!' });
      }

      commit('setLoader', false);
    },
  },

  created() {
    this.init();
  }
};
</script>
<style lang="scss">
.page-admin-clients {
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
}
</style>
