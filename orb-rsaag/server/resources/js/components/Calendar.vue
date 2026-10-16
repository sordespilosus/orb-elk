<template>
  <div class="calendar">
    <div class="calendar__inner">
      <div class="calendar__header">

        <icon
        @click="switchDate(-1)"
        name="dropdown"
        dir="right"
        class="calendar__prev"/>

        <div class="calendar__title" @click="switchMode">{{title}}</div>

        <icon
        @click="switchDate(1)"
        name="dropdown"
        dir="left"
        class="calendar__next"/>

      </div>
      <div 
      class="calendar__dates"
      tabindex="0"
      @keydown="handleDatesKeydown($event)">
        <div 
        v-for="date in dates" 
        @mouseenter="highlightDate(date.value)"
        @click="handleDateClick(date.value)"
        :key="date.value.getTime()"
        :class="getModifiers(date)"
        class="calendar__date">{{date.label}}</div>
      </div>
    </div>
  </div>
</template>
<script>
import {
  startOfHour,
  startOfDay,
  startOfWeek,
  startOfMonth,
  startOfYear,
  startOfDecade,
  
  addMinutes,
  addHours,
  addDays,
  addMonths,

  addYears,

  getDecade,

  eachDayOfInterval,

  isSameMinute,
  isSameYear,
  isSameMonth,
  isSameDay,
  isSameHour,
  isWeekend,
  isAfter,
  isBefore
} from 'date-fns';

import { mapKeys, times } from 'lodash';

const isSameDecade = (leftDate, rightDate) => 
  getDecade(leftDate) === getDecade(rightDate);

export default {
  name: 'Calendar',

  props: {
    nonBefore: { type: Date },
    nonAfter: { type: Date },
    showTime: { type: Boolean }
  },

  data: () => ({
    mode: 'days',

    monthNames: ['Янв', 'Февр', 'Март', 'Апр', 'Май', 'Июнь', 'Июль', 'Авг', 'Сент', 'Окт', 'Ноя', 'Дек'],
    weekdaysNames: ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'],

    date: startOfMonth(new Date()),
    highlightedDate: startOfHour(new Date())
  }),

  computed: {

    title() {
      const day = this.date.getDate(),
            month = this.monthNames[this.date.getMonth()],
            year = this.date.getFullYear(),
            startOfDecade = getDecade(this.date),
            endOfDecade = startOfDecade + 9;

      return {
        'minutes': `${day} ${month}, ${year}`,
        'hours': `${day} ${month}, ${year}`,
        'days': `${month}, ${year}`,
        'months': year,
        'years': `${startOfDecade} – ${endOfDecade}`
      }[this.mode];
    },

    dates() {
      return this[this.mode];
    },

    minutes() {
      const start = startOfHour(this.date);
      const hour = start.getHours();
      this.__innerMinutes = times(12, index => {
        const minutes = `0${index * 5}`.slice(-2);
        return {
          value: addMinutes(start, index * 5),
          label: `${hour}:${minutes}`
        }
      });

      return this.__innerMinutes;
    },

    hours() {
      const start = startOfDay(this.date);
      this.__innerHours = times(24, index => {
        return {
          value: addHours(start, index),
          label: `${index}:00`
        }
      });

      return this.__innerHours;
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

      const isDateAfter = this.nonAfter && isAfter(date, this.nonAfter) && !this.isSameDate(date, this.nonAfter);
      const isDateBefore = this.nonBefore && isBefore(date, this.nonBefore) && !this.isSameDate(date, this.nonBefore);

      return isDateAfter || isDateBefore;
    },

    isSameDate(leftDate, rightDate) {
      return {
        'minutes': isSameMinute,
        'hours': isSameHour,
        'days': isSameDay,
        'months': isSameMonth,
        'years': isSameYear
      }[this.mode](leftDate, rightDate);   
    },

    switchDate(n) {
      const switchDate = {
        'minutes': addHours(this.date, n),
        'hours': addDays(this.date, n),
        'days': addMonths(this.date, n),
        'months': addYears(this.date, n),
        'years': addYears(this.date, n * 12)
      }[this.mode];

      this.date = switchDate;
      this.highlightedDate = new Date(switchDate.getTime());
    },

    switchMode() {
      const switchMode = {
        'minutes': 'hours',
        'hours': 'days',
        'days': 'months',
        'months': 'years',
        'years': 'days'
      }[this.mode];

      return this.mode = switchMode;
    },

    getModifiers(date) {
      const namespace = 'calendar__date_';

      const baseModifiers = {
        //MODES
        'minute': this.mode === 'minutes',
        'hour': this.mode === 'hours',
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

      if(this.isDisabled(date)) return false;

      const mode = this.mode;

      if(mode === 'minutes') {
        this.$emit('date:selected', date);
      }

      if(mode === 'hours') {
        this.date = date;
        this.mode = 'minutes';
      }

      if(mode === 'days') {
        if(!this.showTime) {
          this.$emit('date:selected', date);
        } else {
          this.date = date;
          this.mode = 'hours';
        }
      }

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
        'minutes': (date, n) => addMinutes(date, n*5),
        'hours': addHours,
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

      if(!isInList) {
        this.date = {
          'minutes': startOfHour,
          'hours': startOfDay,
          'days': startOfMonth
        }[this.mode](date)
      }

      this.highlightedDate = date;  
    }
  }
}
</script>
<style lang="scss">
@import '~@/sass/variables';

.calendar {

  &__inner {
    width: 293px;
    padding: 10px;
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
      background-color: rgba(#f0f3f5, .7);
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
      background-color: rgba(#f0f3f5, .7);
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
      outline: 1px solid $color-accent;
    }
  }

  &__date {
    flex: 0 0 auto;

    &_hour {
      width: 45.5px;
      height: 45.5px;
      margin: 2px 0;

      line-height: 45.5px;
      text-align: center;

      border-radius: 2px;
      cursor: pointer;
    }

    &_day {
      width: 39px;
      height: 39px;
      margin: 2px 0;

      line-height: 39px;
      text-align: center;

      border-radius: 2px;
      cursor: pointer;
    }

    &_minute,
    &_month,
    &_year {
      width: 68.25px;
      height: 68.25px;
      margin: 2px 0;
      
      line-height: 68.25px;

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
      background-color: rgba(#f0f3f5, .7);
    }

    &_in-range {
      background-color: rgba($color-accent, .1);
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
      background-color: $color-accent;
      color: #fff;
    }

    &_disabled {
      background-color: rgba($color-negative, .2);
      border-radius: 0;
      cursor: default;
    }
  }
}
</style>