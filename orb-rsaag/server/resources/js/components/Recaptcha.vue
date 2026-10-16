<template>
  <div class="recaptcha">

    <div class="recaptcha__inner">
      <template>
        На сайте используется защита от спама reCAPTCHA. <br>
        <a class="link" href="https://policies.google.com/privacy">Конфиденциальность</a> -
        <a class="link" href="https://policies.google.com/terms">Условия использования</a>
      </template>

      <div
      v-if="version == 2"
      id='recaptcha'
      class="g-recaptcha"
      :data-sitekey="key"
      data-callback="onCallbackRe"
      data-expired-callback="onCallbackExpired"
      data-error-callback="onCallbackError"
      data-size="invisible"></div>

      <!--<div class="recaptcha__widget" ref="recaptcha" v-else/>-->

        <div v-show="hasErrorLoad" class="recaptcha__error">
            При загрузке поля "Я не робот" возникла ошибка, отключите блокировщики рекламы и перезагрузите страницу.
        </div>
    </div>
  </div>
</template>

<script>
import keys from '../environment';

export default {
  name: 'Recaptcha',
  props: {
    version: { type: [String, Number], required: true }
  },

  data(){
    return{
      hasErrorLoad: false,
    }
  },

  computed: {
    key() {
      return keys[this.version];
    }
  },

  methods: {
    init() {
      return this.version == 2 ? this.renderV2() : this.renderV3();
    },

    renderV2() {
      window.onCallbackRe = this.onCallback;
      window.onCallbackExpired = this.onCallbackExpired;
      window.onCallbackError = this.onCallbackError;
      let script = document.createElement('script');
      script.type = 'text/javascript';
      script.src =
        'https://www.google.com/recaptcha/api.js';
      script.async = true;
      script.defer = true;
      document.head.appendChild(script);

      /*const widget = this.$refs.recaptcha;
      const onSubmitCallback = token => this.$emit('recaptcha:token', token);
      const onExpiredCallback = () => this.$emit('recaptcha:expired');
      const onErrorCallback = () => {
        this.hasErrorLoad = true;
        this.$emit('recaptcha:error')
      };*/

      /*window.onloadCallback = () => {
        grecaptcha.dataCallback('', {
          sitekey: this.key,
          callback: onSubmitCallback,
          'expired-callback': onExpiredCallback,
          'error-callback': onErrorCallback
        });
      };*/
    },

    renderV3() {
      let script = document.createElement('script');
      script.type = 'text/javascript';
      script.src = `https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=${this.key}`;
      script.async = true;
      script.defer = true;
      document.head.appendChild(script);

      window.onloadCallback = async () => {
        const token = await grecaptcha.execute(this.key, {
          action: 'register'
        });
        this.$emit('recaptcha:token', token);
      };
    },

    onCallback(token){
      this.$emit('recaptcha:validate', token);
    },

    onCallbackExpired(){
      this.$emit('recaptcha:expired');
    },

    onCallbackError(error){
      this.$emit('recaptcha:error', error);
      if (error) {
        if (typeof error === 'object' && error.message)
          console.log(error.message)
        else{
          console.log(error)
        }
      }
    }
  },

  mounted() {
    this.init();
  }
};
</script>
<style lang="scss">
@import '~@/sass/variables';

.recaptcha {
  &__inner {
    font-size: 13px;
    color: #666;
  }

  &__widget > div {
    @media (max-width: $break_xs) {
      transform: scale(0.8);
      transform-origin: 0 0;
    }
  }

    &__error {
        color: #ff5555;
    }
}

.grecaptcha-badge {
  display: none;
}
</style>
