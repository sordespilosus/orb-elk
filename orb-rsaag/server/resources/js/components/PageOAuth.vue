<template>
  <div class="page-oauth">

    <app-header class="page-oauth__header"/>

    <div class="page-oauth__content wysiwyg">

        <div
            class="page-oauth__user"
            v-if="$store.getters.isAuth" v-text="userText" />

      <div class="page-oauth__text">
        Для входа в <span class="page-oauth__name">{{ client.name }}</span> необходимо предоставить доступ к следующим данным Вашего аккаунта ГИС ЕЛК ОО:
      </div>
      <ul
      v-if="scopes.length"
      class="page-oauth__list">
        <li
        v-for="scope in scopes"
        :key="scope.id"
        class="page-oauth__scopes-item">{{ scope.description }}</li>
      </ul>

      <template v-if="client.esia_scope">
        <div class="page-oauth__text">
          Необходимо предоставить следующие данные из вашего профиля Госуслуг:
        </div>
        <ul
        class="page-oauth__list">
          <li
          v-for="(scope, name) in client.esia_scope"
          :key="name"
          class="page-oauth__scopes-item">{{ scope }}</li>
        </ul>
      </template>

      <div v-if="agreement && agreement.agreement" class="page-oauth__text">
        Продолжая, Вы принимаете
        <a
        @click="isModalShown = true"
        class="link">Пользовательское соглашение системы {{client.name}}</a>

        (Редакция обновлена {{formatDate(agreement.created_at)}})
      </div>

      <template v-if="client.need_organization && organizations.length">
        <div class="page-oauth__text">Для авторизации в системе <span class="page-oauth__name">{{client.name}}</span>, Вам необходимо выбрать через какое юридическое лицо или индивидуального предпринимателя войти.</div>
        <div class="page-oauth__text">Выберите из списка:</div>
        <div class="page-oauth__text">
          <base-radio
          v-for="organization in organizations"
          v-model="currentOrganization"
          :input-value="organization.id"
          :label="`${organization.short_name} (${organization.full_name})`"
          :key="organization.id"
          name="organization"
          class="page-oauth__radio"/>
        </div>
      </template>

      <div
      v-if="needUpdateEsia"
      class="page-oauth__warning">Требуется авторизация через Госуслуги.</div>

      <div
      v-if="client.need_organization && !organizations.length"
      class="page-oauth__warning">Для входа в эту систему необходимо быть юридическим лицом или индивидуальным предпринимателем и иметь подтверждённые организации</div>

      <div
          v-if="!hasTrustedLevel"
          class="page-oauth__warning">Необходимо иметь уровень учётной записи ЕСИА - {{esiaLevel}}</div>
      <template v-if="hasRequiredScopes.length">
          <div class="page-oauth__text page-oauth__error-text">
              Для входа в систему вам необходимо заполнить следующие данные:
          </div>
          <ul
              class="page-oauth__list">
              <li
                  v-for="scope in hasRequiredScopes"
                  :key="scope"
                  class="page-oauth__scopes-item page-oauth__error-item" v-html="requiredScope[scope]" />
          </ul>
      </template>
    </div>

    <div
        v-if="!$store.getters.isAuth"
        class="page-oauth__auth-text"
        v-text="'Для предоставления доступа, авторизуйтесь в ГИС ЕЛК ОО.'" />

    <div class="page-oauth__buttons">
      <a
      v-if="this.hasRequiredScopes && this.hasRequiredScopes.length !== 0"
      class="button"
      v-text="'В мой профиль'"
      href="/profile" />
      <form
      v-else
      method="get"
      action="/oauth/approve_authorize"
      class="page-oauth__button">
          <input type="hidden" name="_token" :value="$store.getters.csrf">
          <input type="hidden" name="state" :value="state">
          <input type="hidden" name="org_id" :value="currentOrganization">
          <input type="hidden" name="client_id" :value="client.id">
          <button
              class="button"
              :disabled="isButtonDisabled"
              :class="{'button--positive': this.$store.getters.isAuth}">
            {{buttonText}}
          </button>
      </form>

      <form method="post" action="/oauth/authorize" class="page-oauth__button">
          <input type="hidden" name="_token" :value="$store.getters.csrf">
          <input type="hidden" name="_method" value="DELETE">
          <input type="hidden" name="state" :value="state">
          <input type="hidden" name="client_id" :value="client.id">
          <button class="button button--negative">Отмена</button>
      </form>
    </div>

    <modal
    v-if="isModalShown"
    @modal:close="isModalShown = false"
    title="Пользовательское соглашение"
    :text="agreement.agreement"/>

  </div>
</template>
<script>
import { format, parseISO } from 'date-fns';
import BaseRadio from './BaseRadio';
import Modal from './Modal';
import { isEmpty } from 'lodash';
import AppHeader from '@/js/components/AppHeader';

export default {
  name: 'PageOAuth',
  components: { BaseRadio, Modal, AppHeader },

  props: {
    client: { type: Object },
    scopes: { type: Array },
    state: { type: String },
    agreement: { type: Object },
    organizations: { type: Array },
    needUpdateEsia: { type: Boolean },
    hasRequiredScopes: { type: Array, default: () => ([]) },
    hasTrustedLevel: { type: Boolean },
    allScopes: { type: Object },
  },

  data() {
    return {
      isModalShown: false,
      currentOrganization: this.organizations[0] ? this.organizations[0].id : null,
      requiredScope:{
        email: 'E-mail',
        esia_user_id: 'ID пользователя в Госуслугах',
        fb_user_id: 'ID пользователя в Facebook',
        esia_address: 'Адрес пользователя (в Вашем <a class="link" href="https://esia.gosuslugi.ru/profile/user/personal" target="_blank">профиле Госуслуг</a>)',
        passport: 'Паспортные данные (в Вашем <a class="link" href="https://esia.gosuslugi.ru/profile/user/personal" target="_blank">профиле Госуслуг</a>)',
        phone: 'Номер телефона',
        snils: 'СНИЛС (в Вашем <a class="link" href="https://esia.gosuslugi.ru/profile/user/personal" target="_blank">профиле Госуслуг</a>)',
        vk_user_id: 'ID пользователя в ВК',
      }
    }
  },

  computed: {

    isButtonDisabled() {
      const needOrganization =
        this.client.need_organization && !this.organizations.length && this.$store.getters.isAuth;

      return needOrganization || (this.hasRequiredScopes && this.hasRequiredScopes.length !== 0);
    },

    buttonText() {
      if (!this.$store.getters.isAuth){
          return 'АВТОРИЗАЦИЯ';
      }
      if (!this.hasTrustedLevel){
        return 'Войти через Госуслуги'
      }
      if (this.agreement && this.agreement.agreement) {
        if (this.needUpdateEsia) return 'Принять и войти через Госуслуги';
        else return 'Принять и разрешить';
      } else {
        if (this.needUpdateEsia) return 'Войти через Госуслуги';
        else return 'Разрешить';
      }
    },

    userText(){
        if (!this.$store.getters.isAuth) return null;
        return ({'male': 'Уважаемый ', 'female': 'Уважаемая '}[this.$store.state.user.gender] || 'Уважаемый/ая ') + this.$store.state.user.fio;
    },

    esiaLevel(){
      return {
        //'NONE': 'Упрощённая учётная запись',
        'STANDART': 'Проверенная учётная запись',
        'TRUSTED': 'Подтверждённая учётная запись'
      }[this.client.esia_level] || 'Упрощённая учётная запись';
    }
  },

  methods: {
    formatDate(date) {
      const parsedDate = parseISO(date);
	    return date ? format(parsedDate, 'dd.MM.yyyy') : '';
    },

    setAppLayout(value) {
      this.$store.commit('setData', { path: 'isHeaderHidden', value });
      this.$store.commit('setData', { path: 'isFooterHidden', value });
    }
  },

  created() {
      this.setAppLayout(true);
      //this.$once('hook:beforeDestroy', () => this.setAppLayout(false)); //TODO Мы отсюда никуда с помощью роутера не переходим, следственно востанавливать переменные окружения на м не нужно
  }
};
</script>
<style lang="scss">
.page-oauth {
  max-width: 500px;
  padding: 20px;
  margin: auto;
  background-color: #fff;
  border-radius: 10px;
  flex: none;
  > * + * {
      margin-top: 20px;
  }

  &__header {
    padding-bottom: 10px;
    margin-bottom: 10px;
    border-bottom: 1px solid #d3d8d8;

    .app-header__user {
      display: none;
    }
  }

  &__icon {
    flex: 0 0 auto;
    margin-right: 15px;
    color: #3b5467;
  }

  &__title {
    flex: 1 1 auto;
  }

  &__content {
    > * + * {
        margin-top: 10px;
    }
  }

  &__name {
    font-weight: bold;
  }

  &__text {
    margin-bottom: 20px;
  }

  &__list {
    margin-bottom: 20px;
  }

  &__radio {
    margin-bottom: 10px;
  }

  &__button {
      white-space: normal;
  }

    &__buttons {
        display: grid;
        gap: 20px;
        grid-template-columns: repeat(2, 1fr);
        align-items: center;
    }

    &__auth-text {
        padding-top: 20px;
        border-top: 1px solid #d3d8d8;
    }

    &__error-text, &__error-item {
        font-weight: bold;
    }
}
</style>
