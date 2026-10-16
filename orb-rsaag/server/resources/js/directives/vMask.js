import Inputmask from 'inputmask';

export default {
  bind(el, binding) {
    Inputmask(binding.value).mask(el);
  },

  unbind(el) {
    Inputmask.remove(el);
  },
};