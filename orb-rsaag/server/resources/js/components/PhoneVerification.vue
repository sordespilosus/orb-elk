<template>
  <transition name="vue-dropdown-fade">
    <div class="phone-verification">
      <button 
      :disabled="smsInterval"
      @click="verifyPhone"
      type="button"
      class="button phone-verification__button">{{buttonText}}</button>
      <form-field
      v-if="shownVerify"
      label="Код подтверждения"
      :errors="veeErrors.collect('sms_code')"
      class="phone-verification__field">
        <div class="phone-verification__container">
          <base-input
          v-model="formData.sms_code"
          v-validate="'required|length_numbers:4'"
          :mask="'9999'"
          :isError="veeErrors.has('sms_code')"
          data-vv-as="Код подтверждения"
          name="sms_code"
          class="phone-verification__input"
          placeholder="Код подтверждения"/>

          <button
          class="button phone-verification__button-verify"
          type="button"
          v-text="'Подтвердить'"
          @click="verifySmsCode" />
        </div>
      </form-field>


    </div>

  </transition>
</template>
<script>
import FormField from './FormField';
import BaseInput from './BaseInput';

export default {
  name: 'PhoneVerification',
  components: { FormField, BaseInput },
  props: {
    phone: { type: String, required: true },
    isRegisterPage: { type: Boolean, default: true }
  },

  data: () => ({
    formData: {
      sms_code_id: null,
      sms_code: null
    },

    smsInterval: null,
    buttonText: 'Отправить СМС еще раз',
    shownVerify: false,
  }),

  methods: {
    async init() {
      await this.verifyPhone();
    },

    async verifyPhone() {
      const { commit, dispatch } = this.$store;
      const actionName = this.isRegisterPage
        ? 'verifyRegisterPhone'
        : 'verifyProfilePhone';

      commit('setLoader', true);
      const response = await dispatch(actionName, { phone: this.phone });
      commit('setLoader', false);

      if (response.status) {
        this.shownVerify = true;
        this.$notify({type: 'success', text: 'На Ваш номер отправлено сообщение. Введите 4-значный код в поле ниже'})
        this.formData.sms_code_id = response.data.id;
        this.setSmsInterval();
      } else {
        this.shownVerify = false;
        this.$notify({type: 'error', text: 'Отправить СМС не удалось'})
        /*const errors = Object.values(response.errors).reduce(
          (total, field) => total.concat(field),
          []
        );
        errors.forEach(error => this.$notify({ type: 'error', text: error }));*/
        this.$emit('verification:error', response.errors);
      }
    },

    async verifySmsCode() {
      const isFormValid = await this.$validator.validateAll();
      if (!isFormValid) {
        this.$notify({type: 'error', text: 'Введите СМС код'})
        return;
      }
      const { commit, dispatch } = this.$store;

      const params = {
        sms_code: this.formData.sms_code,
        verify_sms_code_id: this.formData.sms_code_id
      };

      const actionName = this.isRegisterPage
        ? 'verifyRegisterSmsCode'
        : 'verifyProfileSmsCode';

      commit('setLoader', true);
      const response = await dispatch(actionName, params);
      commit('setLoader', false);

      if (response.status) {
        this.$notify({
          type: 'success',
          text: 'Номер телефона успешно подтвержден!'
        });
        this.clearSmsInterval();
        this.$emit('verification:success', response.data);
      } else {
        const errors = Object.values(response.errors).reduce(
          (total, field) => total.concat(field),
          []
        );
        errors.forEach(error => this.$notify({ type: 'error', text: error }));
      }
    },

    setSmsInterval() {
      if (this.smsInterval) this.clearSmsInterval();

      let time = 60;
      const intervalFunction = () => {
        if (time == 0) {
          this.clearSmsInterval();
          this.buttonText = 'Отправить СМС еще раз';
        } else {
          this.buttonText = `Отправить еще раз через ${time} с.`;
          time--;
        }
      };

      this.smsInterval = setInterval(() => intervalFunction(), 1000);
    },

    clearSmsInterval() {
      clearInterval(this.smsInterval);
      this.smsInterval = null;
    }
  },

  created() {
    this.init();
    this.$once('hook:destroy', this.clearSmsInterval);
  }
};
</script>
<style lang="scss">
.phone-verification {
  > * {
    margin-bottom: 20px;
  }

  &__container {
    display: flex;
  }

  &__input {
    flex: 1 1 auto;
  }

  &__button-verify {
    margin-left: 10px;
    flex: 0 1 150px;
  }
}
</style>


