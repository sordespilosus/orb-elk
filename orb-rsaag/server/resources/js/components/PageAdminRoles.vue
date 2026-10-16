<template>
  <div class="page-admin-roles">
        
    <admin-actions>
      <button @click="isCreateModalShown = true" class="button">Создать</button>
      <button @click="isEditModalShown = true" class="button button--neutral">Редактировать</button>
      <button @click="deleteRole(currentRole.id)" class="button button--negative">Удалить</button>
    </admin-actions>

    <h2 class="heading-2 page-admin-roles__title">Роли</h2>
    <base-table 
    v-if="this.roles.length" 
    v-model="currentRole"
    v-bind="tableData"
    class="page-admin-roles__table"/>

    <admin-role-edit
    v-if="isCreateModalShown"
    @modal:close="isCreateModalShown = false"
    @role:update="createRole"
    :role="emptyRole"
    title="Создание роли"/>

    <admin-role-edit
    v-if="isEditModalShown"
    @modal:close="isEditModalShown = false"
    @role:update="editRole"
    :role="currentRole"
    title="Редактирование роли"/>

  </div>
</template>
<script>
import AdminActions from './AdminActions';
import AdminRoleEdit from './AdminRoleEdit';
import BaseTable from './BaseTable';

export default {
  name: 'PageAdminRoles',
  components: { AdminRoleEdit, BaseTable, AdminActions },
  data: () => ({
    roles: [],
    currentRole: null,
    emptyRole: { name: '', public_name: '' },
    isCreateModalShown: false,
    isEditModalShown: false
  }),

  computed: {
    tableData() {
      const header = [
        { label: 'ID', field: 'id' },
        { label: 'Наименование', field: 'public_name' },
        { label: 'Системное имя', field: 'name' }
      ];

      return { header, body: this.roles, hasRadio: true };
    }
  },

  methods: {
    async init() {
      const { commit } = this.$store;
      commit('setLoader', true);
      await this.fetchRoles();
      commit('setLoader', false);
    },

    async fetchRoles() {
      const { dispatch } = this.$store;
      this.roles = await dispatch('fetchRoles');
      this.currentRole = this.roles[0];
    },

    async editRole(params) {
      const { commit, dispatch } = this.$store;

      commit('setLoader', true);
      const isSuccess = await dispatch('editRole', params);

      if (isSuccess) {
        this.isEditModalShown = false;
        await this.fetchRoles();
        this.$notify({
          type: 'success',
          text: 'Роль успешно отредактирована!'
        });
      } else {
        this.$notify({
          type: 'error',
          text: 'Отредактировать роль не удалось!'
        });
      }
      commit('setLoader', false);
    },

    async createRole(params) {
      const { commit, dispatch } = this.$store;

      commit('setLoader', true);
      const isSuccess = await dispatch('createRole', params);

      if (isSuccess) {
        this.isCreateModalShown = false;
        await this.fetchRoles();
        this.$notify({ type: 'success', text: 'Роль успешно создана!' });
      } else {
        this.$notify({ type: 'error', text: 'Создать роль не удалось!' });
      }
      commit('setLoader', false);
    },

    async deleteRole(id) {
      const { commit, dispatch } = this.$store;

      commit('setLoader', true);
      const isSuccess = await dispatch('deleteRole', id);

      if (isSuccess) {
        await this.fetchRoles();
        this.$notify({ type: 'success', text: 'Роль успешна удалена!' });
      } else {
        this.$notify({ type: 'success', text: 'Удалить роль не удалось!' });
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
.page-admin-roles {
  &__title {
    margin-bottom: 20px;
  }

  &__table {
    margin-bottom: 20px;
  }

  &__buttons {
    > * + * {
      margin-top: 10px;
    }
  }
}
</style>
