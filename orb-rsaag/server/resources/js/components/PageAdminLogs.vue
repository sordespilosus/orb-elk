<template>
    <div class="page-admin-logs">

    <h2 class="heading-2 page-admin-logs__title">Лог запросов к серверу</h2>

    <base-table 
    v-bind="tableData"
    class="page-admin-logs__table"
    @table:select-row="showLogDetails($event)">
      <template #th="{data: { cell } }">
        <th class="base-table__th">
          <div class="page-admin-logs__head">

            <div 
            @click="setSortOptions(cell.field)" 
            class="page-admin-logs__head-text">{{cell.label}}</div>

              <base-popover 
              v-if="['id', 'date_created', 'method'].includes(cell.field)"
              :popover-options="{ maxHeight: 'none' }">
                <template #trigger>
                  <icon 
                  name="filter" 
                  class="page-admin-logs__filter-icon"
                  :class="{'page-admin-logs__filter-icon_active': isFilterActive(cell.field)}"/>
                </template>

                <template #default="{data: {show, hide}}">
                  <div class="page-admin-logs__filter">
                    <div class="page-admin-logs__filter-title">{{`Фильтр: "${cell.label}"`}}</div>
                    <!--@todo: по дефолту прописывать чекбоксам и инпутам value = model, если value не указана-->

                    <div class="page-admin-logs__filter-methods" v-if="cell.field === 'method'">
                      <base-checkbox 
                      v-model="filters.method_get"
                      :input-value="filters.method_get"
                      label="GET"/>
                      <base-checkbox 
                      v-model="filters.method_post"
                      :input-value="filters.method_post"
                      label="POST"/>
                      <base-checkbox 
                      v-model="filters.method_others"
                      :input-value="filters.method_others"
                      label="Другие"/>
                    </div>

                    <base-datepicker 
                    v-if="cell.field === 'date_created'" 
                    v-model="dateFilterModel"
                    type="range"
                    :isConfirm="false"/>

                    <div class="page-admin-logs__filter-ids" v-if="cell.field === 'id'">
                      <base-input 
                      v-model.number="filters.min_id"
                      @change="checkMinRange"
                      type="number"/>
                      
                      <base-input
                      v-model.number="filters.max_id"
                      @change="checkMaxRange"
                      type="number"/>
                    </div>

                    <div class="page-admin-logs__filter-actions">
                      <a
                      @click="applyFilter(cell.field, hide)"
                      class="link page-admin-logs__filter-apply">Применить</a>

                      <a
                      @click="resetFilter(cell.field, hide)"
                      class="link page-admin-logs__filter-reset">Сбросить</a>
                    </div>

                  </div>
                </template>             
              </base-popover>

              <div 
              v-show="queryParams.order_by === cell.field"
              @click="setSortOptions(cell.field)" 
              class="page-admin-logs__sort-icon"
              :class="{'page-admin-logs__sort-icon_asc' : queryParams.is_asc}">
                <icon name="dropdown"/>
              </div>

          </div>
        </th>

      </template>
      
      <template #td="{data: { cell } }">
        <td class="base-table__td">{{cell.value}}</td>
      </template>     
    </base-table>

    <pagination v-model="pageModel" :size="paginationLength"/>

    <admin-log-edit
    v-if="isModalShown"
    @modal:close="isModalShown = false"
    :log="currentLog"/>
  </div>
</template>
<script>
import { format, parseISO } from 'date-fns';

import AdminLogEdit from './AdminLogEdit';

import BaseTable from './BaseTable';
import BaseCheckbox from './BaseCheckbox';
import BaseInput from './BaseInput';
import BaseDatepicker from './BaseDatepicker';
import BasePopover from './BasePopover';

import Pagination from './Pagination';

const handleLogData = log => {
  const dateHandler = val => {
    if (!val) return 'Не указано';

    const parsedDate = parseISO(val);

    return format(parsedDate, 'dd.MM.yyyy HH:mm:ss');
  };

  const emptyHandler = val => (val ? val : 'Не указано');

  const fields = [
    { key: 'id', label: 'ID' },
    { key: 'user_id', label: 'Пользователь' },
    { key: 'date_created', label: 'Дата', handler: dateHandler },
    { key: 'url', label: 'URL' },
    { key: 'method', label: 'Метод' },
    { key: 'ip', label: 'IP' },
    { key: 'get', label: 'GET-параметры' },
    { key: 'post', label: 'POST-параметры' },
    { key: 'header', label: 'Заголовки' }
  ];

  return fields.reduce((result, field) => {
    const key = field.key;

    if (key in log) {
      const system_value = log[key];
      const value = field.handler
        ? field.handler(system_value)
        : emptyHandler(system_value);
      result[key] = { ...field, system_value, value };
    }

    return result;
  }, {}); //@LODASH!!!!!
};

import { inRange, pick } from 'lodash';

export default {
  name: 'PageAdminLogs',
  components: {
    AdminLogEdit,
    BaseTable,
    BaseCheckbox,
    BaseInput,
    BaseDatepicker,
    BasePopover,
    Pagination
  },

  data() {
    return {
      logs: [],
      currentLog: {},

      queryParams: {
        page: 1,
        per_page: 50,

        is_asc: false,
        order_by: 'date_created',

        method_get: false,
        method_post: false,
        method_others: false,

        max_date: null,
        min_date: null,

        max_id: null,
        min_id: null
      },

      filters: {
        method_get: false,
        method_post: false,
        method_others: false,

        max_date: null,
        min_date: null,

        max_id: null,
        min_id: null
      },

      paginationLength: null,

      isModalShown: false
    };
  },

  computed: {
    tableData() {
      const header = [
        { label: 'ID', field: 'id' },
        { label: 'Пользователь', field: 'user_id' },
        { label: 'Дата', field: 'date_created' },
        { label: 'URL', field: 'url' },
        { label: 'Метод', field: 'method' }
      ];

      return { header, body: this.logs };
    },

    pageModel: {
      get() {
        return this.queryParams.page;
      },

      set(value) {
        this.queryParams.page = value;
        this.fetchLogs();
      }
    },

    dateFilterModel: {
      get() {
        const { min_date, max_date } = this.filters;
        return min_date && max_date
          ? [min_date, max_date].map(d => new Date(d))
          : null;
      },

      set(value) {
        if (!value) {
          this.filters.min_date = null;
          this.filters.max_date = null;
        } else {
          const [min_date, max_date] = value.map(d => d.toISOString());
          this.filters.min_date = min_date;
          this.filters.max_date = max_date;
        }
      }
    }
  },

  methods: {
    init() {
      this.fetchLogs();
    },

    async fetchLogs() {
      const { commit, dispatch } = this.$store;

      commit('setLoader', true);
      const { logs, countLogs } = await dispatch('fetchLogs', this.queryParams);
      this.logs = logs.map(handleLogData);
      this.paginationLength = Math.ceil(countLogs / this.queryParams.per_page);
      commit('setLoader', false);
    },

    setSortOptions(name) {
      const query = this.queryParams;
      const isSet = query.order_by === name;

      query.order_by = name;
      query.is_asc = isSet ? !query.is_asc : true;

      this.fetchLogs();
    },

    getFieldsByFilter(filter) {
      const map = {
        id: ['min_id', 'max_id'],
        date_created: ['min_date', 'max_date'],
        method: ['method_get', 'method_post', 'method_others']
      };

      return map[filter];
    },

    isFilterActive(filter) {
      const fields = this.getFieldsByFilter(filter);
      return fields.some(field => this.queryParams[field]);
    },

    applyFilter(filter, hidePopover) {
      const fields = this.getFieldsByFilter(filter);

      //неочевидный момент: когда применяем фильтры обновляем показатель страницы!
      this.queryParams = {
        ...this.queryParams,
        ...pick(this.filters, fields),
        page: 1
      };

      hidePopover();
      this.fetchLogs();
    },

    resetFilter(filter, hidePopover) {
      const fields = this.getFieldsByFilter(filter);
      fields.forEach(key => (this.filters[key] = null));
      this.applyFilter(filter, hidePopover);
      this.fetchLogs();
    },

    checkIdExtent(id, isMin) {
      //@todo: переписать красиво
      let { min_id, max_id } = this.queryParams;

      if (isMin) {
        this.queryParams.min_id =
          id <= 0 ? 0 : inRange(id, max_id + 1) ? id : max_id;
      } else {
        this.queryParams.max_id = inRange(id, min_id, Infinity) ? id : min_id;
      }
    },

    checkMaxRange() {
      const { min_id, max_id } = this.filters;
      if (min_id && max_id && max_id < min_id) this.filters.max_id = min_id;
    },

    checkMinRange(value) {
      const { min_id, max_id } = this.filters;
      if (min_id && max_id && min_id > max_id) this.filters.min_id = max_id;
    },

    showLogDetails(log) {
      this.isModalShown = true;
      this.currentLog = log;
    }
  },

  created() {
    this.init();
  }
};
</script>
<style lang="scss">
.page-admin-logs {
  &__title {
    margin-bottom: 20px;
  }

  &__table {
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

  &__head-text {
    border-bottom: 1px dotted currentColor;
    cursor: pointer;
  }

  &__sort-icon {
    cursor: pointer;
    transition: transform 0.3s;

    &_asc {
      transform: rotate(180deg);
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
    font-size: 13px;
    line-height: 20px;
    font-weight: 500;

    text-align: center;
    text-transform: uppercase;
  }

  &__filter-methods {
    > * + * {
      margin-top: 10px;
    }
  }

  &__filter-ids {
    position: relative;
    display: flex;

    .base-input {
      flex: 1 1 auto;
      width: 40px;

      &__input {
        padding: 4px;
      }
    }

    .base-input + .base-input {
      margin-left: 40px;
    }

    &::after {
      content: '—';
      position: absolute;
      left: 50%;

      line-height: 30px;
      transform: translateX(-50%);
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
}
</style>
