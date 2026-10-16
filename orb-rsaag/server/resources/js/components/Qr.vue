<template>
  <div class="qr">
    <div class="qr__inner">
      <div 
      v-show="isGenerated" 
      class="container-spinner qr__spinner" />

      <vue-qr 
      v-show="!isGenerated" 
      v-bind="qrParams" 
      class="qr__image" 
      alt="QR-код"/>
    </div>
  </div>
</template>

<script>
import VueQr from 'vue-qr';
import qrImage from '@/images/rs2.png';

export default {
  name: 'Qr',
  components: { VueQr },
  props: {
    text: { type: String, required: true }
  },
  data() {
    return {
      isGenerated: true
    };
  },
  computed: {
    qrParams() {
      return {
        text: this.text,
        dotScale: 1,
        callback: this.successGenerateQr,
        size: 400,
        logoSrc: qrImage,
        logoBackgroundColor: '#fff',
        logoMargin: 20,
        logoScale: 1,
        logoCornerRadius: 25,
        margin: 15,
        correctLevel: 3,
        colorDark: '#2A3E4C'
      };
    }
  },
  methods: {
    successGenerateQr() {
      this.isGenerated = false;
    }
  }
};
</script>

<style lang="scss">
@import '~@/sass/variables';
.qr {
  &__inner {
    display: flex;
    flex-direction: column;
    align-items: center;
  }

  &__spinner {
    flex: 0 0 auto;
  }

  &__image {
    flex: 0 0 auto;
    max-width: 100%;
    height: auto;
  }
}
</style>