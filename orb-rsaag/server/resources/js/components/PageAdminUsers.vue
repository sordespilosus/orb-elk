<template>
  <div class="page-admin-users">
    <h1 class="heading-1 page-admin-users__title">Пользователи</h1>

    <card title="Поиск пользователей" :init-expand="false" :need-expanding="true">
      <form action="">
          <div class="card__content-row">
            <form-field label="Имя">
              <base-input
                      v-model="filterData.first_name"
                      data-vv-scope="filterData"
                      data-vv-as="Имя"
                      name="first_name"
                      placeholder="Имя"/>
            </form-field>

            <form-field label="Фамилия">
              <base-input
                      v-model="filterData.second_name"
                      data-vv-scope="filterData"
                      data-vv-as="Фамилия"
                      name="second_name"
                      placeholder="Фамилия"/>
            </form-field>

            <form-field label="Отчество">
              <base-input
                      v-model="filterData.patroname"
                      data-vv-scope="filterData"
                      data-vv-as="Отчество"
                      name="patroname"
                      placeholder="Отчество"/>
            </form-field>
          </div>

          <div class="card__content-row">
            <form-field label="Идентификатор">
              <base-input
                      v-model="filterData.id"
                      type="number"
                      data-vv-scope="filterData"
                      data-vv-as="Идентификатор"
                      name="id"
                      placeholder="Идентификатор"/>
            </form-field>

            <form-field label="Населенный пункт">
              <field-dropdown
                      v-model="filterData.city"
                      name="city"
                      data-vv-scope="filterData"
                      data-vv-as="Населенный пункт"
                      label-name="area_name"
                      track-by="area_id"
                      value-name="area_id"
                      :data="cities"
                      placeholder="Выберите город"
                      searchable>
                <template #option="{option}">
                  <div v-text="option.area_name"/>
                </template>
              </field-dropdown>
            </form-field>
          </div>

          <div class="card__content-row">

            <form-field label="Заблокирован">
              <field-dropdown
                      v-model="filterData.is_blocked"
                      name="is_blocked"
                      data-vv-scope="filterData"
                      data-vv-as="Заблокирован"
                      label-name="title"
                      value-name="value"
                      :data="[{value: 0, title: 'Нет'}, {value: 1, title: 'Да'}]"
                      placeholder=""
                      searchable>
              </field-dropdown>
            </form-field>
            <form-field label="Подтвержден">
              <field-dropdown
                      v-model="filterData.is_accepted"
                      name="is_accepted"
                      data-vv-scope="filterData"
                      data-vv-as="Подтвержден"
                      label-name="title"
                      value-name="value"
                      :data="[{value: 0, title: 'Нет'}, {value: 1, title: 'Да'}]"
                      placeholder="">
              </field-dropdown>
            </form-field>
          </div>
        <div class="card__content-row">
          <button class="button" type="button" @click="clearFilter">Сбросить</button>
          <button class="button button--positive" type="button" @click="fetchUsers">Фильтровать</button>
        </div>
        </form>
    </card>

    <template v-if="isUsersExist">
    <div>
      <span class="page-admin-users__addition-info">{{countUsersString}}</span>
    </div>

    <base-table
    v-if="users.length" 
    v-model="currentUser"
    v-bind="tableData"
    class="page-admin-users__table">
      <template #td="{data: { cell } }">
        <td class="base-table__td">{{cell.value}}</td>
      </template>   
    </base-table>

    <pagination v-model="pageModel" :size="paginationLength"/>
    </template>
    <div v-else style="align-items: center; justify-content: center; display: flex; margin-top: 40px;">
      <div style="font-size: 24px; font-weight: bolder;">Пользователи с указанными данными не найдены</div>
    </div>

    <admin-actions v-if="currentUser">
      <button
      v-if="currentUser.is_deleted.system_value"
      @click="restoreUser"
      class="button">Восстановить</button>

      <button 
      @click="isModalShown = true" 
      class="button button--neutral" >Редактировать</button>

      <button 
      v-if="currentUser.blocked.system_value"
      @click="setUserBlockStatus(0)" 
      class="button">Разблокировать</button>

      <button 
      v-else
      @click="setUserBlockStatus(1)" 
      class="button button--negative">Блокировать</button>
    </admin-actions>

    <admin-user-edit
    v-if="isModalShown"
    @modal:close="isModalShown = false"
    @user:update="editUserRoles"
    :user="currentUser" 
    :roles="roles"/>
  </div>
</template>
<script>

import { maskPhone, handlers, induce } from '../helpers';
import AdminActions from './AdminActions';
import AdminUserEdit from './AdminUserEdit';

import FormField from '@/js/components/FormField';
import BaseInput from '@/js/components/BaseInput';
import BaseCheckbox from '@/js/components/BaseCheckbox';
import FieldDropdown from '@/js/components/FieldDropdown';
import Card from '@/js/components/Card';
import Pagination from './Pagination';

import BaseTable from './BaseTable';

export default {
  name: 'PageAdminUsers',
  components: { AdminActions, AdminUserEdit, Pagination, BaseTable,
    FormField, BaseInput, BaseCheckbox, FieldDropdown, Card },
  data: () => ({
    queryParams: {
      page: 1,
      per_page: 50,
      order_by: null
    },

    countUsers: 0,
    filterData: {
      id: null,
      first_name: null,
      second_name: null,
      patroname: null,
      city: null,
      is_blocked: null,
      is_accepted: null,
    },
    cities: [
      {
        area_id: 41,
        area_name: "город Пермь",
        area_prnt_id: 0,
        has_children: 0,
        lvl: 1
      }, {
        area_id: 1,
        area_name: "Александровский район",
        area_prnt_id: 0,
        has_children: 1,
        lvl: 1
      },
    ],
    fields: [
      { key: 'id', label: 'ID' },
      { key: 'email', label: 'E-mail', handler: handlers.maskEmail },
      { key: 'phone', label: 'Телефон', handler: maskPhone },
      { key: 'blocked', label: 'Заблокирован', handler: handlers.booleanHandler },
      { key: 'fio', label: 'ФИО' },
      { key: 'email_verified_at', label: 'Подтвержден', handler: handlers.dateHandler },
      { key: 'is_deleted', label: 'Удаленный', handler: handlers.booleanHandler },

      { key: 'surname', label: 'Фамилия' },
      { key: 'name', label: 'Имя' },
      { key: 'patroname', label: 'Отчество' },
      { key: 'birthday', label: 'Дата рождения', handler: handlers.dateHandler },
      { key: 'age_period', label: 'Возраст', handler: handlers.ageHandler },
      { key: 'gender', label: 'Пол', handler: handlers.genderHandler },
      { key: 'esia_user_id', label: 'Профиль ЕСИА' },
      { key: 'vk_user_id', label: 'Профиль ВКонтакте' },
      { key: 'fb_user_id', label: 'Профиль Facebook' },
      { key: 'updated_at', label: 'Дата регистрации', handler: handlers.dateHandler },
      {
        key: 'created_at',
        label: 'Дата последнего изменения',
        handler: handlers.dateHandler
      },
      {
        key: 'is_accepted',
        label: 'Согласен с пользовательским соглашением',
        handler: handlers.booleanHandler
      }
    ],
    users: [],
    roles: [],
    paginationLength: null,
    currentUser: null,

    isModalShown: false
  }),

  computed: {
    countUsersString() {
      return 'Найдено ' + this.countUsers + ' ' + induce(this.countUsers, ['пользователь', 'пользователя', 'пользователей']);
    },

    isUsersExist() {
      return this.countUsers > 0;
    },

    tableData() {
      const header = [
        { label: 'ID', field: 'id' },
        { label: 'Логин', field: 'email' },
        { label: 'ФИО', field: 'fio' },
        { label: 'Заблокированный', field: 'blocked' },
        { label: 'Подтвержденный', field: 'is_accepted' }
      ];

      return { header, body: this.users, hasRadio: true };
    },

    pageModel: {
      get() {
        return this.queryParams.page;
      },

      async set(value) {
        this.queryParams.page = value;
        await this.fetchUsers();
        window.scrollTo(0,0 );
      }
    }
  },

  methods: {
    async init() {
      await this.fetchUsers();
      await this.fetchRoles();
      await this.fetchCities();
    },

    async fetchCities() {
      const { dispatch } = this.$store;
      this.cities = await dispatch( 'getCities' );
    },

    clearFilter() {
      let isNeedFetch = false;

      let values = _.values(this.filterData);
      for (let key in values) {
        if (values[key] != null) {
          isNeedFetch = true;
          break;
        }
      }

      this.filterData.city = null;
      this.filterData.first_name = null;
      this.filterData.second_name = null;
      this.filterData.patroname = null;
      this.filterData.id = null;
      this.filterData.is_accepted = null;
      this.filterData.is_blocked = null;

      if (isNeedFetch)
        this.fetchUsers();
    },

    prepareUser(user) {
      return _.reduceRight(this.fields, function (result, field) {
        const key = field.key;
        if (key in user) {
          const system_value = user[key];
          const value = field.handler ? field.handler(system_value) : system_value;
          result[key] = {...field, system_value, value};
        }

        return result;
      }, {});
    },

    async fetchUsers() {
      const { commit, dispatch } = this.$store;
      commit('setLoader', true);

      const { users, countUsers } = await dispatch(
        'fetchUsers',
          Object.assign(this.queryParams,this.filterData),
      );

      this.users = users.map(this.prepareUser);
      this.countUsers = countUsers;
      this.currentUser = this.users[0];

      this.paginationLength = Math.ceil(countUsers / this.queryParams.per_page);
      commit('setLoader', false);
    },

    async fetchRoles() {
      const { dispatch } = this.$store;
      this.roles = await dispatch('fetchRoles');
    },

    async editUserRoles(params) {
      const { commit, dispatch } = this.$store;

      commit('setLoader', true);
      const isSuccess = await dispatch('editUserRoles', params);
      commit('setLoader', false);

      if (isSuccess) {
        this.isModalShown = false;
        this.$notify({
          type: 'success',
          text: 'Данные о пользователе успешно обновлены!'
        });
      } else {
        this.$notify({
          type: 'error',
          text: 'Не удалось обновить данные пользователя!'
        });
      }
    },

    async setUserBlockStatus(value) {
      const { commit, dispatch } = this.$store;
      const user_id = this.currentUser.id.system_value;

      commit('setLoader', true);
      const isSuccess = await dispatch('blockUser', { user_id, value });

      if (isSuccess) {
        await this.fetchUsers();
        this.$notify({
          type: 'success',
          text: 'Данные блокировки пользователя успешно обновлены!'
        });
      } else {
        this.$notify({
          type: 'error',
          text: 'Не удалось обновить данные блокировки пользователя!'
        });
      }

      commit('setLoader', false);
    },

    async restoreUser() {
      const { commit, dispatch } = this.$store;
      const id = this.currentUser.id.system_value;

      commit('setLoader', true);
      const isSuccess = await dispatch('restoreUser', id);
      if (isSuccess) {
        await this.fetchUsers();
        this.$notify({
          type: 'success',
          text: 'Пользователь успешно восстановлен!'
        });
      } else {
        this.$notify({
          type: 'error',
          text: 'Не удалось обновить данные блокировки пользователя!'
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
.page-admin-users {
  &__title {
    margin-bottom: 20px;
  }

  &__addition-info {
    color: #7b898d;
    font-weight: 400;
  }

  &__table {
    margin-bottom: 20px;
  }

  .card__content-row button {
    margin: 0 10px;
  }
}
</style>
