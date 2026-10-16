<template>
  <div class="admin-clients-moderation">

    <base-table
    v-if="this.clients.length"
    @table:select-row="showClient"
    v-model="currentClient"
    v-bind="tableData"
    class="admin-clients-moderation__table">
      <template #td="{ data: { key, cell }}">
        <td class="base-table__td" v-if="cell.key === 'logo'">
          <div
          v-if="cell.value.preview"
          class="admin-clients-moderation__logo"
          :style="`background-image: url(${cell.value.preview})`"/>
          <icon v-else name="photo" class="admin-clients-moderation__placeholder"/>
        </td>

        <td class="base-table__td" v-else>
          {{cell.value}}
        </td>
      </template>

    </base-table>

    <admin-client-edit
        v-if="isModalShown"
        @client:update="setStatusClient"
        @modal:close="isModalShown = $event === 'overlay'"
        :client="currentClient"
        :scope="clientScope"
        :esia-scope="esiaScopes"
        title="Модерация системы"
        :server-errors="serverErrors"
        is-moder-new-clients
        readonly/>

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
    { key: 'reasons_for_rejections', label: 'Причины отклонения' },

    { key: 'ord', label: 'Порядок', nullable: true },
    { key: 'reasons', label: 'Причина отклонения', nullable: true },
  ];

  return fields.reduce((result, field) => {
    const key = field.key;

    if (key in client || field.nullable) {
      const system_value = field.nullable ? null : client[key];
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
  name: 'AdminClientsModeration',
  components: {
    AdminActions,
    AdminClientEdit,
    BaseTable
  },

  props: {
    clientsData: { type: Array, required: true },
    clientScope: { type: Object, default: () => ({}) },
    esiaScopes: { type: Object, default: () => ({}) },
  },

  data: () => ({
    clients: [],
    currentClient: {},
    serverErrors: {},

    isModalShown: false,
  }),

  computed: {
    tableData() {
      const header = [
        { label: 'ID', field: 'id' },
        { label: 'Логотип', field: 'logo' },
        { label: 'Название системы', field: 'name' },
        { label: 'Необходима ЕСИА', field: 'need_esia' },
        { label: 'Система скрыта', field: 'is_hidden' },
      ];

      return { header, body: this.clients };
    }
  },

  methods: {
    async init() {
      this.clients = this.clientsData.map(handleClientData);
    },

    fetchClients() {
      this.$emit('fetch-clients');
    },

    async setStatusClient(row) {
      const { commit, dispatch } = this.$store;
      const clientId = this.currentClient.id.value;
      const reasons = row.reasons;
      const ord = row.ord;
      const statusSystem = row.status;

      commit('setLoader', true);
      const {status, data, form, errors} = await dispatch('setStatusClientModeration', { client_id: clientId, reasons, ord, status: statusSystem });
      commit('setLoader', false);
      if (status) {
        this.isModalShown = false;
        this.$notify({ type: 'success', text: 'Система успешно отмодерирована' });
        this.fetchClients();
      } else {
        if (errors)
          this.serverErrors = errors;
        this.$notify({ type: 'error', text: form || 'Сохранить изменения не удалось' });
      }
    },

    showClient(row){
      this.currentClient = row;
      this.isModalShown = true;
    }
  },

  watch: {
    clientsData: { handler: 'init', immediate: true }
  }
};
</script>
<style lang="scss">
.admin-clients-moderation {
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
