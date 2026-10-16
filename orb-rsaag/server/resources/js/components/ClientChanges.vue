<template>
  <modal
  class="client-changes"
  :title="'Изменения системы от ' + client[fieldChanges].value.changeTime"
  v-bind="$attrs"
  v-on="$listeners">
    <template #text>
      <div class="client-changes__content">

        <div
        v-if="reason"
        class="client-changes__reason">
          <div
          v-text="'Причина отклонения:'"
          class="client-changes__reason-title" />
          <div
          v-text="reason"
          class="client-changes__reason-text"/>
        </div>

        <change
        v-for="field in fields"
        :key="field.name"
        v-if="changes.hasOwnProperty(field.name) || changes.hasOwnProperty(field.scndName)"
        class="client-changes__field">
          <template #title>{{client.hasOwnProperty(field.name) ? client[field.name].label : client[field.scndName].label}}</template>

          <template #old_value>
            <template v-if="field.type === 'char' || field.type === 'bool'">
              {{client[field.name].value}}
            </template>
            <template v-else-if="field.type === 'logo'">
              <div
              v-if="client[field.name].value.preview"
              class="page-my-clients__image"
              :style="`background-image: url(${client[field.name].value.preview});`"/>
              <icon v-else name="photo" class="page-my-clients__image-placeholder"/>
            </template>
            <template v-else-if="field.type === 'scope'">
              <ul
              class="client-changes__list">
                <li
                v-for="(value, key) in client[field.name].system_value"
                :key="key"
                v-if="value"
                class="client-changes__list-item">{{scope[key]}}{{client[field.scndName].system_value[key] ? ' (Обязательное)' : null}}</li>
              </ul>
            </template>
            <template v-else-if="field.type === 'scope_esia'">
              <ul
              v-if="getEsiaScopes(client[field.scndName].system_value)"
              class="client-changes__list">
                <li
                v-for="(value, key) in client[field.scndName].system_value"
                :key="key"
                v-if="value"
                class="client-changes__list-item"
                v-text="esiaScope[key].description" />
              </ul>
              <div
              v-else
              v-text="'Нет'" />
            </template>
            <template v-else-if="field.type === 'redirect'">
              <ul
              class="client-changes__list">
                <li
                v-for="value in client[field.name].system_value"
                :key="value"
                class="client-changes__list-item"
                v-text="value" />
              </ul>
            </template>
            <template v-else-if="field.type === 'agreement'">
              <div
              @click="showAgreement(client[field.name].system_value)"
              v-text="'Старое соглашение'"
              class="link"/>
            </template>

          </template>
          <template #new_value>
            <template v-if="field.type === 'char'">
              {{changes[field.name]}}
            </template>
            <template v-else-if="field.type === 'bool'">
              {{changes[field] ? 'Да' : 'Нет'}}
            </template>
            <template v-else-if="field.type === 'logo'">
              <div
              v-if="changes[field.name]"
              class="page-my-clients__image"
              :style="`background-image: url(${change[field.name]});`"/>
              <icon v-else name="photo" class="page-my-clients__image-placeholder"/>
            </template>
            <template v-else-if="field.type === 'scope'">
              <ul
              class="client-changes__list">
                <template v-if="changes.hasOwnProperty('scope')">
                  <li
                  v-for="value in changes[field.name]"
                  :key="value"
                  class="client-changes__list-item">{{scope[value]}}{{((changes[field.scndName] && changes[field.scndName].includes(value)) || client[field.scndName].system_value[value]) ? ' (Обязательное)' : null}}</li>
                </template>
                <template v-else>
                  <li
                  v-for="(value, key) in client[field.name].system_value"
                  :key="key"
                  v-if="value"
                  class="client-changes__list-item">{{scope[key]}}{{((changes[field.scndName] && changes[field.scndName].includes(key)) || client[field.scndName].system_value[key]) ? ' (Обязательное)' : null}}</li>
                </template>
              </ul>
            </template>
            <template v-else-if="field.type === 'scope_esia'">
              <ul
              v-if="changes[field.name].length"
              class="client-changes__list">
                <li
                v-for="value in changes[field.name]"
                :key="value"
                class="client-changes__list-item"
                v-text="esiaScope[value].description" />
              </ul>
              <div
              v-else
              v-text="'Нет'" />
            </template>
            <template v-else-if="field.type === 'redirect'">
              <ul
              class="client-changes__list">
                <li
                v-for="value in changes[field.name]"
                :key="value"
                class="client-changes__list-item"
                v-text="value" />
              </ul>
            </template>
            <template v-else-if="field.type === 'agreement'">
              <div
              @click="showAgreement(changes[field.name])"
              v-text="'Новое соглашение'"
              class="link"/>
            </template>
          </template>
        </change>

        <form-field
        v-if="isModer"
        :errors="getErrors('status')"
        class="admin-client-edit__field">
          <field-dropdown
          v-model="status"
          label-name="name"
          track-by="value"
          value-name="value"
          data-vv-as="Статус изменений"
          placeholder="Статус изменений"
          :data="statuses"
          name="status"/>
        </form-field>

        <form-field
        v-if="isModer && status === 'rejected'"
        :errors="getErrors('reasons')"
        label="Прчина отклонения"
        class="admin-client-edit__field">
          <base-textarea
          v-model="reasons"
          :isError="!!getErrors('reasons').length"
          data-vv-as="Прчина отклонения"
          name="reasons"/>
        </form-field>
      </div>
      <modal
      v-if="isAgreementModalShown"
      @modal:close="isAgreementModalShown = false"
      title="Пользовательское соглашение системы"
      :text="currentAgreement"/>
    </template>
    <template
    #buttons="{ data: { close } }">
      <button
      v-if="isModer"
      v-text="'Сохранить'"
      @click="updateClient"
      class="button"/>
      <button
      v-if="!isModer && fieldChanges === 'last_changes'"
      v-text="'Отменить'"
      @click="cancelChanges"
      class="button button--negative"/>
      <button
      @click="close('button')"
      v-text="'Закрыть'"
      class="button button--neutral"/>
    </template>
  </modal>
</template>

<script>
  import Modal from '@/js/components/Modal';
  import Change from "./Change";
  import FieldDropdown from '@/js/components/FieldDropdown';
  import BaseTextarea from '@/js/components/BaseTextarea';
  import FormField from '@/js/components/FormField';
  import {cloneDeep, map} from "lodash";

  export default {
    name: "ClientChanges",
    components: {
      Modal,
      Change,
      FieldDropdown,
      BaseTextarea,
      FormField
    },

    props:{
      client: { type: Object, required: true },
      fieldChanges: { type: String, default: 'last_changes' },
      scope: { type: Object, required: true },
      esiaScope: { type: Object, required: true },
      isModer: { type: Boolean, default: false },
      serverErrors: { type: Object, default: {} },
    },

    data: () => ({
      serverErrorsData: {},

      charFields: [
        'name',
        'url_site',
        'url_login',
        'url_notifications',
        'description'
      ],

      boolFields: [
        'need_esia',
        'only_esia',
        'need_organization',
        'is_hidden',
      ],

      esiaLevels: {
        NONE: 'Не подтверждённые учётные записи',
        STANDART: 'Проверенные учётные записи',
        TRUSTED: 'Подтверждённые учётные записи',
      },

      statuses: [
        { value: 'rejected', name: 'Отклонить' },
        { value: 'accepted', name: 'Подтвердить' },
      ],
      status: null,
      reasons: null,

      fields: [
        { name: 'name', type: 'char', scndName: null },
        { name: 'url_site', type: 'char', scndName: null },
        { name: 'url_login', type: 'char', scndName: null },
        { name: 'url_notifications', type: 'char', scndName: null },
        { name: 'need_esia', type: 'bool', scndName: null },
        { name: 'only_esia', type: 'bool', scndName: null },
        { name: 'need_organization', type: 'bool', scndName: null },
        { name: 'is_hidden', type: 'bool', scndName: null },

        { name: 'esia_level', type: 'char', scndName: null },
        { name: 'logo', type: 'logo', scndName: null },
        { name: 'scope', type: 'scope', scndName: 'scope_required' },
        { name: 'esia_scope', type: 'scope_esia', scndName: 'client_hits_esia_scope' },
        { name: 'description', type: 'char', scndName: null },
        { name: 'redirect', type: 'redirect', scndName: null },
        { name: 'agreement', type: 'agreement', scndName: null },
      ],

      isAgreementModalShown: false,
      currentAgreement: null,
    }),

    computed:{
      changes(){
        return this.client[this.fieldChanges].value.changesSum;
      },
      reason(){
        return this.client[this.fieldChanges].value.reason;
      }
    },

    methods:{
      getErrors(field) {
        return this.$store.getters.getErrors(field, this.veeErrors, this.serverErrorsData);
      },

      async updateClient() {
        const isFormValid = await this.$validator.validateAll();
        if (!isFormValid) return false;
        const params = {
          status: this.status,
          reasons: this.reasons,
          changes_ids: this.client[this.fieldChanges].value.aggregatedIds
        };

        this.$emit('changes:update', params);
      },

      cancelChanges() {
        const params = {
          changes_ids: this.client[this.fieldChanges].value.aggregatedIds
        };
        this.$emit('changes:update', params);
      },

      showAgreement(agreement){
        this.currentAgreement = agreement;
        this.isAgreementModalShown = true;
      },

      getEsiaScopes(scopes){
        return map(scopes, (item) => !!item);
      }
    },

    watch: {
      'serverErrors' (){
        this.serverErrorsData = cloneDeep(this.serverErrors);
      }
    }

  }
</script>

<style lang="scss">
  .client-changes {

    &__content {
      padding: 10px;

      > * + * {
        margin-top: 10px;
      }
    }

    &__list {
      list-style-type: disc;
      list-style-position: inside;
    }

    &__image {
      width: 40px;
      height: 40px;

      border-radius: 100%;

      background-size: contain;
      background-repeat: no-repeat;
      background-position: center;
    }

    &__image-placeholder {
      width: 40px;
      height: 40px;
      padding: 5px;
      color: #7b898d;
    }
  }
</style>