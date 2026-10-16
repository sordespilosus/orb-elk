<template>
  <div class="base-checkbox">
    <label
    class="base-checkbox__inner"
    :class="getModifiers">
      <div class="base-checkbox__checkbox">
        <input 
        v-model="model"
        v-bind="$attrs"
        :value="inputValue"
        :size="size"
        type="checkbox" 
        class="base-checkbox__input"/>

        <icon 
        name="tick" 
        class="base-checkbox__icon"
        :class="`base-checkbox__icon_${size}`"/>
      </div>
      
      <div class="base-checkbox__text" v-if="label">{{label}}</div>
      <div class="base-checkbox__text" v-if="$slots.label"><slot name="label"/></div>
    </label>
  </div>
</template>
<script>
export default {
  name: 'BaseCheckbox',

  props: {
    value: { type: null },
    inputValue: { type: null, default: true },
    label: { type: String },
    size: { type: String, default: 'small' }
  },

  computed: {
    model: {
      get() {
        return this.value;
      },

      set(value) {
        this.$emit('input', value);
      }
    },

    getModifiers(){
      return {
        'base-checkbox--disabled': 'disabled' in this.$attrs && this.$attrs.disabled !== false,
        'base-checkbox--readonly': 'readonly' in this.$attrs && this.$attrs.readonly !== false,
      }
    }
  }
};
</script>
<style lang="scss">
@import '~@/sass/variables';

.base-checkbox {
  &__inner {
    display: flex;
    align-items: center;

    &:hover .base-checkbox__icon {
      border-color: $color-accent;
    }
  }

  &__checkbox {
    flex: 0 0 auto;
  }

  &__input {
    position: absolute;
    border: 0;
    margin: -1px;
    padding: 0;
    width: 1px;
    height: 1px;
    overflow: hidden;
    clip: rect(0px, 0px, 0px, 0px);

    &:checked + .base-checkbox__icon {
      background-color: $color-accent;
      border-color: $color-accent;
      color: #fff;
    }

    &:focus + .base-checkbox__icon {
      border-color: $color-accent;
    }
  }

  &__icon {
    border: 1px solid #d3d8db;

    background-color: #ffffff;
    color: #ffffff;

    border-radius: 2px;
    transition: 0.2s;
    cursor: pointer;

    &_small {
      width: 20px;
      height: 20px;
      padding: 2px;
    }

    &_big {
      width: 40px;
      height: 40px;
      padding: 9px;
    }
  }

  &__text {
    flex: 1 1 auto;
    margin-left: 10px;
  }

  &--disabled {
    opacity: 0.4;
    pointer-events: none;
  }

  &--readonly {
    pointer-events: none;
  }
}
</style>
