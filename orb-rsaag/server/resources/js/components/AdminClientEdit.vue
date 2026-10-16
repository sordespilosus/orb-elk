<template>
  <modal
  v-bind="$attrs"
  v-on="$listeners"
  class="admin-client-edit">
    <template #text>
      <div class="modal__text">
        <form-field
        :errors="getErrors('name')"
        label="Название системы"
        class="admin-client-edit__field"
        isRequired>
          <base-input
          v-model="clientData.name.system_value"
          v-validate="'required'"
          :isError="!!getErrors('name').length"
          data-vv-as="Название системы"
          name="name"
          :readonly="readonly || isModerNewClients"/>
        </form-field>

        <template v-if="isEdit">
          <form-field
          label="ID"
          class="admin-client-edit__field">
            <base-input
            :value="clientData.id.value"
            readonly/>
          </form-field>

          <form-field
          label="Дата создания"
          class="admin-client-edit__field">
            <base-input
            :value="clientData.created_at.value"
            readonly/>
          </form-field>

          <form-field
          label="Дата последнего изменения"
          class="admin-client-edit__field">
            <base-input
            :value="clientData.updated_at.value"
            readonly/>
          </form-field>

          <form-field
          label="Секретный ключ"
          class="admin-client-edit__field">
            <base-input
            :value="clientData.secret.value"
            readonly/>
          </form-field>
        </template>


        <form-field
        :errors="getErrors('url_site')"
        label="Адрес системы"
        class="admin-client-edit__field"
        isRequired>
          <base-input
          v-model="clientData.url_site.system_value"
          v-validate="{required: true, url: {require_tld: false}}"
          :isError="!!getErrors('url_site').length"
          data-vv-as="Адрес системы"
          name="url_site"
          :readonly="readonly || isModerNewClients"/>
        </form-field>

        <form-field
        :errors="getErrors('url_login')"
        label="Адрес входа в систему"
        class="admin-client-edit__field">
          <base-input
          v-model="clientData.url_login.system_value"
          v-validate="{url: {require_tld: false}}"
          :isError="!!getErrors('url_login').length"
          data-vv-as="Адрес входа в систему"
          name="url_login"
          :readonly="readonly || isModerNewClients"/>
        </form-field>

        <form-field
        :errors="getErrors('url_notifications')"
        label="Адрес получения уведомлений"
        class="admin-client-edit__field">
          <base-input
          v-model="clientData.url_notifications.system_value"
          v-validate="{url: {require_tld: false}}"
          :isError="!!getErrors('url_notifications').length"
          data-vv-as="Адрес получения уведомлений"
          name="url_notifications"
          :readonly="readonly || isModerNewClients"/>
        </form-field>

        <form-field
        :errors="getErrors('need_esia')"
        class="admin-client-edit__field">
          <base-checkbox
          v-model="needESIAModel"
          :input-value="clientData.need_esia.system_value"
          data-vv-as="Необходим профиль ЕСИА"
          name="need_esia"
          label="Необходим профиль ЕСИА"
          :readonly="readonly || isModerNewClients"/>
        </form-field>

        <form-field
        :errors="getErrors('only_esia')"
        class="admin-client-edit__field">
          <base-checkbox
          v-model="onlyEsiaModel"
          :input-value="clientData.only_esia.system_value"
          data-vv-as="Авторизация только через ЕСИА"
          name="only_esia"
          label="Авторизация только через ЕСИА"
          :readonly="readonly || isModerNewClients"/>
        </form-field>

          <form-field
              :errors="getErrors('esia_level')"
              class="admin-client-edit__field">

              <field-dropdown
                  v-model="clientData.esia_level.system_value"
                  label-name="name"
                  track-by="value"
                  value-name="value"
                  data-vv-as="Уровень учётных записей ЕСИА"
                  placeholder="Уровень учётных записей ЕСИА"
                  :data="esiaLevels"
                  :readonly="readonly || isModerNewClients"/>
          </form-field>

        <form-field
        :errors="getErrors('need_organization')"
        class="admin-client-edit__field">
          <base-checkbox
          v-model="needOrganizationModel"
          :input-value="clientData.need_organization.system_value"
          data-vv-as="Нужна авторизация как юр. лица или ИП"
          name="need_organization"
          label="Нужна авторизация как юр. лица или ИП"
          :readonly="readonly || isModerNewClients"/>
        </form-field>

        <form-field
        :errors="getErrors('is_hidden')"
        class="admin-client-edit__field">
          <base-checkbox
          v-model="clientData.is_hidden.system_value"
          v-validate
          :input-value="clientData.is_hidden.system_value"
          data-vv-as="Система скрыта"
          name="is_hidden"
          label="Система скрыта"
          :readonly="readonly || isModerNewClients"/>
        </form-field>

        <form-field
            :errors="getErrors('dont_remember')"
            class="admin-client-edit__field">
            <base-checkbox
                v-model="clientData.dont_remember.system_value"
                :input-value="clientData.dont_remember.system_value"
                data-vv-as="Запоминать пользователя после авторизации"
                name="dont_remember"
                label="Запоминать пользователя после авторизации"
                :readonly="readonly || isModerNewClients"/>
        </form-field>

        <form-field
        :errors="getErrors('without_oauth_notify')"
        class="admin-client-edit__field">
          <base-checkbox
          v-model="clientData.without_oauth_notify.system_value"
          :input-value="clientData.without_oauth_notify.system_value"
          data-vv-as="Не показывать окно с разрешениями системы"
          name="without_oauth_notify"
          label="Не показывать окно с разрешениями системы"/>
        </form-field>

        <form-field
        :errors="getErrors('only_login')"
        class="admin-client-edit__field">
          <base-checkbox
          v-model="clientData.only_login.system_value"
          :input-value="clientData.only_login.system_value"
          data-vv-as="' '"
          name="dont_remember"
          label="Открывать форму ввода логина и пароля"/>
        </form-field>

        <form-field
        :errors="getErrors('logo')"
        label="Логотип"
        class="admin-client-edit__field">
          <image-loader
          :value="{file: null, preview: clientData.logo.system_value}"
          @input="clientData.logo.system_value = $event.preview"
          name="logo"
          :readonly="readonly || isModerNewClients"/>
        </form-field>

        <form-field
            label="Разрешения для системы (первая галочка доступ к данным, вторая - обязательность данных)"
            :errors="getErrors('scope') || getErrors('scope_required')"
            class="admin-client-edit__field">
            <div
                class="admin-client-edit__field-double-checkbox"
                v-for="(value, key) in clientData.scope.system_value"
                :key="key">
                <base-checkbox
                    v-model="clientData.scope.system_value[key]"
                    :label="!scopeRequiredField.includes(key) ? scope[key] : null"
                    :input-value="value"
                    :readonly="readonly || isModerNewClients"/>
                <base-checkbox
                    v-if="scopeRequiredField.includes(key)"
                    v-model="clientData.scope_required.system_value[key]"
                    :label="scope[key]"
                    :input-value="clientData.scope_required.system_value[key]"
                    :readonly="readonly || isModerNewClients"/>
            </div>

        </form-field>

        <form-field
            label="Данные ЕСИА из системы"
            :errors="getErrors('client_hits_esia_scope')"
            class="admin-client-edit__field">
            <base-checkbox
                v-for="(value, key) in clientData.client_hits_esia_scope.system_value"
                v-model="clientData.client_hits_esia_scope.system_value[key]"
                :label="esiaScope[key].description"
                :input-value="value"
                :key="key"
                :readonly="readonly || isModerNewClients"/>
        </form-field>

        <form-field
        label="Методы входа"
        :errors="getErrors('auth_methods')"
        class="admin-client-edit__field">
          <checkbox-group
          v-model="clientData.auth_methods.system_value"
          :items="authMethods"
          direction="column"
          name="auth_methods"
          class=""/>
        </form-field>

        <form-field
        :errors="getErrors('description')"
        label="Краткое описание системы"
        class="admin-client-edit__field">
          <base-textarea
          v-model="clientData.description.system_value"
          :isError="!!getErrors('description').length"
          data-vv-as="Описание системы"
          name="description"
          :readonly="readonly || isModerNewClients"/>
        </form-field>

        <form-field
        :errors="getErrors('redirect')"
        label="Адреса редиректа системы"
        class="admin-client-edit__field"
        isRequired>
          <div
          v-for="(value, key) in clientData.redirect.system_value"
          :key="key"
          class="admin-client-edit__redirect">
            <base-input
            v-model="clientData.redirect.system_value[key]"
            v-validate="{required: true, url: {require_tld: false}}"
            :isError="!!getErrors(`redirect.${key}`).length"
            data-vv-as="Адреса редиректа системы"
            :name="`redirect.${key}`"
            class="admin-client-edit__redirect-input"
            :readonly="readonly || isModerNewClients"/>
            <icon
            v-if="!readonly"
            @click="deleteRedirectUrl(key)"
            name="close"
            class="admin-client-edit__remove-redirect-icon"/>
          </div>
          <div
          v-if="!readonly"
          @click="addRedirectUrl"
          class="admin-client-edit__add-redirect">
            <icon name="plus" class="admin-client-edit__add-redirect-icon"/>
            <div
            v-text="'Добавить адрес'"
            class="admin-client-edit__add-redirect-text"/>
          </div>
        </form-field>

        <form-field
        :errors="getErrors('agreement')"
        label="Пользовательское соглашение системы"
        class="admin-client-edit__field">
          <div
          v-if="clientData.agreement.system_value"
          @click="isAgreementModalShown = true"
          v-text="'Нажмите для просмотра пользовательского соглашения'"
          class="link"/>
          <base-editor
          v-model="clientData.agreement.system_value"
          :disabled="(isEdit && !clientData.update_agreement.system_value)"
          :readonly="readonly || isModerNewClients"/>
        </form-field>

        <form-field
        v-if="isEdit"
        :errors="getErrors('update_agreement')"
        class="admin-client-edit__field">
          <base-checkbox
          v-model="clientData.update_agreement.system_value"
          label="Обновить пользовательское соглашение"
          :input-value="clientData.update_agreement.system_value"
          :readonly="readonly || isModerNewClients"/>
        </form-field>

        <form-field
        v-if="isModer || isModerNewClients"
        :errors="getErrors('status')"
        class="admin-client-edit__field">

          <field-dropdown
          v-model="clientData.status.system_value"
          label-name="name"
          track-by="value"
          value-name="value"
          data-vv-as="Статус системы"
          placeholder="Статус системы"
          :data="statuses"
          name="status"/>
        </form-field>

        <form-field
        v-if="(isModer || isModerNewClients) && clientData.status.system_value === 'rejected'"
        :errors="getErrors('reasons')"
        label="Прчина отклонения"
        class="admin-client-edit__field">
          <base-textarea
          v-model="clientData.reasons.system_value"
          :isError="!!getErrors('reasons').length"
          data-vv-as="Прчина отклонения"
          name="reasons"/>
        </form-field>

        <form-field
        v-if="isModer || (isModerNewClients && clientData.status.system_value === 'approved')"
        :errors="getErrors('ord')"
        label="Порядок"
        class="admin-client-edit__field">
          <base-input
          v-model="clientData.ord.system_value"
          v-validate="'numeric'"
          :isError="!!getErrors('ord').length"
          data-vv-as="Порядок"
          name="ord"/>
        </form-field>

        <div
        v-if="(isEdit && !isModer && clientData.status.system_value === 'rejected') || (((isEdit && isModer) || isModerNewClients) && clientData.reasons_for_rejections.value && clientData.reasons_for_rejections.value.length)"
        class="client-changes__reason">
          <div
          v-text="isModerNewClients ? 'Последняя причина отклонения' : 'Причина отклонения:'"
          class="client-changes__reason-title" />
          <div
          v-text="clientData.reasons_for_rejections.value[0].reasons"
          class="client-changes__reason-text"/>
        </div>
      </div>

      <modal
      v-if="isAgreementModalShown"
      @modal:close="isAgreementModalShown = false"
      title="Пользовательское соглашение системы"
      :text="clientData.agreement.system_value"/>
    </template>

    <template #buttons>
      <button
      v-text="buttonText"
      @click="updateClient"
      class="button"/>
    </template>
  </modal>
</template>

<script>
import { keys, pickBy, mapKeys, mapValues, cloneDeep, uniq } from 'lodash';

import Modal from '@/js/components/Modal';
import FormField from '@/js/components/FormField';
import BaseInput from '@/js/components/BaseInput';
import BaseTextarea from '@/js/components/BaseTextarea';
import BaseCheckbox from '@/js/components/BaseCheckbox';
import BaseEditor from '@/js/components/BaseEditor';
import ImageLoader from '@/js/components/ImageLoader';
import FieldDropdown from '@/js/components/FieldDropdown';
import CheckboxGroup from '@/js/components/CheckboxGroup';

export default {
  name: 'AdminClientEdit',
  components: {
    Modal,
    FormField,
    BaseInput,
    BaseTextarea,
    BaseCheckbox,
    BaseEditor,
    ImageLoader,
    FieldDropdown,
    CheckboxGroup
  },
  inheritAttrs: false,
  props: {
    client: { type: Object, required: true },
    scope: { type: Object, required: true },
    esiaScope: { type: Object, required: true },
    isEdit: { type: Boolean, default: false },
    isModer: { type: Boolean, default: false },
    readonly: { type: Boolean, default: false },
    isModerNewClients: { type: Boolean, default: false },
    serverErrors: { type: Object, default: {} },
  },

  data() {
    return {
      clientData: cloneDeep(this.client),
      serverErrorsData: {},

      esiaLevels: [
        { value: 'NONE', name: 'Не подтверждённые учётные записи' },
        { value: 'STANDART', name: 'Проверенные учётные записи' },
        { value: 'TRUSTED', name: 'Подтверждённые учётные записи' },
      ],

      authMethods: [
        { value: 'login', label: 'Логин и пароль' },
        { value: 'esia', label: 'Госуслуги' },
        { value: 'vk', label: 'ВКонтакте' },
        { value: 'fb', label: 'Facebook' },
      ],

      scopeRequiredField: [
        'email',
        'esia_user_id',
        'fb_user_id',
        'esia_address',
        'passport',
        'phone',
        'snils',
        'vk_user_id',
        'organizations_user'
      ],

      statuses: [
        { value: 'sent', name: 'На модерации' },
        { value: 'rejected', name: 'Отклонена' },
        { value: 'approved', name: 'Подтверждена' },
      ],

      isAgreementModalShown: false
    };
  },

  computed: {
    needOrganizationModel: {
      get() {
        return this.clientData.need_organization.system_value;
      },

      set(value) {
        const { need_organization, need_esia } = this.clientData;

        need_organization.system_value = value;
        if (value) need_esia.system_value = value;
      }
    },

    onlyEsiaModel: {
      get() {
        return this.clientData.only_esia.system_value;
      },
      set(value) {
        const { only_esia, need_esia } = this.clientData;
        only_esia.system_value = value;
        if (value) need_esia.system_value = value;
      }
    },

    needESIAModel: {
      get() {
        return this.clientData.need_esia.system_value;
      },

      set(value) {
        const isTrue = value;
        const { need_organization, need_esia } = this.clientData;

        need_esia.system_value = value;
        if (!isTrue) need_organization.system_value = value;
      }
    },

    buttonText(){
      if (this.isModer || this.isModerNewClients){
        return 'Сохранить';
      }
      return {
        rejected: 'Отправить повтороно',
        approved: 'Изменить',
        sent: 'Изменить',
      }[this.clientData.status.system_value] || 'Создать'
    }
  },

  methods: {
    getErrors(field) {
      if (field === 'redirect') {
        let errors = this.$store.getters.getErrors('redirect', this.veeErrors, this.serverErrorsData);
        for (let i = 0; i < this.clientData.redirect.system_value.length; i++){
          errors = errors.concat(this.$store.getters.getErrors(`redirect.${i}`, this.veeErrors, this.serverErrorsData))
        }
        return uniq(errors);
      }
      else {
        return this.$store.getters.getErrors(field, this.veeErrors, this.serverErrorsData);
      }
    },

    async updateClient() {
      const isFormValid = await this.$validator.validateAll();
      if (!isFormValid) return false;
      const params = mapValues(this.clientData, 'system_value');
      params.scope = JSON.stringify(keys(pickBy(params.scope)));
      params.scope_required = JSON.stringify(keys(pickBy(params.scope_required)));
      params.client_hits_esia_scope = JSON.stringify(keys(pickBy(params.client_hits_esia_scope)));

      this.$emit('client:update', params);
    },

    deleteRedirectUrl(key){
      this.clientData.redirect.system_value.splice(key, 1);
    },

    addRedirectUrl(){
      this.clientData.redirect.system_value.push('');
    }
  },

  watch: {
    'serverErrors' (){
      this.serverErrorsData = cloneDeep(this.serverErrors);
    }
  }
};
</script>
<style lang="scss">
.admin-client-edit {
  &__field {
    margin-bottom: 20px;
  }

  &__button {
    margin-top: 30px;
  }

  &__field-double-checkbox {
      display: flex;
      align-items: center;
      > * {
          flex: 0 0 auto;
      }

      > * + * {
          margin-left: 10px;
      }
  }

  &__redirect {
    position: relative;
  }

  &__redirect-input {

    .base-input__input {
      padding-right: 40px;
    }
  }

  &__remove-redirect-icon {
    position: absolute;
    top: calc(50% - 15px);
    right: 5px;
    width: 30px;
    height: 30px;
    cursor: pointer;
    opacity: 0.8;
    transition: opacity 0.3s;

    &:hover {
      opacity: 1;
      color: #ff5555;
    }
  }

  &__add-redirect {
    display: flex;
    align-items: flex-end;
    opacity: 0.8;
    transition: opacity 0.3s;
    color: #32BA91;

    &:hover {
      opacity: 1;
    }

    > * + * {
      margin-left: 10px;
    }

    &__add-redirect-icon {
      width: 20px;
      height: 20px;
    }
  }

}
</style>
