<template>
  <div class="base-accordion" :class="{'base-accordion_opened' : isShown}">
    <slot name="header">
      <div class="base-accordion__header" @click="isShown = !isShown">
        <div class="base-accordion__title"><slot name="title"/></div>
        <icon name="dropdown" class="base-accordion__icon"/>
      </div>
    </slot>


    <transition name="vue-modal-fade">
      <div class="base-accordion__content" v-show="isShown">
        <slot/>
      </div>
    </transition>
  </div>
</template>
<script>
export default {
  name: 'BaseAccordion',
  props: {
    startShown: { type: Boolean, default: false }
  },

  data() {
    return {
      isShown: this.startShown
    }
  }
}
</script>
<style lang="scss">
.base-accordion {
  &__header {
    display: flex;
    justify-content: space-between;
    align-items: center;

    padding: 10px;

    background-color: #d3d8db;
    cursor: pointer;
  }

  &__title {
    flex: 1 1 auto;
  }

  &__icon {
    flex: 0 0 auto;
    margin-left: 20px;

    transition: transform .3s;
  }

  &__content {
  }

  &_opened {
    .base-accordion__icon {
      transform: rotate(180deg);
    }
  }
}

</style>

