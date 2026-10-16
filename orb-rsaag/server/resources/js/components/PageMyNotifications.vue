<template>
  <div class="container page-my-notifications">
    <div class="page-my-notifications__header">
      <h1
      v-text="'Мои заявления'"
      class="heading-1 page-my-notifications__title"/>

      <field-dropdown 
      v-if="data.organizations && data.organizations.length > 0"
      @input="setRoute({ page: 1, org_id: $event })"
      :data="data.organizations"
      :value="query.org_id"
      label-name="short_name"
      value-name="id"
      placeholder="Организации"
      class="page-my-notifications__dropdown"/>
    </div>

    <div
    v-if="data.notifications"
    class="page-my-notifications__scroll">

      <div
      class="page-my-notifications__items-header">
        <base-popover
        :popover-options="{ arrow: false, hasSameWidth: true }">
          <template #trigger>
            <div class="page-my-notifications__filter">
              <icon
              :dir="query.is_asc ? 'down' : 'up'"
              name="dropdown"
              class="page-my-notifications__filter-sort"/>
              <div
              v-text="`Номер: ${activeSortType}`"
              class="page-my-notifications__filter-text"/>
            </div>
          </template>
          <template #default="{ data: { hide } }">
            <div class="page-my-notifications__filter-list">
              <div
              v-for="item in data.sortTypes"
              v-text="item.name"
              @click="setRoute({is_asc: !query.is_asc}, hide)"
              class="page-my-notifications__filter-item"
              :class="{'page-my-notifications__filter-item--active': item.is_asc === query.is_asc}"/>
            </div>
          </template>
        </base-popover>

        <base-popover
        :popover-options="{ arrow: false, hasSameWidth: true }">
          <template #trigger>
            <div class="page-my-notifications__filter">
              <icon
              name="filter"
              class="page-my-notifications__filter-icon"/>
              <div
              v-text="`Наименование услуги: ${activeService}`"
              class="page-my-notifications__filter-text"/>
            </div>
          </template>
          <template #default="{ data: { hide } }">
            <div class="page-my-notifications__filter-list">
              <div
              v-for="item in data.services"
              v-text="item.name"
              @click="setRoute({service: item.number}, hide)"
              class="page-my-notifications__filter-item"
              :class="{'page-my-notifications__filter-item--active': item.number === query.service}"/>
            </div>
          </template>
        </base-popover>

        <base-popover
        :popover-options="{ arrow: false, hasSameWidth: true }">
          <template #trigger>
            <div class="page-my-notifications__filter">
              <icon
              name="filter"
              class="page-my-notifications__filter-icon"/>
              <div
              v-text="`Статус: ${activeStatus}`"
              class="page-my-notifications__filter-text"/>
            </div>
          </template>
          <template #default="{ data: { hide } }">
            <div class="page-my-notifications__filter-list">
              <div
              v-for="item in data.statuses"
              v-text="item.name"
              @click="setRoute({status: item.number}, hide)"
              class="page-my-notifications__filter-item"
              :class="{'page-my-notifications__filter-item--active': item.number === query.status}"/>
            </div>
          </template>
        </base-popover>
      </div>

      <div
      class="page-my-notifications__items">
        <div
        v-for="notify in data.notifications"
        :key="notify.service_number"
        @click="fetchNotification(notify.service_number)"
        class="page-my-notifications__item">
          <div class="page-my-notifications__item-attr">
            <div
            v-text="'Номер'"
            class="page-my-notifications__item-attr-header"/>
            <div
            v-text="notify.number"
            class="page-my-notifications__text"/>
            <div
            v-text="notify.reg_service_date"
            class="page-my-notifications__additional"/>
          </div>

          <div class="page-my-notifications__item-attr">
            <div
            v-text="'Наименование услуги'"
            class="page-my-notifications__item-attr-header"/>
            <div
            v-text="notify.service_name"
            class="page-my-notifications__text"/>
            <div
            v-if="notify.summary"
            v-text="notify.summary"
            class="page-my-notifications__additional"/>
          </div>

          <div class="page-my-notifications__item-attr">
            <div
            v-text="'Статус'"
            class="page-my-notifications__item-attr-header"/>
            <div
            v-text="notify.status_name"
            class="page-my-notifications__text"
            :class="{
              'page-my-notifications__text--positive': notify.status_id == 1075,
              'page-my-notifications__text--negative': notify.status_id == 1080,
            }"/>
            <div
            v-text="notify.status_date"
            class="page-my-notifications__additional"/>
          </div>
        </div>
      </div>

      <base-table
      @table:select-row="fetchNotification($event.service_number)"
      :header="tableHeader"
      :body="data.notifications"
      class="page-my-notifications__table">
        <template #th="{ data: { cell } }">
          <th class="base-table__th">
            <!--
            <div 
            v-if="cell.field === 'number'"
            class="page-my-notifications__filter">
              <icon
              @click="setRoute({is_asc: !query.is_asc})"
              :dir="query.is_asc ? 'down' : 'up'"
              name="dropdown"
              class="page-my-notifications__filter-sort"/>
              <div 
              v-text="cell.label"
              class="page-my-notifications__filter-text"/>
            </div>-->

            <base-popover
            v-if="cell.field === 'number'"
            :popover-options="{ arrow: false, hasSameWidth: true }">
              <template #trigger>
                <div class="page-my-notifications__filter">
                  <icon
                  :dir="query.is_asc ? 'down' : 'up'"
                  name="dropdown"
                  class="page-my-notifications__filter-sort"/>
                  <div
                  v-text="`${cell.label}: ${activeSortType}`"
                  class="page-my-notifications__filter-text"/>
                </div>
              </template>
              <template #default="{ data: { hide } }">
                <div class="page-my-notifications__filter-list">
                  <div
                  v-for="item in data.sortTypes"
                  v-text="item.name"
                  @click="setRoute({is_asc: !query.is_asc}, hide)"
                  class="page-my-notifications__filter-item"
                  :class="{'page-my-notifications__filter-item--active': item.is_asc === query.is_asc}"/>
                </div>
              </template>
            </base-popover>

            <base-popover 
            v-if="cell.field === 'service_name'"
            :popover-options="{ arrow: false, hasSameWidth: true }">
              <template #trigger>
                <div class="page-my-notifications__filter">
                  <icon 
                  name="filter"
                  class="page-my-notifications__filter-icon"/>
                  <div 
                  v-text="`${cell.label}: ${activeService}`"
                  class="page-my-notifications__filter-text"/>
                </div>
              </template>
              <template #default="{ data: { hide } }">
                <div class="page-my-notifications__filter-list">
                  <div
                  v-for="item in data.services"
                  v-text="item.name"
                  @click="setRoute({service: item.number}, hide)"
                  class="page-my-notifications__filter-item"
                  :class="{'page-my-notifications__filter-item--active': item.number === query.service}"/>
                </div>  
              </template>          
            </base-popover>

            <base-popover 
            v-if="cell.field === 'status_name'"
            :popover-options="{ arrow: false, hasSameWidth: true }">
              <template #trigger>
                <div class="page-my-notifications__filter">
                  <icon 
                  name="filter"
                  class="page-my-notifications__filter-icon"/>
                  <div 
                  v-text="`${cell.label}: ${activeStatus}`"
                  class="page-my-notifications__filter-text"/>
                </div>
              </template>
              <template #default="{ data: { hide } }">
                <div class="page-my-notifications__filter-list">
                  <div
                  v-for="item in data.statuses"
                  v-text="item.name"
                  @click="setRoute({status: item.number}, hide)"
                  class="page-my-notifications__filter-item"
                  :class="{'page-my-notifications__filter-item--active': item.number === query.status}"/>
                </div>
              </template>      
            </base-popover>
          </th>
        </template>
        <template #td="{ data: { cell, row, key } }">
          <td 
          class="base-table__td" 
          :aria-label="tableHeader[key].label ? tableHeader[key].label : ''">
            <div
            v-text="cell"
            class="page-my-notifications__text"
            :class="{ 
              'page-my-notifications__text--positive': key == '2' && row.status_id == 1075,
              'page-my-notifications__text--negative': key == '2' && row.status_id == 1080,
            }"/>

            <div 
            v-if="key == '0'"
            v-text="row.reg_service_date"
            class="page-my-notifications__additional"/>

            <div
            v-else-if="key == '1' && row.summary"
            v-text="row.summary"
            class="page-my-notifications__additional"/>

            <div
            v-else-if="key == '2'"
            v-text="row.status_date"
            class="page-my-notifications__additional"/>
          </td>
        </template>
      </base-table>
    </div>

    <notification-modal
    v-if="isModalShown"
    @modal:close="isModalShown = false"
    @modal:refresh="fetchNotification"
    max-width="620"
    :data="data.detalization"/>
      
  </div>
</template>

<script>
import { noop, cloneDeep, pickBy, get, find } from 'lodash';

import BaseTable from '@/js/components/BaseTable';
import BasePopover from '@/js/components/BasePopover';
import FieldDropdown from '@/js/components/FieldDropdown';
import NotificationModal from '@/js/components/NotificationModal';

export default {
  name: 'PageMyNotifications',
  components: { BaseTable, BasePopover, FieldDropdown, NotificationModal },

  data: () => ({
    data: {},
    isModalShown: false,
    tableHeader: [
      { label: 'Номер', field: 'number' },
      { label: 'Наименование услуги', field: 'service_name' },
      { label: 'Статус', field: 'status_name' }
    ],

    defaultQuery: {
      page: 1,
      status: null,
      service: null,
      org_id: null,
      order_by: 'show_number',
      is_asc: false
    },
    isOrgSelected: false
  }),

  computed: {
    query() {
      return { ...this.defaultQuery, ...this.$route.query };
    },

    activeService() {
      const number = this.query.service;
      return get(find(this.data.services, { number }), 'name');
    },

    activeStatus() {
      const number = this.query.status;
      return get(find(this.data.statuses, { number }), 'name');
    },

    activeSortType() {
      const is_asc = this.query.is_asc;
      return get(find(this.data.sortTypes, { is_asc }), 'name');
    }
  },

  methods: {
    setRoute(query, hide) {
      if (hide) hide();

      if (query.hasOwnProperty('org_id')) this.isOrgSelected = true;

      const { name, params } = this.$route;
      query = this.filterURLQuery({ ...this.query, ...query });
      this.$router.push({ name, query, params }, noop);
    },

    filterURLQuery(query) {
      return pickBy(query, (value, key) => this.defaultQuery[key] !== value);
    },

    async fetchNotifications() {
      const { commit, dispatch } = this.$store;

      commit('setLoader', true);

      const response = await dispatch('fetchNotifications', this.query);

      if (response.status) {
        const { data } = response,
          { service, status } = this.query,
          hasSameService =
            service && this.isOrgSelected
              ? find(data.services, { number: service })
              : undefined,
          hasSameStatus =
            status && this.isOrgSelected
              ? find(data.statuses, { number: status })
              : undefined;

        /* Проверка на присутствие выбранной в фильтре услуги\статуса при выборе другой организации
         * Если выбрана новая организация, в которой нет выбранного в текущем фильтре услуги\статуса
         * То сбросить фильтр */
        if (this.isOrgSelected && !hasSameService && service) {
          this.setRoute({ service: null });
        } else if (this.isOrgSelected && !hasSameStatus && status) {
          this.setRoute({ status: null });
        }
        this.isOrgSelected = false;

        data.services = this.withEmptyItem('services', data.services);
        data.statuses = this.withEmptyItem('statuses', data.statuses);
        data.sortTypes = [
          { is_asc: false, name: 'По убыванию' },
          { is_asc: true, name: 'По возрастанию' }
        ];
        this.data = data;
      } else {
        this.$notify({ type: 'error', text: 'Получить заявления не удалось' });
      }

      commit('setLoader', false);
    },

    async fetchNotification(service_number) {
      const { commit, dispatch } = this.$store;

      commit('setLoader', true);
      const response = await dispatch('fetchNotification', { service_number });
      commit('setLoader', false);

      if (response.status) {
        this.data.detalization = { ...response.data, service_number };
        this.isModalShown = true;
      } else {
        this.$notify({
          type: 'error',
          text: 'Получить уведомление не удалось'
        });
      }
    },

    withEmptyItem(name, source) {
      return [{ name: 'Все', number: null }, ...source];
    },
  },

  watch: {
    query: { handler: 'fetchNotifications', immediate: true }
  }
};
</script>

<style lang="scss">
@import '~@/sass/variables';

.page-my-notifications {
  > * + * {
    margin-top: 20px;

    @media (max-width: $break_md) {
      margin-top: 10px;
    }
  }

  &__header {
    display: flex;
    align-items: center;
    justify-content: flex-start;

    > * + * {
      margin-left: 20px;
    }

    @media (max-width: $break_md) {
      flex-direction: column;
      align-items: flex-start;
      justify-content: flex-start;

      > * + * {
        margin-left: 0;
        margin-top: 10px;
      }
    }
  }

  &__title {
    flex: 0 0 auto;
  }

  &__dropdown {
    flex: 1 1 auto;
    max-width: 300px;
    @media screen and (max-width: $break_md) {
      max-width: none;
      width: 100%;
    }

    .multiselect__single {
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
  }

  &__scroll {
    overflow-x: auto;
  }

  &__table {
    /*min-width: 700px;*/

    @media screen and (max-width: $break_md) {
      display: none;
    }

    .base-table__th {
      min-width: 200px;
    }

    .base-table__th:nth-of-type(2) {
      min-width: 300px;
    }

    .base-table__td {
      vertical-align: top;
    }

    .base-table {

    }

  }

  &__filter {
    display: flex;
    align-items: flex-start;

    > * + * {
      margin-left: 10px;
    }
  }

  &__filter-icon,
  &__filter-sort {
    flex: 0 0 auto;

    width: 15px;
    height: 15px;
    color: #7b898d;

    cursor: pointer;
  }

  &__filter-text {
    flex: 0 1 auto;

    font-size: 13px;
    line-height: 15px;
    font-weight: 500;
    color: #7b898d;
  }

  &__filter-item {
    padding: 15px 20px;
    color: #7b898d;

    cursor: pointer;

    &:hover {
      background-color: rgba(#f0f3f5, 0.7);
    }

    &--active {
      font-weight: bold;
    }
  }

  &__text--positive {
    font-weight: bold;
    color: #32ba91;
  }

  &__text--negative {
    font-weight: bold;
    color: #ff5555;
  }

  &__additional {
    margin-top: 10px;
    color: #7b898d;
    @media screen and (max-width: $break_md) {
      margin-top: 0;
    }
  }

  &__items-header {
    background-color: rgba(211, 216, 219, 0.3);
    margin-bottom: 10px;
    border-radius: 10px;
    padding: 10px;

    @media screen and (min-width: $break_md) {
      display: none;
    }

    > * {
      padding: 10px;
      border-bottom: none;
      text-transform: uppercase;
    }
  }

  &__items {
    @media screen and (min-width: $break_md) {
      display: none;
    }

    > * + * {
      margin-top: 1rem;
    }
  }

  &__item {
    border: 1px solid #d3d8db;
    border-radius: 10px;
    margin-bottom: 1rem;
    background-color: #fff;
    > * + * {
      border-top: 1px solid #d3d8d8;
    }
  }

  &__item-attr {
    padding: 10px;
  }

  &__item-attr-header {
    content: attr(aria-label);
    font-weight: bold;
  }
}
</style>