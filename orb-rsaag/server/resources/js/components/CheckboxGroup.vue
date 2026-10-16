<template>
  <div class="checkbox-group">
    <div 
    class="checkbox-group__inner"
    :class="`checkbox-group__inner_${direction}`">
      <base-checkbox 
      v-for="checkbox in items"
      v-bind="$attrs"
      v-model="model"
      :key="checkbox.value"
      :input-value="checkbox.value"
      :label="checkbox.label"
      class="checkbox-group__checkbox"/>
    </div>
  </div>
</template>
<script>
import BaseCheckbox from './BaseCheckbox';
import { cloneDeep } from 'lodash';
export default {
  name: 'CheckboxGroup',
  components: { BaseCheckbox },
  inheritAttrs: false,
  props: {
    value: { type: null, required: true },
    items: { type: Array, requited: true },
    direction: { type: String, default: 'row' }
  },

  computed: {
    model: {
      get() {
        return this.value
      },

      set(value) {
        this.$emit('input', value);
      }
    }
  }
}
</script>
<style lang="scss">
.checkbox-group {
  &__inner {

    &_column {
      > * + * {
        margin-top: 20px;
      }
    }

    &_row {
      display: flex;

      > * {
        flex: 0 0 auto;
      }

      > * + * {
        margin-left: 20px;
      }
    }
  }
}
</style>