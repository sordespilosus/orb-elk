<template>
  <div class="base-radio">
    <label class="base-radio__inner">
      <div class="base-radio__radio">
        <input 
        v-model="model"
        v-bind="$attrs"
        :name="name"
        :value="inputValue"
        type="radio" 
        class="base-radio__input"/>

        <div class="base-radio__icon"/>
      </div>
      
      <div class="base-radio__text" v-if="label">{{label}}</div>
      <div class="base-radio__text" v-if="$slots.label"><slot name="label"/></div>
    </label>
  </div>
</template>
<script>
export default {
  name: 'BaseRadio',

  props: {
    inputValue: { type: null, required: true },
    name: { type: String, required: true },
    value: { type: null, required: true },
    label: { type: String }
  },

  computed: {
    model: {
      get() {
        return this.value;
      },

      set(value) {
        this.$emit('input', value);
      }
    }
  }
};
</script>
<style lang="scss">
@import '~@/sass/variables';

.base-radio {
  &__inner {
    display: flex;
    align-items: center;

    &:hover .base-radio__icon {
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

    &:checked + .base-radio__icon {
      background-color: #fff;
      border-color: $color-accent;
      border-width: 6px;
    }

    &:focus + .base-radio__icon {
      border-color: $color-accent;
    }
  }

  &__icon {
    width: 20px;
    height: 20px;
    border: 1px solid #d3d8db;

    background-color: #fff;

    border-radius: 100%;
    transition: 0.2s;
    cursor: pointer;
  }

  &__text {
    flex: 1 1 auto;
    margin-left: 10px;
  }

  &[disabled] {
    .base-radio__input:focus + .base-radio__icon,
    .base-radio__input:checked + .base-radio__icon,
    .base-radio__inner:hover .base-radio__icon {
      border-color: #d3d8db;
    }
  }
}
</style>