<template>
  <div class="card">
    <header class="card__header" style="height: 64px;">
      <div class="card__header-content" style="height: 64px;" @click="expand = !expand">
        <div class="card__header-title"  v-text="title"  />
        <button v-if="needExpanding" class="card__expand-button" :class="{'card__expand-button--closed': !expand}" >
          <i aria-hidden="true" class="card__icon-expanding" />
        </button>

      </div>
    </header>
    <div class="card__content" :class="{'card--closed': !expand}">
      <slot v-if="expand"/>
    </div>

  </div>
</template>

<script>
  /**
   * Copy from https://vuetifyjs.com/
   * because not drag full lib into project
   */

  export default {
    name: "Card",
    props: {
      title: {type: String, default: ''},
      needExpanding: {type: Boolean, default: false},
      initExpand: {type: Boolean, default: true},
    },
    data() {
      return {
        expand: true,
      }
    },
    mounted() {
      this.expand = this.initExpand
    }
  }
</script>

<style lang="scss">

  .card {
    box-shadow: 0 2px 4px -1px rgba(0, 0, 0, 0.2), 0px 4px 5px 0px rgba(0, 0, 0, 0.14), 0px 1px 10px 0px rgba(0, 0, 0, 0.12);
    background: white;
    margin-bottom: 20px;
	cursor: pointer;
	  border-radius: 10px;

    &__expand-button {
      display: inline-flex;
      margin-left: auto;
      width: 50px;
      justify-content: center;
      align-items: center;
      height: 30px;
      border-radius: 5px;
      cursor: pointer;
      transform: rotateX(180deg);

      &--closed {
        transform: none;
      }
    }

    &__icon-expanding:before {
      display: inline-block;
      width: 0;
      height: 0;
      vertical-align: .155em;
      content: "";
      border-top: .4em solid;
      border-right: .5em solid transparent;
      border-bottom: 0;
      border-left: .5em solid transparent;
    }

    &--closed {
      padding: 0 !important;
    }

    &__content {
      padding: 20px 10px 5px;

      &-row {
        display: flex;
        justify-content: flex-start;
        flex: 1 1;
        margin-bottom: 30px;

        .form-field {
          margin-left: 10px;
          flex: 1 1;
        }

        .base-input__input {
          border-radius: 10px;
        }

      }
    }

    &__header {
      contain: layout;
      display: block;
      flex: 1 1 auto;
      max-width: 100%;
      transition: 0.2s cubic-bezier(0.4, 0, 0.2, 1) transform, 0.2s cubic-bezier(0.4, 0, 0.2, 1) background-color, 0.2s cubic-bezier(0.4, 0, 0.2, 1) left, 0.2s cubic-bezier(0.4, 0, 0.2, 1) right, 280ms cubic-bezier(0.4, 0, 0.2, 1) box-shadow, 0.25s cubic-bezier(0.4, 0, 0.2, 1) max-width, 0.25s cubic-bezier(0.4, 0, 0.2, 1) width;
      box-shadow: 0 2px 4px -1px rgba(0, 0, 0, 0.2), 0px 4px 5px 0px rgba(0, 0, 0, 0.14), 0px 1px 10px 0px rgba(0, 0, 0, 0.12);
      background-color: #32ba91 !important;
      border-color: #32ba91 !important;
      color: white;
	    border-radius: 10px;

      &-content {
        align-items: center;
        display: flex;
        position: relative;
        z-index: 0;
        padding: 4px 16px;
      }

      &-title {
        font-size: 1.25rem;
        line-height: 1.5;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        margin-left: 10px;
      }
    }
  }
</style>