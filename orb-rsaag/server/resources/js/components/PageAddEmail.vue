<template>
  <div class="page-add-email">
    <h2 class="heading-2 page-add-email__title">Необходимо прикрепить e-mail</h2>
      <div class="page-add-email__content">
          <p>После прикрепления e-mail адреса Вы не сможете войти в свой профиль, пока не подтвердите новый e-mail адрес</p>
      </div>

    <form @submit.prevent="sendForm" action="/change_email" method="POST" class="page-add-email__form">
        <input type="hidden" name="_token" :value="$store.getters.csrf"/>
        <form-field
            :errors="getErrors('email')"
            label="E-mail"
            class="page-add-email__field"
            isRequired>
            <base-input
                v-model="email"
                v-validate="'required|email'"
                :isError="!!getErrors('email').length"
                data-vv-scope="changeEmail"
                data-vv-as="e-mail"
                name="email"
                placeholder="E-mail"/>
        </form-field>

      <div class="page-add-email__buttons">
        <button class="button page-add-email__button">Прикрепить</button>
      </div>
    </form>
  </div>
</template>

<script>
import FormField from './FormField';
import BaseInput from './BaseInput';

export default {
  name: 'PagePhoneVerification',
  components: { FormField, BaseInput },
  props: {
    oldEmail: { type: String },
    serverErrors: { type: Object }
  },

  data() {
    return {
      email: this.oldEmail
    };
  },

  computed: {},

  methods: {
    getErrors(field) {
      return this.$store.getters.getErrors(
        field,
        this.veeErrors,
        this.serverErrors
      );
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
.page-add-email {
  max-width: 520px;
  margin: 0 auto;

  &__content {
    margin-bottom: 20px;
  }

  &__title {
    text-align: center;
    margin-bottom: 20px;
  }

  &__field {
    margin-bottom: 20px;
  }
}
</style>
