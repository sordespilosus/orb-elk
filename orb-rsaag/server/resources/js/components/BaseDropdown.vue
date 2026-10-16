<template>
  <multiselect 
  v-bind="$attrs"
  v-model="model"
  :options="options"
  :show-labels="false"
  :allow-empty="false"
  :disabled="disabled"
  :option-height="60">
    <icon name="dropdown" class="multiselect__caret" slot="caret"/>
    
    <!--передаем слоты через обертку-->
    <slot 
    v-for="slot in Object.keys($slots)" 
    :name="slot" 
    :slot="slot"/>
    
    <template 
    v-for="slot in Object.keys($scopedSlots)" 
    :slot="slot" 
    slot-scope="scope">
      <slot :name="slot" v-bind="scope"/>
    </template>

    <template slot="noResult">Ничего не найдено</template>
  </multiselect>
</template>
<script>
import Multiselect from 'vue-multiselect';
import 'vue-multiselect/dist/vue-multiselect.min.css';

export default {
  name: 'BaseDropdown',
  props: { 
    value: { required: true },
    options: { type: Array, required: true },
    modelKey: { type: String },
    isError: { type: Boolean },
    disabled: { type: Boolean, default: false }
  },

  components: { Multiselect },
  computed: {
    modifiers() {
      return { 'multiselect--error' : this.isError }
    },

    model: {
      get() {
        const { modelKey, value, options } = this;
        return modelKey ? 
          options.find(o => o[modelKey] == value) : value
      },

      set(value) {
        this.$emit('input', this.modelKey ? value[this.modelKey] : value);
      }
    }
  }
}
</script>
<style lang="scss">
@import "~@/sass/variables";

.multiselect {
  min-height: 60px;

  color: #666;
	box-shadow: 0 2px 7px $shadow-color;
	border-radius: 10px;

  &__select {
    width: 60px;
    height: 60px;
    padding: 0;
  }

  &__caret {
    position: absolute;
    top: 0;
    right: 0;

    width: 60px;
    height: 60px;
    padding: 20px;

    transition: transform .2s;
  }

  &__tags {
    min-height: 60px;
    padding: 19px 59px 0 19px;
    border-color: #ccc;

    font-size: 18px;

    border-radius: 0;
    background-color: #fff;
  }

  &__single {
    padding-left: 0;
    margin-bottom: 19px;

    font-size: 18px;
  }

  &__input {
    padding: 0;
    margin-bottom: 19px;

    font-size: 18px;

    color: #666;
  }

  &__placeholder {
    margin-bottom: 19px;
    padding-top: 0;

    line-height: 20px;
    color: inherit; //@todo: цвет для плейсхолдера
  }

  &__content-wrapper {
    border-color: #ccc;
  }

  &__option {
    min-height: 60px;
    padding: 22px 20px;

    &::after {
      line-height: 60px;
      padding-right: 20px;
    }

    &--highlight {
      background-color: #1abc9c;

      &::after {
        background-color: #1abc9c;
      }
    }

    &--selected {
      &.multiselect__option--highlight {
        background-color: #1abc9c;

        &::after {
          background-color: #1abc9c;
        }
      }
    }
  }

  &--active {
    .multiselect__caret {
      transform: rotate(180deg);
    }
  }

  &--disabled {
    color: #999;
    opacity: 1;

    .multiselect__tags {
      background-color: #ebebe4;
    }

  }

  &--error {
    .multiselect__tags {
      border-color: #ff5555;
    }

    &:hover, 
    &:focus {
      border-color: #ff5555;
    }
  }

  &:hover {
    .multiselect__tags {
      border-color: #1abc9c;
    }
  }
}
</style>
