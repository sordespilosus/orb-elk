<template>
  <div class="page-admin-agreement">
    <admin-actions>
      <button 
      @click="editAgreement"
      :disabled="!agreement"
      class="button">Сохранить</button>
    </admin-actions>

    <h2 class="heading-2 page-admin-agreement__title">Пользовательское соглашение</h2>
    <base-editor v-model="agreement"/>
  </div>
</template>
<script>
import AdminActions from './AdminActions';
import BaseEditor from './BaseEditor';

export default {
  name: 'PageAdminAgreement',
  components: { AdminActions, BaseEditor },
  data: () => ({
    agreement: ''
  }),

  methods: {
    async init() {
      const { commit, dispatch } = this.$store;
      commit('setLoader', true);
      await this.fetchAgreement();
      commit('setLoader', false);
    },

    async fetchAgreement() {
      const { dispatch } = this.$store;
      this.agreement = await dispatch('fetchAgreement');
    },

    async editAgreement() {
      const { commit, dispatch } = this.$store;
      const text_agreement = this.agreement;

      commit('setLoader', true);
      const isSuccess = await dispatch('editAgreement', { text_agreement });
      if (isSuccess) {
        await this.fetchAgreement();
        this.$notify({
          type: 'success',
          text: 'Пользовательское соглашение изменено!'
        });
      } else {
        this.$notify({
          type: 'error',
          text: 'Изменить пользовательское соглашение не удалось!'
        });
      }
      commit('setLoader', false);
    }
  },

  created() {
    this.init();
  }
};
</script>
<style lang="scss">
.page-admin-agreement {
  &__title {
    margin-bottom: 20px;
  }
}
</style>