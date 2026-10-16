<template>
  <modal 
  v-bind="$attrs"
  v-on="$listeners"
  class="admin-role-edit">
    <template #text>
      <div class="modal__text">
        <form-field 
        :errors="getErrors('role_name')"
        label="Имя роли"
        class="admin-role-edit__field"
        isRequired>
          <base-input 
          v-model="roleData.public_name"
          v-validate="'required'"
          :isError="!!getErrors('role_name').length"
          data-vv-as="Имя роли" 
          name="role_name"/>
        </form-field>

        <form-field 
        :errors="getErrors('role_tech_name')"
        label="Техническое имя роли" 
        class="admin-role-edit__field"
        isRequired>
          <base-input 
          v-model="roleData.name"
          v-validate="'required|alpha_lat_dash'"
          :isError="!!getErrors('role_tech_name').length"
          data-vv-as="Техническое имя роли"
          name="role_tech_name"/>
        </form-field>
      </div>
    </template>

    <template #buttons>
      <button 
      v-text="'Сохранить'"
      @click="updateRole"
      class="button"/>
    </template>

  </modal>
</template>
<script>
import { cloneDeep } from 'lodash';

import Modal from '@/js/components/Modal';
import FormField from '@/js/components//FormField';
import BaseInput from '@/js/components//BaseInput';

export default {
  name: 'AdminRoleEdit',
  components: { Modal, FormField, BaseInput },
  inheritAttrs: false,
  props: {
    role: { type: Object, required: true },
    isEdit: { type: Boolean, default: false }
  },

  data() {
    return {
      roleData: cloneDeep(this.role)
    };
  },

  methods: {
    async updateRole() {
      const isFormValid = await this.$validator.validateAll();
      if (!isFormValid) return false;

      const params = {
        role_id: this.roleData.id,
        role_name: this.roleData.public_name,
        role_tech_name: this.roleData.name
      };

      this.$emit('role:update', params);
    },

    getErrors(field) {
      return this.$store.getters.getErrors(field, this.veeErrors);
    }
  }
};
</script>
<style lang="scss">
.admin-role-edit {
  &__field {
    margin-bottom: 20px;
  }
}
</style>

