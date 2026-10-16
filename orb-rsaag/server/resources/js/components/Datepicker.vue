<template>
  <div class="datepicker">
    <div class="datepicker__inner">

      <component 
      v-bind="$attrs"
      v-model="model"
      :is="selectedComponent"
      class="datepicker__datepicker"/>

      <div class="datepicker__buttons" v-if="isConfirm">
        <a
        @click="confirm"
        class="datepicker__confirm">Применить</a>

        <a
        @click="reset"
        class="datepicker__reset">Сбросить</a>
      </div>
    </div>
  </div>
</template>
<script>

const startOfDozenth = (date) => {
  let result = toDate(date);
  let minutes = date.getMinutes();
  while(minutes % 5 !== 0) minutes++;
  result.setMinutes(minutes);
  return result;
};

import {
  parse, 
  format,

  startOfHour,
  startOfMonth,

  toDate,
  compareAsc,
  isWithinInterval
} from 'date-fns';

import Calendar from '@/js/components/Calendar';

const SinglePicker = {
  name: 'SinglePicker',
  extends: Calendar,

  props: { 
    value: { type: [Date, String] },
    formatter: { type: String },
  },

  data: () => ({
    innerDate: null
  }),

  methods: {
    isValid(value) {
      return value instanceof Date || typeof value === 'string';
    },

    handleInputValue(value) {
      if(!this.isValid(value)) return this.innerDate = null;
      
      const parseFunction = val => this.formatter ? parse(val, this.formatter, new Date()) : val;
      this.innerDate = startOfDozenth(parseFunction(value));

      const isInList = this.dates.some(d => this.isSameDate(d.value, this.innerDate));
      
      if(!isInList) {
        this.date = startOfHour(startOfMonth(this.innerDate));
        this.highlightedDate = toDate(this.innerDate);
      }
    },

    handleOutputValue(value) {
      const formatFunction = val => this.formatter ? format(val, this.formatter) : val;
      this.$emit('input', formatFunction(value));
    },

    handleSelectValue(value) {
      if(!this.isDisabled(value)) {
        this.handleOutputValue(value);
      }  
    },

    getExtraModifiers(date) {
      return {
        'active': this.isSameDate(date.value, this.innerDate)
      }
    }
  },

  created() {
    this.$on('date:selected', this.handleSelectValue);
  },

  watch: {
    value: { handler: 'handleInputValue', immediate: true }
  }
};

const RangePicker = {
  name: 'RangePicker',
  extends: Calendar,
  props: {
    value: { type: Array },
    formatter: { type: String }
  },

  data: () => ({
    innerDates: null
  }),

  computed: {
    range() {
      const [ first, second ] = this.innerDates;
      return [first || this.highlightedDate, second || this.highlightedDate].sort(compareAsc);
    }
  },

  methods: {
    isValid(value) {
      const typeValidator = val => val instanceof Date || typeof val === 'string';
      return Array.isArray(value) && value.every(typeValidator);
    },

    normalizeRange(value) {
      return value.sort(compareAsc);      
    },

    handleInputValue(value) {
      if(!this.isValid(value)) return this.innerDates = null;

      const parseFunction = val => this.formatter ? parse(val, this.formatter, new Date()) : val;

      const values = value.map(val => startOfDozenth(parseFunction(val)));

      this.innerDates = this.normalizeRange(values);

      const inList = date => this.innerDates.some(val => this.isSameDate(val, date.value));
      const isInList = this.dates.some(inList);

      if(!isInList) {
        this.date = startOfMonth(this.innerDates[0]);
        this.highlightedDate = toDate(this.innerDates[0]);
      }
    },

    handleOutputValue(value) {
      const formatFunction = val => this.formatter ? format(val, this.formatter) : val;
      this.$emit('input', value.sort(compareAsc).map(formatFunction));
    },

    handleSelectValue(value) {
      if(this.isDisabled(value)) return false;

      if(!this.innerDates) return this.innerDates = [value];

      const [first, second] = this.innerDates;
      if(second) this.innerDates = [value];
      else this.handleOutputValue([...this.innerDates, value]);
    },

    getExtraModifiers(date) {
      if(!this.isValid(this.innerDates)) return {};

      const [start, end] = this.range;
      return {
        'active': this.innerDates.some(value => this.isSameDate(value, date.value)),
        'in-range': isWithinInterval(date.value, {start, end}),
        'in-range-start': this.isSameDate(start, date.value),
        'in-range-end': this.isSameDate(end, date.value),
      };
    }
  },

  created() {
    this.$on('date:selected', this.handleSelectValue);
  },

  watch: {
    value: { handler: 'handleInputValue', immediate: true }
  }
};

export default {
  name: 'Datepicker',
  components: { 
    Calendar, 
    SinglePicker, 
    RangePicker
  },

  inheritAttrs: false,

  props: {
    value: { type: null },
    type: { type: String, default: 'single'},
    isConfirm: { type: Boolean, default: true }
  },

  data() {
    return {
      selectedValue: this.value
    }
  },

  computed: {
    selectedComponent() {
      return {
        'single': SinglePicker,
        'range': RangePicker
      }[this.type];
    },

    model: {
      get() {
        return this.selectedValue
      },

      set(value) {
        this.select(value);
      }
    }
  },

  methods: {
    select(value) {
      this.selectedValue = value;

      if(!this.isConfirm) this.confirm();
    },

    confirm() {
      this.$emit('input', this.selectedValue);
    },

    reset() {
      this.selectedValue = null;
      this.confirm();
    }
  },

  watch: {
    value: { handler: 'select' }
  }
}
</script>
<style lang="scss">
@import '~@/sass/variables';

.datepicker {

  &__inner {
    display: inline-block;
  }

  &__datepicker {

  }

  &__buttons {
    display: flex;
    justify-content: center;

    padding: 10px 0;
    border-top: 1px dotted #d3d8db;

    & > * + * {
      margin-left: 20px;
    }
  }

  &__confirm {
    flex: 0 0 auto;

    border-bottom: 1px dotted currentColor;
    color: $color-positive;
    cursor: pointer;
  }

  &__reset {
    flex: 0 0 auto;
    border-bottom: 1px dotted currentColor;
    color: #7B898D;
    cursor: pointer;
  }
}
</style>