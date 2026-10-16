<template>
  <modal 
  v-on="$listeners"
  v-bind="$attrs"
  title="Детализация записи"
  class="admin-log-edit">
    <template #text>
      <div class="modal__text">

        <base-accordion :startShown="true" class="admin-log-edit__accordion">
          <template #title>Основная информация</template>
          <base-list 
          :items="logData.main"
          label="label"
          value="value"
          class="admin-log-edit__list">
            <template #value="{ item: { key, value } }">
              <div class="base-list__value" v-if="['get', 'post'].includes(key)">
              <ul v-if="value.length" class="base-list__list">
                <li class="base-list__item" v-for="(value, key) in value" :key="key">{{value}}</li>
              </ul>
              <template v-else>Нет данных</template>
              </div>

              <div v-else class="base-list__value">{{value}}</div>
            </template>
          </base-list>
        </base-accordion>

        <base-accordion class="admin-log-edit__accordion">
          <template #title>Показать заголовки</template>
          <base-list :items="logData.header.value"/>
        </base-accordion>
      </div>
    </template>
  </modal>
</template>
<script>
const withAccordionData = log => {
  let { header, ...main } = cloneDeep(log);

  main = values(main);
  header.value = mapValues(header.value, toString); //@todo: выяснить, зачем значения заголовков передаются в виде массива!
  return { header, main };
};

import { cloneDeep, values, mapValues, toString } from 'lodash';

import Modal from '@/js/components/Modal';
import BaseList from '@/js/components//BaseList';
import BaseAccordion from '@/js/components//BaseAccordion';

export default {
  name: 'AdminLogEdit',
  components: { Modal, BaseList, BaseAccordion },
  props: {
    log: { type: Object, required: true }
  },

  data() {
    return {
      logData: withAccordionData(this.log)
    };
  }
};
</script>
<style lang="scss">
.admin-log-edit {
  &__accordion {
    & + & {
      border-top: 1px solid #fff;
    }
  }
}
</style>