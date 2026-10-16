<template>
  <portal to="modals">
    <div 
    @mousedown.self="close('overlay')"
    :key="id"
    class="modal">
      <div 
      :style="`max-width: ${maxWidth}px;`"
      class="modal__body">
        <icon 
        @click="close('icon')"
        name="close" 
        class="modal__close"/>

        <slot>
          <div class="modal__content">

            <slot name="title">
              <h2 
              v-if="title"
              v-html="title"
              class="heading-2 modal__title"/>
            </slot>

            <slot name="text">
              <div 
              v-if="text"
              v-html="text"
              class="wysiwyg modal__text"/>
            </slot>

            <div 
            v-if="hasFooter"
            class="modal__footer">

              <slot name="footer"/>

              <div 
              v-if="hasButtons"
              class="modal__buttons">
                <slot
                :data="modalInterface"
                name="buttons">
                  <button 
                  @click="close('button')"
                  v-text="'Закрыть'"
                  class="button button--neutral"/>
                </slot>
              </div> 
            </div>

          </div>
        </slot>
      </div>
    </div>
  </portal>
</template>

<script>
import { uniqueId, pick } from 'lodash';

export default {
  name: 'Modal',
  props: {
    title: { type: String },
    text: { type: String },
    hasFooter: { type: Boolean, default: true },
    hasButtons: { type: Boolean, default: true },
    maxWidth: { default: '1200' }
  },

  data: () => ({
    id: uniqueId('modal_')
  }),

  computed: {
    modalInterface() {
      return pick(this, ['close']);
    }
  },

  methods: {
    close(target) {
      this.$emit('modal:close', target);
    },

    escapeHandler(event) {
      const esc = 27;
      if (event.keyCode === esc) this.close('escape');
    },

    init() {
      window.addEventListener('keydown', this.escapeHandler);

      this.$once('hook:destroyed', () => {
        window.removeEventListener('keydown', this.escapeHandler);
      });
    }
  },

  mounted() {
    this.init();
  }
};
</script>
<style lang="scss">
@import '~@/sass/variables';

.modal {
  position: fixed;
  left: 0;
  right: 0;
  top: 0;
  bottom: 0;
  z-index: 1000;

  padding: 20px 55px;
  padding-bottom: 0;

  overflow: auto;

  background-color: rgba(240, 243, 245, 0.96);

  text-align: center;

  transition: opacity 0.3s;

  &:after {
    content: '';
    display: inline-block;
    vertical-align: middle;
    height: 100%;
  }

  &__body {
    position: relative;
    display: inline-block;
    vertical-align: middle;

    width: 80%;
    padding-top: 20px;
    padding-bottom: 20px;
    margin-bottom: 20px;

    background-color: #fff;
    border-radius: 10px;
    box-shadow: 0 0 26px #e4e8eb;
  }

  &__close {
    float: right;
    margin-left: 20px;
    margin-right: 20px;
    margin-bottom: 20px;

    width: 20px;
    height: 20px;

    color: #7b898d;

    cursor: pointer;

    &:hover {
      color: #2a3e4c;
    }
  }

  &__content {
    text-align: left;

    > * + * {
      margin-top: 20px;
    }
  }

  &__title {
    padding-left: 20px;
    padding-right: 20px;
    padding-bottom: 20px;
    border-bottom: 1px solid #d3d8db;
  }

  &__text {
    padding-left: 20px;
    padding-right: 20px;
  }

  &__footer {
    padding-left: 20px;
    padding-right: 20px;
    padding-top: 20px;
    margin-top: 30px;
    border-top: 1px solid #d3d8db;
  }

  &__buttons {
    display: flex;
    align-items: center;

    > * {
      flex: 1 1 auto;
      max-width: 220px;
    }

    > * + * {
      margin-left: 20px;
    }
  }
}
</style>