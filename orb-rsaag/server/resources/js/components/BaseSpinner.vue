<template>
  <div class="base-spinner" :class="modifiers">
    <div class="base-spinner__icon"/>
    <div class="base-spinner__text" v-if="text">{{text}}</div>
  </div>
</template>

<script>

export default {
  name: 'BaseSpinner',
  props: {
    text: { type: String },
  },

  data: () => ({
    stack: 0,
    isShown: false
  }),

  computed: {
    modifiers() {
      return { 'base-spinner_no-text': !this.text }
    }
  }
}
</script>

<style lang="scss">
.base-spinner {
  display: flex;
  align-items: center;
  justify-content: center;

  padding: 20px 30px;

  border-radius: 60px;
  background-color: #fff;

  box-shadow: 0 3px 26px rgba(0, 0, 0, 0.16);

  &__icon {
    position: relative;
    height: 40px;
    width: 40px;

    &:before {
      content: '';

      position: absolute;
      top: 0;
      left: 0;
            
      width: calc(100% - 8px);
      height: calc(100% - 8px);

      border: 4px solid rgba(0, 0, 0, 0.1);
      border-radius: 100%;
    }

    &:after {
      content: '';

      position: absolute;
      top: 0;
      left: 0;
            
      width: calc(100% - 8px);
      height: calc(100% - 8px);

      border: 4px solid transparent;
      border-top-color: #30bf94;

      animation: loader 0.6s linear;
      animation-iteration-count: infinite;
      border-radius: 100%;

      box-shadow: 0px 0px 0px 1px transparent;
    }
  }

  &__text {
    margin-left: 20px;

    font-size: 15px;
    font-weight: bold;
    white-space: nowrap;

    color: #d3d8db;
    text-transform: uppercase;
  }

  &_no-text {
    padding: 10px;
  }
}

@keyframes loader {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}
</style>