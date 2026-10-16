<template>
  <div class="change-password">
    <form
    v-show="isChangePassword"
    @submit.prevent="changePasswordData"
    class="change-password__change">
      <form-field
      :errors="getErrors('passwordData.old_password')"
      label="Старый пароль"
      class="change-password__field"
      isRequired>
        <template #toggle v-if="$store.state.user.email">
          <span
          v-text="'не помню'"
          @click="isChangePassword = false"/>
        </template>
        <base-input
        v-model="passwordData.old_password"
        v-validate="{required: true, password: true}"
        data-vv-scope="passwordData"
        data-vv-as="Старый пароль"
        :isError="!!getErrors('passwordData.old_password').length"
        type="password"
        name="old_password"
        placeholder="Старый пароль"/>
      </form-field>


      <div class="change-password__section">

        <form-field
        :errors="getErrors('passwordData.password')"
        label="Новый пароль"
        class="change-password__field"
        isRequired>
          <base-input
          v-model="passwordData.password"
          v-validate="{required: true, password: true, new_password: passwordData.old_password}"
          data-vv-scope="passwordData"
          data-vv-as="Новый пароль"
          :isError="!!getErrors('passwordData.password').length"
          type="password"
          name="password"
          placeholder="Новый пароль"/>
        </form-field>

        <form-field
        :errors="getErrors('passwordData.password_confirmation')"
        label="Подтверждение нового пароля"
        class="change-password__field"
        isRequired>
          <base-input
          v-model="passwordData.password_confirmation"
          v-validate="{required: true, password_confirmation: passwordData.password, password: true}"
          data-vv-scope="passwordData"
          data-vv-as="Подтверждение нового пароля"
          :isError="!!getErrors('passwordData.password_confirmation').length"
          name="password_confirmation"
          type="password"
          placeholder="Подтверждение нового пароля"/>
        </form-field>

      </div>

      <div class="change-password__buttons">
        <button
        v-text="'Сохранить'"
        :disabled="isButtonDisabled('passwordData')"
        class="change-password__button"
        :class="isButtonDisabled('passwordData') ? 'button button--neutral' : 'button'"/>

        <button
        type="button"
        @click="revertChanges"
        v-text="'Отменить'"
        class="button button--neutral change-password__button"/>
      </div>
    </form>


    <form
    v-show="!isChangePassword"
    @submit.prevent="restorePasswordData"
    class="change-password__restore">
      <div
      v-text="'Письмо с инструкцией по изменению пароля будет направлено на адрес электронной почты, указанный при регистрации'"
      class="change-password__restore-text"/>
      <form-field class="change-password__field">
        <span
        v-text="'указать пароль'"
        @click="isChangePassword = true"
        slot="toggle"/>

        <base-input
        :value="maskEmail(serverData.email)"
        disabled/>
      </form-field>

      <div class="change-password__buttons">
        <button
        v-text="'Восстановить пароль'"
        class="button change-password__button"/>
      </div>

    </form>

    <modal
    v-if="isAfterChangingPasswordModalShown"
    @modal:close="isAfterChangingPasswordModalShown = false"
    title="Пароль успешно изменен"
    text="<p>Пароль Вашей учётной записи изменён.</p><p>Используйте новый пароль при следующем входе в Вашу учётную запись.</p>"
    max-width="380"/>
  </div>
</template>

<script>
import { every } from 'lodash';
import { maskEmail } from '../helpers';

import FormField from '@/js/components/FormField';
import BaseInput from '@/js/components/BaseInput';
import Modal from '@/js/components/Modal';

export default {
  name: 'ChangePassword',
  components: { FormField, BaseInput, Modal },

  props: {
    serverData: { type: Object, default: () => ({}) }
  },

  data() {
    return {
      passwordData: {
        old_password: null,
        password: null,
        password_confirmation: null
      },

      serverErrors: {
        passwordData: null,
        restoreData: null
      },

      isChangePassword: true,

      isAfterChangingPasswordModalShown: false
    };
  },

  methods: {
    getErrors(field) {
      return this.$store.getters.getErrors(
        field,
        this.veeErrors,
        this.serverErrors
      );
    },

    isButtonDisabled(name) {
      return every(this.veeFields[`$${name}`], { dirty: false });
    },

    maskEmail(email) {
      return maskEmail(email);
    },

    async changePasswordData() {
      const isFormValid = await this.$validator.validateAll('passwordData');
      if (!isFormValid) return false;

      const { commit, dispatch } = this.$store;

      commit('setLoader', true);
      this.serverErrors.passwordData = await dispatch(
        'changePassword',
        this.passwordData
      );
      commit('setLoader', false);

      if (!this.serverErrors.passwordData) {
        this.isAfterChangingPasswordModalShown = true;
        this.passwordData = {
          old_password: null,
          password: null,
          password_confirmation: null
        };
        this.$nextTick(() => {
          this.$validator.reset();
        });
      } else {
        this.$notify({ type: 'error', text: 'Не удалось изменить пароль' });
      }
    },

    async restorePasswordData() {
      const { email } = this.serverData;
      const { commit, dispatch } = this.$store;

      commit('setLoader', true);
      this.serverErrors.restoreData = await dispatch('resetPassword', {
        email
      });
      commit('setLoader', false);

      if (!this.serverErrors.restoreData) {
        this.$notify({
          type: 'success',
          text: 'На почту отправлена инструкция по восстановлению пароля'
        });
      } else {
        this.$notify({
          type: 'error',
          text: 'Отправить инструкцию по восстановлению пароля не удалось'
        });
      }
    },

    revertChanges() {
      this.passwordData = {
        old_password: null,
        password: null,
        password_confirmation: null
      };
      this.$nextTick(() => {
        this.$validator.validateAll('passwordData');
      });
    }
  }
};
</script>

<style lang="scss">
.change-password {
  &__restore,
  &__change,
  &__section {
    > * + * {
      margin-top: 15px;
    }
  }

  &__section {
    margin-top: 30px;
    padding-top: 20px;
    padding-bottom: 20px;
    border-top: 1px solid #d3d8db;
    border-bottom: 1px solid #d3d8db;
  }

  &__buttons {
    margin-top: 20px;

    display: flex;
    align-items: center;

    > * + * {
      margin-left: 20px;
    }
  }

  &__button {
    flex: 1 1 auto;
    max-width: 220px;
  }

  &__restore-text {
    font-weight: bold;
  }
}
</style>
