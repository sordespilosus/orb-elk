import tippy from 'tippy.js';

const defaults = {
  arrow: true,
  theme: 'light'
};

export default (options = {}) => ({
  bind(el, binding) {
    return tippy(el, {
      content: binding.value,
      ...defaults,
      ...options      
    });
  },

  unbind(el) {
    el._tippy.destroy();
  } 
});