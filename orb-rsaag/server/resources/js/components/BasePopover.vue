<template>
  <div class="base-popover">
    <div 
    class="base-popover__trigger" 
    ref="trigger">
      <slot 
      name="trigger" 
      :data="popoverInterface"/>
    </div>
    <div
    class="base-popover__popper"
    :style="{'max-height': `${popoverOptions.maxHeight || 250}px`}"
    ref="popper">
      <slot :data="popoverInterface"/>
    </div>
  </div>
</template>
<script>
import { get } from 'lodash';
import tippy from 'tippy.js';

export default {
  name: 'BasePopover',

  props: {
    popoverOptions: { type: Object, default: () => ({}) }
  },

  data: () => ({
    tippy: null
  }),

  computed: {
    defaults() {
      return {
        arrow: true,
        interactive: true,
        placement: 'bottom',
        theme: 'popover',
        trigger: 'click',
        maxWidth: ''
      };
    },

    popoverInterface() {
      return {
        show: this.show,
        hide: this.hide,
        info: get(this.tippy, 'state') || {}
      };
    }
  },

  methods: {
    setupTippy() {
      if (!this.tippy) {
        const { trigger, popper } = this.$refs;
        let { hasSameWidth, maxHeight, ...tippyOptions } = this.popoverOptions;

        tippyOptions = {
          ...this.defaults,
          ...tippyOptions,
          content: popper
        };

        if (hasSameWidth) {
          tippyOptions.popperOptions = {
            modifiers: {
              setSameWidth: {
                enabled: true,
                order: 849,
                fn: this.setSameWidth
              }
            }
          };
        }

        this.tippy = tippy(trigger, tippyOptions);

        this.$emit('popover:init', this.popoverInterface);
      }
    },

    setSameWidth(data) {
      const { width, left, right } = data.offsets.reference;

      data.styles.width = width;
      data.offsets.popper.width = width;
      data.offsets.popper.left = left;
      data.offsets.popper.right = right;

      return data;
    },

    show() {
      if (this.tippy) this.tippy.show();
    },

    hide() {
      if (this.tippy) this.tippy.hide();
    }
  },

  mounted() {
    this.setupTippy();
    this.$on('hook:beforeDestroy', () => {
      this.tippy.destroy();
      this.tippy = null;
    });
  }
};
</script>
<style lang="scss">
.base-popover {
  &__trigger {
    outline: none;
    cursor: pointer;
  }

  &__popper {
    overflow: auto;
  }
}

.tippy-tooltip.popover-theme {
  padding: 0;

  color: #26323d;
  text-align: left;

  background-color: #fff;
  box-shadow: 0 0 20px 4px rgba(154, 161, 177, 0.15),
    0 4px 80px -8px rgba(36, 40, 47, 0.25),
    0 4px 4px -2px rgba(91, 94, 105, 0.15);
  border-radius: 5px;

  &[x-placement^='top'] .tippy-arrow {
    border-top: 8px solid #fff;
    border-right: 8px solid transparent;
    border-left: 8px solid transparent;
  }

  &[x-placement^='bottom'] .tippy-arrow {
    border-bottom: 8px solid #fff;
    border-right: 8px solid transparent;
    border-left: 8px solid transparent;
  }

  &[x-placement^='left'] .tippy-arrow {
    border-left: 8px solid #fff;
    border-top: 8px solid transparent;
    border-bottom: 8px solid transparent;
  }

  &[x-placement^='right'] .tippy-arrow {
    border-right: 8px solid #fff;
    border-top: 8px solid transparent;
    border-bottom: 8px solid transparent;
  }

  .tippy-backdrop {
    background-color: #fff;
  }

  .tippy-roundarrow {
    fill: #fff;
  }
}
</style>