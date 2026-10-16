<template>
  <div class="base-calendar">
    <div class="base-calendar__inner">
      <div class="base-calendar__header">

        <icon
        @click="switchDate(-1)"
        name="dropdown" 
        dir="right"
        class="base-calendar__prev"/>

        <div class="base-calendar__title" @click="switchMode">{{title}}</div>

        <icon
        @click="switchDate(1)"
        name="dropdown" 
        dir="left"
        class="base-calendar__next"/>

      </div>
      <div 
      class="base-calendar__dates"
      tabindex="0"
      @keydown="handleDatesKeydown($event)">
        <div 
        v-for="date in dates" 
        @mouseenter="highlightDate(date.value)"
        @click="handleDateClick(date.value)"
        :key="date.value.toString()"
        :class="getModifiers(date)"
        class="base-calendar__date">{{date.label}}</div>
      </div>
    </div>
  </div>
</template>
<script>
import {
  startOfWeek,
  startOfMonth,
  startOfYear,
  startOfDecade,

  addDays,
  addMonths,

  addYears,

  getDecade,

  eachDayOfInterval,
  isSameYear,
  isSameMonth,
  isSameDay,
  isWeekend,
  isAfter,
  isBefore
} from 'date-fns';

import { mapKeys } from 'lodash';

const isSameDecade = (leftDate, rightDate) => getDecade(leftDate) === getDecade(rightDate);

export default {
  name: 'BaseCalendar',

  props: {
    nonBefore: { type: Date },
    nonAfter: { type: Date }
  },

  data: () => ({
    mode: 'days',

    monthNames: ['Янв', 'Февр', 'Март', 'Апр', 'Май', 'Июнь', 'Июль', 'Авг', 'Сент', 'Окт', 'Ноя', 'Дек'],
    weekdaysNames: ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'],

    date: startOfMonth(new Date()),
    highlightedDate: new Date()
  }),

  computed: {

    title() {
      const month = this.monthNames[this.date.getMonth()],
            year = this.date.getFullYear(),
            startOfDecade = getDecade(this.date),
            endOfDecade = startOfDecade + 9;

      return {
        'days': `${month}, ${year}`,
        'months': year,
        'years': `${startOfDecade} – ${endOfDecade}`
      }[this.mode];
    },

    dates() {
      return this[this.mode];
    },

    days() {
      const start = startOfWeek(this.date, {weekStartsOn: 1});
      const end = addDays(start, 41);
      const range = eachDayOfInterval({start, end});

      this.__innerDays = range.map(date => {
        return {
          value: date,
          label: date.getDate(),
          isInMonth: isSameMonth(date, this.date),
          isWeekend: isWeekend(date)
        }
      });

      return this.__innerDays;
    },

    months() {
      const start = startOfYear(this.date);
      this.__innerMonths = Array.from({length: 12}).map((date, index) => {
        date = addMonths(start, index);

        return {
          value: date,
          label: this.monthNames[index]
        }
      });

      return this.__innerMonths;
    },

    years() {
      const start = addYears(startOfDecade(this.date), -1);
      this.__innerYears = Array.from({length: 12}).map((date, index) => {
        date = addYears(start, index);

        return {
          value: date,
          label: date.getFullYear(),
          isInDecade: isSameDecade(date, this.date)
        }
      })
      
      return this.__innerYears;
    },


  },

  methods: {
    isDisabled(date) {
      const isDateAfter = this.nonAfter && isAfter(date, this.nonAfter);
      const isDateBefore = this.nonBefore && isBefore(date, this.nonBefore);

      return isDateAfter || isDateBefore;
    },

    isSameDate(leftDate, rightDate) {
      return {
        'days': isSameDay,
        'months': isSameMonth,
        'years': isSameYear
      }[this.mode](leftDate, rightDate);   
    },

    switchDate(n) {
      const switchDate = {
        'days': addMonths(this.date, n),
        'months': addYears(this.date, n),
        'years': addYears(this.date, n * 12)
      }[this.mode];

      this.date = switchDate;
      this.highlightedDate = new Date(switchDate.getTime());
    },

    switchMode() {
      const switchMode = {
        'days': 'months',
        'months': 'years',
        'years': 'days'
      }[this.mode];

      return this.mode = switchMode;
    },

    getModifiers(date) {
      const namespace = 'base-calendar__date_';

      const baseModifiers = {
        //MODES
        'day': this.mode === 'days',
        'month': this.mode === 'months',
        'year': this.mode === 'years',

        //STATES
        'weekend': this.mode === 'days' && date.isWeekend,
        'in-month': this.mode === 'days' && date.isInMonth,
        'out-of-month': this.mode === 'days' && !date.isInMonth,

        'in-decade': this.mode === 'years' && date.isInDecade,
        'out-of-decade': this.mode === 'years' && !date.isInDecade,

        'highlighted': this.isSameDate(date.value, this.highlightedDate),
        'disabled': this.isDisabled(date.value)
      };

      const extraModifiers = this.getExtraModifiers ? this.getExtraModifiers(date) : {};

      return mapKeys({...baseModifiers, ...extraModifiers}, (value, key) => namespace + key);
    },

    handleDateClick(date) {
      const mode = this.mode;

      if(mode === 'days') this.$emit('date:selected', date)

      else if(mode === 'months') {
        this.date = date; 
        this.mode = 'days';
      }

      else if(mode === 'years') {
        this.date = date;
        this.mode = 'months';
      }
    },

    handleDatesKeydown(event) {

      event.preventDefault();

      const method = {
        'days': addDays,
        'months': addMonths,
        'years': addYears
      }[this.mode];

      const argument = {
        'Enter': 0,
        'ArrowLeft': -1,
        'ArrowRight': 1,
        'ArrowUp': this.mode === 'days' ? -7 : -4,
        'ArrowDown': this.mode === 'days' ? 7 : 4
      }[event.code];

      if(argument) this.highlightDate(method(this.highlightedDate, argument));
      if(event.code === 'Enter') this.handleDateClick(this.highlightedDate);
    },

    highlightDate(date) {
      const isInList = this.dates.some(d => this.isSameDate(d.value, date));

      if(!isInList) this.date = startOfMonth(date);
      this.highlightedDate = date;  
    }
  }
}
</script>
<style lang="scss">
.base-calendar {

  &__inner {
    width: 210px;
  }

  &__header {
    position: relative;
    padding: 0 50px;

    padding-bottom: 5px;
    margin-bottom: 5px;

    border-bottom: 1px dotted #d3d8db;
  }

  &__title {
    height: 40px;

    line-height: 40px;
    text-align: center;

    border-radius: 2px;
    cursor: pointer;

    &:hover {
      background-color: #f6f4fa;
    }
  }

  &__prev,
  &__next {
    position: absolute;
    top: 0;

    width: 40px;
    height: 40px;
    padding: 10px;

    border-radius: 2px;

    color: #7b898d;

    cursor: pointer;
    
    &:hover {
      background-color: #f6f4fa;
    }
  }

  &__prev {
    left: 0;
  }

  &__next {
    right: 0;
  }

  &__dates {
    display: flex;
    flex-wrap: wrap;

    &:focus {
      outline: 1px solid #32ba91;
    }
  }

  &__date {
    flex: 0 0 auto;

    &_day {
      width: 30px;
      height: 30px;
      margin: 2px 0;

      line-height: 30px;
      text-align: center;

      border-radius: 2px;
      cursor: pointer;
    }

    &_month,
    &_year {
      width: 52.5px;
      height: 52.5px;
      margin: 2px 0;
      
      line-height: 52.5px;

      text-align: center;

      border-radius: 2px;
      cursor: pointer;
    }

    &_weekend {
      color: #ff5555;
    }

    &_out-of-month,
    &_out-of-decade {
      color: #d3d8db;
    }

    &_highlighted {
      background-color: #f6f4fa;
    }

    &_in-range {
      //background-color: #f6f4fa;
      background-color: #eaf8fe;
    }

    &_in-range-start {
      border-top-left-radius: 10px;
      border-bottom-left-radius: 10px;
    }

    &_in-range-end {
      border-top-right-radius: 10px;
      border-bottom-right-radius: 10px;
    }

    &_active {
      background-color: #32ba91;
      color: #fff;
    }

    &_disabled {
      background-color: #f3f3f3;
      color: #ccc;
      border-radius: 0;
      cursor: not-allowed;
    }
  }
}
</style>