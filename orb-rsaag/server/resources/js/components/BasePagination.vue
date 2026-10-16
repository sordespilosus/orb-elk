<template>
  <div class="base-pagination" v-if="size > 0">

    <div class="base-pagination__inner">

      <icon
      v-if="currentIndex > 0"
      @click="changeIndex(-max)"
      name="action/dropdown"
      dir="right"
      class="base-pagination__icon"/>

      <div
      v-for="item in itemsByIndex"
      :key="item"
      class="base-pagination__item"
      :class="{'base-pagination__item_active': item == selected}"
      @click="select(item)">
        {{item}}
      </div>

      <icon
      v-if="!itemsByIndex.includes(size)"
      @click="changeIndex(max)"
      name="action/dropdown"
      dir="left"
      class="base-pagination__icon"/>

      <div class="base-pagination__size">из {{size}}</div>

    </div>

  </div>
</template>
<script>
import { times } from 'lodash';

export default {
  name: 'BasePagination',
  props: {
    selected: { type: [String, Number], required: true },
    max: { type: [String, Number], default: 10 },
    size: { type: [String, Number], required: true },
    currentIndexSynced: { type: [String, Number], default: 0 },
  },

  model: {
    prop: 'selected',
    event: 'page:select'
  },

  data() {
    return {
      currentIndex: this.selected - 1,
    }
  },

  computed: {
    items() {
      return times(this.size, i => i + 1);
    },

    itemsByIndex() {
      return this.items.slice(this.currentIndex, this.currentIndex + Number(this.max));
    },
  },

  watch: {
    currentIndexSynced: function (newIndex, oldIndex) {
      const max = this.size,
            min = 0;
      let index = Number(newIndex);

      if(index < min) index = min;
      else if(index > max) index = max;

      this.currentIndex = index;
    },
  },

  methods: {
    changeIndex(n) {
      const max = this.size - this.max,
            min = 0;

      let index = this.currentIndex + Number(n);

      if(index < min) index = min;
      else if(index > max) index = max;

      this.currentIndex = index;
    },

    select(number) {
      this.$emit('page:select', number);
    }
  }
}
</script>
<style lang="scss">
.base-pagination {

  &__inner {
    display: flex;
    flex-wrap: wrap;
    align-items: center;

    color: #7B898D;

    > * {
      flex: 0 0 auto;
      user-select: none;
    }
  }

  &__icon {
    cursor: pointer;

    &:hover {
      color: #2A3E4C;
    }
  }

  &__item {

    padding: 0 5px;
    min-width: 30px;
    height: 30px;

    font-size: 13px;
    line-height: 30px;

    text-align: center;

    cursor: pointer;

    &_active,
    &:hover {
      color: #2A3E4C;
      font-weight: bold;
    }
  }

  &__icon + &__size {
    margin-left: 10px;
  }

  &__size {
    font-size: 13px;
    line-height: 30px;
  }
}
</style>

