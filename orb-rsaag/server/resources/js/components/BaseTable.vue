<template>
  <div class="base-table">
    <table class="base-table__inner">
      <thead class="base-table__thead">
        <tr class="base-table__head-row">
          <th v-if="hasRadio || hasCheckbox" class="base-table__th">#</th>
          <template v-for="(cell, index) in header">
            <slot name="th" :data="{cell}">
              <th
              :key="index"
              class="base-table__th">{{cell.label}}</th>
            </slot>
          </template>

        </tr>
      </thead>

      <tbody>
        <template v-if="body.length">
          <template v-for="(row, index) in body">
            <slot name="row" :row="row">
              <tr 
              @click="selectRow(row)"
              :key="index"
              class="base-table__body-row">
                <td class="base-table__td" v-if="hasCheckbox"><base-checkbox v-model="model" :input-value="row"/></td>
                <td class="base-table__td" v-if="hasRadio"><base-radio  v-model="model" :input-value="row" name="base-table-radio"/></td>

                <template v-for="(cell, key) in withHeaderNames(row)">
                  <slot name="td" :data="{cell, row, key}">
                    <td class="base-table__td" :key="key">{{cell}}</td>
                  </slot>
                </template>
              </tr>
            </slot>
          </template>
        </template>

        <tr 
        v-else
        class="base-table__body-row">
          <td 
          v-text="'Нет данных'"
          colspan="666"
          class="base-table__empty"/>
        </tr>
      </tbody>
    </table>
  </div>
</template>
<script>
import BaseCheckbox from './BaseCheckbox';
import BaseRadio from './BaseRadio';

export default {
  name: 'BaseTable',
  components: { BaseCheckbox, BaseRadio },
  props: {
    header: { type: Array, required: true },
    body: { type: Array, required: true },
    hasCheckbox: { type: Boolean, default: false },
    hasRadio: { type: Boolean, default: false },
    selected: null
  },

  model: {
    prop: 'selected',
    event: 'table:select-row'
  },

  computed: {
    model: {
      get() {
        return this.selected;
      },

      set(value) {
        this.selectRow(value);
      }
    }
  },

  methods: {
    selectRow(row) {
      this.$emit('table:select-row', row);
    },

    withHeaderNames(row) {
      return this.header.map(item => row[item.field]);
    }
  }
};
</script>
<style lang="scss">
.base-table {
  &__inner {
    height: 100%;
    min-width: 100%;
    border-collapse: collapse;
  }

  &__th {
    padding: 20px;
    border-bottom: 2px solid #d3d8d8;
    color: #7b898d;
    text-align: left;
    font-weight: 400;
  }

  &__body-row {
    cursor: pointer;

    &:hover {
      background-color: #ededed;
    }
  }

  &__td {
    padding: 20px;
    border-bottom: 1px solid #d3d8d8;

    max-width: 180px;
    vertical-align: middle;
    overflow-wrap: break-word;
  }

  &__empty {
    padding: 30px 20px;
    background-color: #fff;
    font-weight: bold;
    text-align: center;
    text-transform: uppercase;
    border-radius: 0 0 10px 10px;
  }
}
</style>


