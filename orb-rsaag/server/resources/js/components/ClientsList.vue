<template>
  <div class="clients-list">

    <ul class="clients-list__list">
      <li
      v-for="client in clients"
      :key="client.id"
      class="clients-list__item">

        <div
        v-if="client.notice"
        v-text="client.notice"
        class="clients-list__notice"/>

        <div
        v-if="client.notice_cnt"
        v-text="getNotices(client.notice_cnt)"
        @click="goToNotifications(client.id)"
        class="clients-list__notifications"/>

        <div
        v-if="client.logo"
        class="clients-list__image"
        :style="`background-image: url(${client.logo});`"/>
        <icon v-else name="photo" class="clients-list__image-placeholder"/>

        <a
        v-text="client.name"
        :href="client.url_site"
        target="_blank"
        class="link clients-list__name"/>

        <div
        v-if="client.description"
        v-text="client.description"
        @click="showDescription(client)"
        class="clients-list__description"/>

        <button
        v-if="!isUserESIA && client.need_esia"
        v-text="'Войти'"
        @click="loginESIA(client)"
        class="button button--invert clients-list__button"/>

        <button
        v-else-if="!hasUserOrgFields && client.need_organization"
        v-text="'Войти'"
        @click="loginOrganization(client)"
        class="button button--invert clients-list__button"/>

        <a
        v-else
        v-text="'Войти'"
        :href="getLink(client)"
        class="button button--invert clients-list__button"/>
      </li>
    </ul>

    <modal
    v-if="isESIAVerificationModalShown"
    @modal:close="isESIAVerificationModalShown = false"
    title="Внимание!"
    :text="`Для авторизации в системе <b>${currentClient.name}</b> Вам необходимо подтвердить Ваш профиль на портале Госуслуг<br>Подтвердить сейчас?`"
    max-width="800">
      <template #buttons>
        <a
        v-text="'Подтвердить'"
        :href="getLink(currentClient)"
        class="button"/>

        <button
        class="button button--neutral"
        v-text="'Отмена'"
        @click="isESIAVerificationModalShown = false"/>
      </template>
    </modal>

    <modal
    v-if="isClientDescriptionModalShown"
    @modal:close="isClientDescriptionModalShown = false"
    :title="currentClient.name"
    max-width="800">
      <template #text>
        <div class="wysiwyg modal__text">
          <p v-text="currentClient.description"/>

          <template v-if="currentClient.need_esia || currentClient.need_organization">
            <p v-text="'Для входа в систему необходимо:'"/>

            <ul>
              <li
              v-if="currentClient.need_esia"
              v-text='`Прикреплённая учётная запись запись Госуслуг уровня "${esiaLevel(currentClient.esia_level)}"`'/>

              <li
              v-if="currentClient.need_organization"
              v-text="'Быть юридическим лицом или индивидуальным предпринимателем'"/>
            </ul>
          </template>

          <template v-if="currentClient.scope">
            <p v-text="'Для входа в систему необходимо будет предоставить следующие разрешения:'"/>
              <ul class="clients-list__scopes">
                <li
                v-for="(scope, name) in currentClient.scope"
                v-text="scope"
                :key="name"
                class="clients-list__scopes-item"/>
              </ul>
          </template>
            <template v-if="currentClient.scope && currentClient.scope_required.length">
                <p v-text="'Должны быть заполнены следующие поля:'"/>
                <ul class="clients-list__scopes">
                    <li
                        v-for="scope in currentClient.scope_required"
                        v-text="requiredScope[scope]"
                        :key="scope"
                        class="clients-list__scopes-item"/>
                </ul>
            </template>
            <template v-if="currentClient.esia_scope">
                <p v-text="'Также при входе будут предоставлены следующие данные из вашего профиля Госуслуг:'"/>
                <ul class="clients-list__scopes">
                    <li
                        v-for="(scope, name) in currentClient.esia_scope"
                        v-text="scope"
                        :key="name"
                        class="clients-list__scopes-item"/>
                </ul>
            </template>
          <p v-if="currentClient.agreement_text">
            <a
            v-text="`Пользовательское соглашение системы ${currentClient.name} (Обновлено ${currentClient.agreement_date})`"
            @click="isAgreementModalShown = true"
            class="link"/>
          </p>
        </div>
      </template>
    </modal>

    <modal
    v-if="isAgreementModalShown"
    @modal:close="isAgreementModalShown = false"
    title="Пользовательское соглашение системы"
    :text="currentClient.agreement_text"/>

    <modal
    v-if="isOrganizationModalShown"
    @modal:close="isOrganizationModalShown = false"
    title="Внимание!"
    :text="`Для авторизации в системе <b>${currentClient.name}</b> Вам необходимо быть юридическим лицом или индивидуальным предпринимателем и иметь подтверждённые организации.<br>Привязать профиль Госуслуг с юридическим лицом или индивидуальным предпринимателем или обновить существующий сейчас?`"
    max-width="800">
      <template #buttons>
        <a
        v-text="'Обновить'"
        href="/loginESIA"
        class="button"/>

        <button
        v-text="'Отмена'"
        @click="isOrganizationModalShown = false"
        class="button button--neutral"/>
      </template>
    </modal>

    <modal
    v-if="isNotificationModalShown"
    @modal:close="isNotificationModalShown = false"
    title="Уведомления системы"
    max-width="600">
    <template #text>
      <div class="wysiwyg clients-list__notifications-modal">
      <p>
        <strong>04.12.2019 14:38</strong><br>
      Для вас подготовлен новый ответ. <a href="https://vmeste.permkrai.ru/login">Зайдите в систему</a>, чтобы посмотреть.
      </p>

      <p>
        <strong>05.12.2019 12:13</strong><br>
      Ваше сообщение <a href="https://vmeste.permkrai.ru/messages/reports/38350">№38350</a> отклонено
      </p>
      </div>
    </template>

    </modal>

  </div>
</template>
<script>
import axios from 'axios';
import Modal from './Modal';
import { format, parseISO } from 'date-fns';

export default {
  name: 'ClientsList',
  components: { Modal },
  props: {
    clients: { type: Array, required: true },
    isUserESIA: { type: Boolean, default: false },
    hasUserESIAFields: { type: Boolean, default: false },
    hasUserOrgFields: { type: Boolean, default: false }
  },

  data: () => ({
    isESIAVerificationModalShown: false,
    isClientDescriptionModalShown: false,
    isAgreementModalShown: false,
    isOrganizationModalShown: false,

    isNotificationModalShown: false,

    currentClient: {},

    requiredScope:{
      email: 'E-mail',
      esia_user_id: 'ID пользователя в Госуслугах',
      fb_user_id: 'ID пользователя в Facebook',
      esia_address: 'Адрес пользователя',
      passport: 'Паспортные данне',
      phone: 'Номер телефона',
      snils: 'СНИЛС',
      vk_user_id: 'ID пользователя в ВК',
    }
  }),

  methods: {

    getLink(client) {
      const userNeedsESIA = !this.isUserESIA && client.need_esia,
        userHasNoESIAFields = !this.hasUserESIAFields && this.isUserESIA,
        loginURL = client.url_login;

      const ESIAURL = `/loginESIA?redirect=${encodeURIComponent(loginURL)}`;

      return userNeedsESIA || userHasNoESIAFields ? ESIAURL : loginURL;
    },

    showDescription(client) {
      const infoFields = [
        'description',
        'scope',
        'agreement_text',
        'need_esia',
        'need_organization'
      ];
      const hasInformation = infoFields.some(field => client[field]);

      if (hasInformation) {
        this.currentClient = client;
        this.isClientDescriptionModalShown = true;
      }
    },

    loginESIA(client) {
      this.currentClient = client;
      this.isESIAVerificationModalShown = true;
    },

    loginOrganization(client) {
      this.currentClient = client;
      this.isOrganizationModalShown = true;
    },

    formatDate(date) {
      const parsedDate = parseISO(date);
      return format(parsedDate, 'dd.MM.yyyy');
    },

    esiaLevel(esiaLevel){
      return {
        //'NONE': 'Упрощённая учётная запись',
        'STANDART': 'Проверенная учётная запись',
        'TRUSTED': 'Подтверждённая учётная запись'
      }[esiaLevel] || 'Упрощённая учётная запись';
    },

    goToNotifications(clientId){
      this.$router.push({ name: 'pageNotices', query: { client_id: clientId } })
    },

    getNotices(cnt){
      return cnt >= 100 ? '+99' : cnt;
    },
  }
};
</script>
<style lang="scss">
@import '~@/sass/variables';

.clients-list {

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

  &__name {
    display: block;
    margin-top: 20px;
    font-size: 13px;
    line-height: 20px;
    font-weight: bold;
    text-transform: uppercase;
  }

  &__notice {
    position: absolute;
    top: -15px;
    right: -15px;

    width: 30px;
    height: 30px;
    padding: 5px 0;

    font-size: 13px;
    line-height: 20px;
    font-weight: bold;
    color: #fff;
    background-color: $color-accent;

    border-radius: 100%;
    white-space: nowrap;
    text-align: center;
  }

  &__description {
    margin-top: 20px;

    position: relative;
    max-height: 80px;
    min-height: 40px;

    font-size: 13px;
    line-height: 20px;

    overflow: hidden;
    cursor: pointer;

    &::after {
      content: '';
      position: absolute;
      left: 0;
      right: 0;
      bottom: 0;
      height: 20px;
      background-image: linear-gradient(to right, rgba(255, 255, 255, 0), #fff);
    }
  }

  &__button {
    display: block;

    position: absolute;
    left: 20px;
    right: 20px;
    bottom: 20px;

    width: calc(100% - 40px);
  }

  &__notifications {
    position: absolute;
    right: 20px;
    top: 20px;

    width: 30px;

    font-size: 15px;
    line-height: 30px;
    color: #fff;
    text-align: center;

    background-color: $color-accent;

    border-radius: 100%;

    cursor: pointer;
  }

  &__notifications-modal {
    padding: 0 20px;
  }
}
</style>
