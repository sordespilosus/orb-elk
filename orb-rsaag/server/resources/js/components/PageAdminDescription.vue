<template>
  <div class="page-admin-description">
    <admin-actions>
      <button 
      @click="editDescription"
      class="button">Сохранить</button>
    </admin-actions>

    <h2 class="heading-2 page-admin-description__title">Описание системы</h2>
    <base-editor v-model="description"/>
  </div>
</template>
<script>
import AdminActions from './AdminActions';
import BaseEditor from './BaseEditor';

export default {
  name: 'PageAdminDescription',
  components: { AdminActions, BaseEditor },
  data: () => ({
    description: ''
  }),

  methods: {
    async init() {
      const { commit, dispatch } = this.$store;
      commit('setLoader', true);
      await this.fetchDescription();
      commit('setLoader', false);
    },

    async fetchDescription() {
      const { dispatch } = this.$store;
      this.description = await dispatch('fetchDescription');
    },

    async editDescription() {
      const { commit, dispatch } = this.$store;
      const description = this.description;

      commit('setLoader', true);
      const isSuccess = await dispatch('editDescription', { description });
      if (isSuccess) {
        await this.fetchDescription();
        this.$notify({ type: 'success', text: 'Описание изменено!' });
      } else {
        this.$notify({ type: 'error', text: 'Изменить описание не удалось!' });
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
.page-admin-description {
  &__title {
    margin-bottom: 20px;
  }
}
</style>