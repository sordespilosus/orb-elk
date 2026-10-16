<template>
  <div class="base-textarea">
    <textarea 
    v-model="model"
    v-bind="$attrs"
    class="base-textarea__textarea"
    :class="{'base-textarea__textarea_error' : isError}"
    :style="`min-height: ${minHeight}px; border-radius: ${rounded}px`"/>
  </div>
</template>
<script>
export default {
  name: 'BaseTextarea',
  props: {
	  rounded: {type: Number},
    value: { type: null },
    minHeight: { type: Number, default: 200 },
    isError: { type: Boolean, default: false }
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
}
</script>
<style lang="scss">
.base-textarea {
  &__textarea {
    width: 100%;
    padding: 19px;
    border: 1px solid #ccc;

    color: #666;
    font-size: 18px;
    line-height: 20px;

    outline: none;
    resize: none;

    &:focus,
    &:hover {
      border-color: #1abc9c;
    }

    &:disabled {
      background-color: #ebebe4;
      color: #999;
      pointer-events: none;

      &:hover, 
      &:focus {
        border-color: #ccc;
      }
    }

    &:read-only {
      pointer-events: none;

      &:hover,
      &:focus {
        border-color: #ccc;
      }
    }

    &_error {
      background-color: #fff6f6;
      border-color: #ff5555;
      
      &:hover, 
      &:focus {
      border-color: #ff5555;
      }
    }
  }
}
</style>
