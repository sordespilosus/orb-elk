<template>
  <vue-scroll
      :ops="options"
      class="touch-scroll__inner">
    <slot/>
  </vue-scroll>
</template>

<script>
import VueScroll from 'vuescroll';

export default {
  name: 'TouchScroll',
  components: { VueScroll },
  computed: {
    options() {
      const defaults = {
        vuescroll: {
          //sizeStrategy: 'number'
        },

        rail: {
          background: '#FFFFFF',
          opacity: 0,
          size: '2px',
          specifyBorderRadius: '5px',
          gutterOfSide: '0',
          gutterOfEnds: '30px',
          border: 'none'
        },

        bar: {
          keepShow: true,
          background: '#7B898D',
          specifyBorderRadius: '5px',
          size: '3px'
        }
      };

      return { ...defaults, ...this.$attrs };
    }
  }
};
</script>

<style>
</style>