<template>
  <div class="page-my-notifications">
    <div class="page-my-notifications__inner">
      <div class="page-my-notifications__heading">

        <h2 
        v-text="'Мои заявления'"
        class="heading-2 page-my-notifications__title" />

        <template v-if="organizations.length">
          <div class="page-my-notifications__declarant">

            <h2 
            v-text="'Заявитель:'"
            class="heading-2 page-my-notifications__declarant-name" />

            <base-dropdown 
            v-validate
            @input="setOrganization"
            :data="organizations"
            :value="filters.org_id"
            label-name="short_name"
            value-name="id"
            placeholder="Организации"
            class="page-my-notifications__organizations-dropdown"/>

          </div>
        </template>
      </div>

      <template v-if="!isLoading">
        <base-table 
        v-bind="tableData"
        @table:select-row="showNotify($event)"
        class="page-my-notifications__table">

          <template #th="{data: { cell } }">

            <th 
            v-if="cell.field === 'show_number'"
            @click="setSortOptions(cell.field)"
            class="base-table__th">

              <div class="page-my-notifications__head">
                <div 
                v-text="cell.label" 
                class="page-my-notifications__head-text"/>

                <div 
                v-show="filters.order_by === cell.field"
                :class="{'page-my-notifications__sort-icon_asc' : filters.is_asc}"
                class="page-my-notifications__sort-icon">
                  <icon name="dropdown"/>
                </div>
              </div>
            </th>

            <th 
            v-else-if="cell.field === 'status_name'"
            class="base-table__th">
              <base-popover>
                <template #trigger>
                  <div class="page-my-notifications__head">
                    <div 
                    v-text="cell.label" 
                    class="page-admin-logs__head-text"/>

                    <icon 
                    name="filter"
                    class="page-my-notifications__filter-icon"
                    :class="{'page-my-notifications__filter-icon_active': filters.status}"/>
                  </div>
                </template>

                <template #default="{ data: { show, hide } }">
                  <div class="page-my-notifications__filter">
                    <div class="page-my-notifications__filter-radio">
                      <base-radio 
                      v-for="status in statuses"
                      v-model="currentStatusFilter"
                      :key="status.number"
                      :input-value="status.number"
                      :label="status.name"
                      :name="cell.field"/>
                    </div>
                  </div>
                  <div class="page-my-notifications__filter-actions">
                    <a 
                    v-text="'Применить'"
                    @click="applyFilter('status')"
                    class="link page-my-notifications__filter-apply"/>

                    <a
                    v-text="'Сбросить'"
                    @click="resetFilter('status')"
                    class="link page-my-notifications__filter-reset"/>
                  </div>
                </template>
              </base-popover>
            </th>
            <th 
            v-else-if="cell.field === 'service_name'"
            class="base-table__th">
              <base-popover>
                <template #trigger>
                  <div class="page-my-notifications__head">
                    <div 
                    v-text="cell.label" 
                    class="page-admin-logs__head-text"/>

                    <icon 
                    name="filter"
                    class="page-my-notifications__filter-icon"
                    :class="{'page-my-notifications__filter-icon_active': filters.service}"/>
                  </div>
                </template>
                <template #default="{data: {show, hide}}">
                  <div class="page-my-notifications__filter">
                    <div class="page-my-notifications__filter-radio">
                      <base-radio 
                      v-for="service in services"
                      v-model="currentServiceFilter"
                      :key="service.number"
                      :input-value="service.number"
                      :label="service.name"
                      :name="cell.field"/>
                    </div>
                  </div>

                  <div class="page-my-notifications__filter-actions">
                    <a 
                    v-text="'Применить'"
                    @click="applyFilter('service')"
                    class="link page-my-notifications__filter-apply"/>

                    <a 
                    v-text="'Сбросить'"
                    @click="resetFilter('service')"
                    class="link page-my-notifications__filter-reset"/>
                  </div>
                </template>
              </base-popover>
            </th>
          </template>

          <template #td="{data: { cell, key } }">
            <td class="base-table__td"
            v-html="cell"
            :key="key"/>
          </template>

        </base-table>

        <div 
        v-if="notifications.length === 0"
        v-text="'Нет заявлений'"
        class="page-my-notifications__not-one heading-2"/>

        <pagination 
        v-if="resultsTotal > 1"
        v-model="pageModel"
        :size="resultsTotal" 
        class="page-my-notifications__pagination"/>

      </template>

      <base-spinner 
      v-else
      class="page-my-notifications__spinner"/>

    </div>

    <my-notification 
    v-if="isShowMore"
    @notify:close="isShowMore = false"
    :data="currentNotify"/>
  </div>
</template>

<script>
import { pickBy } from 'lodash';
import MyNotification from './MyNotification';
import BaseSpinner from './BaseSpinner';
import Pagination from './Pagination';
import BaseTable from './BaseTable';
import BasePopover from './BasePopover';
import BaseRadio from './BaseRadio';
import BaseDropdown from './BaseDropdown';

export default {
  name: 'PageMyNotifications',
  components: {
    BaseDropdown,
    MyNotification,
    BaseSpinner,
    Pagination,
    BaseTable,
    BasePopover,
    BaseRadio
  },
  props: {
    query: { type: Object }
  },

  data() {
    return {
      organizations: [],
      notifications: [],
      statuses: [],
      services: [],
      filters: {
        page: this.query.page ? this.query.page : 1,
        status: this.query.status,
        service: this.query.service,
        org_id: this.query.org_id ? this.query.org_id : null,
        order_by: this.query.order_by
          ? this.query.order_by
          : 'reg_service_date',
        is_asc: this.query.is_asc && this.query.is_asc === 'true' ? true : false
      },
      isLoading: true,
      resultsTotal: 1,
      tableHeader: [
        { label: 'Номер', field: 'show_number' },
        { label: 'Наименование услуги', field: 'service_name' },
        { label: 'Статус', field: 'status_name' }
      ],
      currentNotify: null,
      isShowMore: false,
      currentStatusFilter: this.query.status,
      currentServiceFilter: this.query.service
    };
  },
  computed: {
    tableData() {
      return { header: this.tableHeader, body: this.notifications };
    },

    queryParams() {
      const query = { ...this.filters };
      const defaults = {
        page: 1,
        status: null,
        org_id: null,
        order_by: 'reg_service_date',
        is_asc: false
      };

      return pickBy(query, (value, key) => value != defaults[key]);
    },

    pageModel: {
      get() {
        return this.filters.page;
      },

      set(value) {
        if (value != this.filters.page) {
          this.filters.page = value;
          this.$router.push({
            name: 'myNotifications',
            query: this.queryParams
          });
        }
      }
    }
  },

  methods: {
    async fetchNotifications() {
      this.isLoading = true;
      const query = this.queryParams;
      const { dispatch } = this.$store;
      const response = await dispatch('fetchNotifications', query);
      if (response.status) {
        var x = query;
        debugger;
        this.organizations = response.data.organizations;
        this.notifications = response.data.notifications;
        this.resultsTotal = response.data.resultsTotal;
        this.statuses = response.data.statuses;
        this.services = response.data.services;
      } else
        this.$notify({ type: 'error', text: 'Получить заявления не удалось' });
      this.isLoading = false;
    },

    showNotify(notify) {
      this.isShowMore = true;
      this.currentNotify = notify;
    },

    applyFilter(name) {
      const modelName = `current${`${name
        .charAt(0)
        .toLocaleUpperCase()}${name.slice(1)}`}Filter`;
      this.filters[`${name}`] = this[modelName];
      this.$router.push({
        name: 'myNotifications',
        query: this.queryParams
      });
    },

    resetFilter(name) {
      const modelName = `current${`${name
        .charAt(0)
        .toLocaleUpperCase()}${name.slice(1)}`}Filter`;
      this.filters[`${name}`] = this[modelName] = null;
      this.$router.push({
        name: 'myNotifications',
        query: this.queryParams
      });
    },

    setOrganization(org) {
      this.filters.page = 1;
      this.filters.org_id = org;
      const query = this.queryParams;

      this.$router.push({
        name: this.$route.name,
        params: this.$route.params,
        query
      });
      //this.$router.push({name: this.$route.name, query});
    },

    setSortOptions(name) {
      const isSet = this.filters.order_by === name;

      this.filters.order_by = name;
      this.filters.is_asc = isSet ? !this.filters.is_asc : true;
      this.$router.push({
        name: 'myNotifications',
        query: this.queryParams
      });
    }
  },

  watch: {
    query: { handler: 'fetchNotifications', immediate: true }
  }
};
</script>

<style lang="scss">
.page-my-notifications {
  &__inner {
    .base-table__td {
      overflow-wrap: normal;
    }
  }

  &__list {
    & > * + * {
      border-top: 1px solid #e8e8e8;
    }
  }

  &__declarant {
    flex-direction: row;
    align-items: center;
    display: flex;
    &-name {
      margin-right: 15px;
    }
  }

  &__spinner {
    background-color: inherit;
    box-shadow: none;
  }

  &__not-one {
    text-align: center;
    margin-bottom: 20px;
  }

  &__head {
    display: flex;
    align-items: center;

    > * {
      flex: 0 0 auto;
    }

    > * + * {
      margin-left: 10px;
    }
  }

  &__table {
    margin-bottom: 20px;

    .base-table__td:nth-of-type(2) {
      width: 60%; //@todo: говнокодище
    }
  }

  &__filter-icon {
    cursor: pointer;

    &_active {
      color: #32ba91;
    }
  }

  &__filter {
    padding: 10px;

    text-align: left;

    font-size: 17px;
    line-height: 20px;

    > * + * {
      margin-top: 10px;
    }
  }

  &__filter-title {
    font-size: 17px;
    line-height: 20px;
    font-weight: 500;

    text-align: center;
    //text-transform: uppercase;
  }

  &__filter-radio {
    > * + * {
      margin-top: 15px;
    }
  }

  &__filter-actions {
    text-align: center;

    > * + * {
      margin-left: 10px;
    }
  }

  &__filter-apply {
    flex: 0 0 auto;
    border-bottom: 1px dotted currentColor;
  }

  &__filter-reset {
    flex: 0 0 auto;
    border-bottom: 1px dotted currentColor;
    color: #7b898d;
  }

  &__organizations-dropdown {
    max-width: 400px;
    min-width: 250px;

    &__parent {
      font-weight: bold;
    }

    &__child {
      padding-left: 15px;
    }
  }

  &__heading {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    margin-bottom: 20px;
  }

  &__head-text {
    flex: 0 0 auto;
    border-bottom: 1px dotted currentColor;
    cursor: pointer;
  }

  &__sort-icon {
    flex: 0 0 auto;
    cursor: pointer;
    transition: transform 0.3s;

    &_asc {
      transform: rotate(180deg);
    }
  }
}
</style>
