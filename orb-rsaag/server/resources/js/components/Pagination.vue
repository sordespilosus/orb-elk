<template>
  <div class="pagination">
    <div class="pagination__item" @click="changeIndex(-max)">&lt;&lt;</div>
    <div class="pagination__item" @click="changeIndex(-1)">&lt;</div>

    <div 
    v-for="(item, index) in itemsByIndex" 
    :key="index"
    class="pagination__item" 
    :class="{'pagination__item_active': item === value}"
    @click="selectPage(item)">
      {{item}}
    </div>

    <div class="pagination__item" @click="changeIndex(1)">&gt;</div>
    <div class="pagination__item" @click="changeIndex(max)">&gt;&gt;</div>

  </div>
</template>
<script>
export default {
  name: 'Pagination',
  props: {
    value: {required: true},
    max: {default: 10},
    size: {required: true}
  },

  data() {
    return {
      currentIndex: this.value - 1,
    }
  },

  computed: {
    items() {
      return [...new Array(this.size)].map((el, i) => i + 1);
    },

    itemsByIndex() {
      return this.items.slice(this.currentIndex, this.currentIndex + this.max);
    }
  },

  methods: {
    changeIndex(n) {
      const max = this.size - this.max,
            min = 0;

      let index = this.currentIndex + n;

      if(index < min) index = min;
      else if(index > max) index = max;

      this.currentIndex = index;

      this.$emit('input', index + 1);
    },

    selectPage(number) {
      this.$emit('input', number);
    }
  }
}
</script>
<style lang="scss">
.pagination {
  display: flex;

  &__item {
    user-select: none;

    width: 40px;
    height: 40px;

    font-size: 15px;
    line-height: 40px;

    text-align: center;

    cursor: pointer;

    &_active {
      background-color: #d3d8db;
      font-weight: bold;
    }

    &:hover {
      background-color: #d3d8db;
    }

    & + & {
      margin-left: 5px;
    }
  }
}
</style>

