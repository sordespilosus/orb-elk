<template>
  <div class="page-phone-verification">
    <h2 class="heading-2 page-phone-verification__title">Введите номер телефона</h2>

    <form @submit.prevent="sendForm" action="/register" method="POST" class="page-phone-verification__form">

      <input type="hidden" name="_token" :value="$store.getters.csrf"/>
      <input type="hidden" name="sms_hash" v-validate="'required'" :value="formData.sms_hash"/>

      <form-field 
      label="Номер телефона"
      :errors="veeErrors.collect('phone')"
      class="page-phone-verification__field">
        <template>
          <base-input
          v-model="formData.phone"
          v-validate="'required|phone'"
          :mask="'+7 (999) 999-99-99'"
          :isError="veeErrors.has('phone')"
          :readonly="!!formData.sms_hash"
          data-vv-as="Номер телефона"
          name="phone"
          placeholder="+7 (___) ___-__-__"/>
          
          <button 
          v-show="isPhoneVerificationButtonShown"
          @click="isPhoneVerificationShown = true"
          type="button"
          class="button button--negative">Необходимо подтверждение телефона</button>
        </template>
      </form-field>

      <phone-verification 
      v-if="isPhoneVerificationShown"
      :phone="formData.phone"
      @verification:error="isPhoneVerificationShown = false"
      @verification:success="handlePhoneVerification($event)"
      class="page-phone-verification__field"/>

      <div class="page-phone-verification__buttons">
        <button class="button page-phone-verification__button">Зарегистрироваться</button>
      </div>
    </form>
  </div>
</template>

<script>
import FormField from './FormField';
import BaseInput from './BaseInput';

import PhoneVerification from './PhoneVerification';

export default {
  name: 'PagePhoneVerification',
  components: { FormField, BaseInput, PhoneVerification },

  data: () => ({
    formData: {
      phone: null,
      smsHash: null
    },

    isPhoneVerificationShown: false
  }),

  computed: {
    isPhoneVerificationButtonShown() {
      const hasPhoneData = !!this.formData.phone,
        hasErrors = this.veeErrors.has('phone'),
        isVerified = !!this.formData.sms_hash;

      return (
        hasPhoneData &&
        !hasErrors &&
        !isVerified &&
        !this.isPhoneVerificationShown
      );
    }
  },

  methods: {
    handlePhoneVerification(sms_hash) {
      this.formData.sms_hash = sms_hash;
      this.isPhoneVerificationShown = false;
    },

    async sendForm(event) {
      const form = event.target;
      const hasNoErrors = await this.$validator.validateAll();
      if (hasNoErrors) form.submit();
    }
  }
};
</script>
<style lang="scss">
.page-phone-verification {
  max-width: 520px;
  margin: 0 auto;

  &__title {
    text-align: center;
    margin-bottom: 20px;
  }

  &__field {
    margin-bottom: 20px;
  }
}
</style>
