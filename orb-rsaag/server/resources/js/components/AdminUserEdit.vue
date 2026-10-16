<template>
  <modal
  v-on="$listeners"
  v-bind="$attrs"
  class="admin-user-edit"
  title="Редактирование пользователя">
    <template #text>
      <div class="modal__text">

        <base-accordion :startShown="true" class="admin-user-edit__accordion">
          <template #title>Основная информация</template>
          <base-list 
          :items="userData.main"
          label="label"
          value="value"
          class="admin-user-edit__list">
            <template #value="{ item: {key, value, system_value} }">
              <div class="base-list__value" v-if="key === 'email'">
                {{isEmailShown ? system_value : value}}

                <span 
                @click="isEmailShown = !isEmailShown"
                class="link admin-user-edit__info-toggle">
                  [ {{isEmailShown ? 'Скрыть' : 'Показать'}} ]
                </span>
                
              </div>

              <div class="base-list__value" v-else-if="key === 'phone'">
                {{isPhoneShown ? system_value : value}}

                <span 
                @click="isPhoneShown = !isPhoneShown"
                class="link admin-user-edit__info-toggle">
                  [ {{isPhoneShown ? 'Скрыть' : 'Показать'}} ]
                </span>
                
              </div>

              <div v-else class="base-list__value">{{value}}</div>
            </template>
          </base-list>
        
        </base-accordion>

        <base-accordion :startShown="true" class="admin-user-edit__accordion">
          <template #title>Роли пользователя</template>
            <checkbox-group
            v-model="userRoles"
            :items="roles.map(role => ({label: role.public_name, value: role.id}))"
            direction="column"
            name="user_roles"
            class="admin-user-edit__roles"/>
        </base-accordion>

        <base-accordion class="admin-user-edit__accordion">
          <template #title>Показать дополнительную информацию</template>
          <base-list 
          :items="userData.additional"
          label="label"
          value="value"/>
        </base-accordion>

      </div>
    </template>

    <template #buttons>
      <button 
      v-text="'Сохранить'"
      @click="updateUser"
      type="button" 
      class="button"/>
    </template>
  </div>
  </modal>
</template>
<script>
import { groupBy } from 'lodash';

import BaseList from '@/js/components/BaseList';
import BaseAccordion from '@/js/components/BaseAccordion';
import Modal from '@/js/components/Modal';
import CheckboxGroup from '@/js/components/CheckboxGroup';

const withAccordionData = user => {
  const mainFields = [
    'id',
    'email',
    'phone',
    'blocked',
    'email_verified_at',
    'fio',
    'is_deleted'
  ];

  return groupBy(Object.values(user), ({ key }) =>
    mainFields.includes(key) ? 'main' : 'additional'
  );
};

export default {
  name: 'AdminUserEdit',
  components: { BaseList, BaseAccordion, CheckboxGroup, Modal },
  inheritAttrs: false,
  props: {
    user: { type: Object, required: true },
    roles: { type: Array, required: true }
  },

  data() {
    return {
      userData: withAccordionData(this.user),

      userRoles: null,

      isPhoneShown: false,
      isEmailShown: false
    };
  },

  methods: {
    async init() {
      const { commit, dispatch } = this.$store;
      const id = this.user.id.system_value;
      commit('setLoader', true);
      const userRoles = await dispatch('fetchUserRoles', id);
      this.userRoles = userRoles.map(role => role.id);
      commit('setLoader', false);
    },

    updateUser() {
      const id = this.user.id.system_value;
      this.$emit('user:update', { id, roles: this.userRoles });
    }
  },

  created() {
    this.init();
  }
};
</script>
<style lang="scss">
.admin-user-edit {
  &__accordion {
    & + & {
      border-top: 1px solid #fff;
    }
  }

  &__info-toggle {
    font-size: 11px;
    font-weight: bold;
    text-transform: uppercase;
  }

  &__roles {
    padding: 10px;
  }
}
</style>