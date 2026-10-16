<template>
  <div class="base-editor">
    <vue-editor 
    v-bind="$attrs"
    v-on="$listeners"
    v-model="model"
    :editorToolbar="toolbar"
    class="base-editor__inner wysiwyg"/>
  </div>
</template>

<script>
import { VueEditor } from "vue2-editor";
export default {
  name: 'BaseEditor',
  components: { VueEditor },
  props: { value: { required: true }},

  data: () => ({
    toolbar: [
      [{ 'header': [1, 2, 3, false] }],
      ['bold', 'italic', 'underline'],
      [{ 'list': 'ordered'}, { 'list': 'bullet' }],
      [{ 'indent': '-1'}, { 'indent': '+1' }],
    ]
  }),

  computed: {
    model: {
      get() {
          return this.value;
      },

      set(value) {
          this.$emit('input', value);
      }
    }
  }
}
</script>

<style lang="scss">
.base-editor {
  background-color: #fff;

  &[disabled] {
    background-color: #ebebe4;
    color: #999;
    pointer-events: none;
  }

  &[readonly] {
    pointer-events: none;
  }

  .ql-container {
    font-family: inherit;
  }

  .ql-editor {
    font-size: 17px;

    ol li.ql-indent-1::before {
      content: counter(list-1) ') ';
    }
  }

  .ql-snow .ql-picker.ql-header {
    width: 120px;
  }

  .ql-snow .ql-picker.ql-header .ql-picker-item[data-value="1"]:before, 
  .ql-snow .ql-picker.ql-header .ql-picker-label[data-value="1"]:before {
      content: 'Заголовок 1';
  }

  .ql-snow .ql-picker.ql-header .ql-picker-item[data-value="2"]:before, 
  .ql-snow .ql-picker.ql-header .ql-picker-label[data-value="2"]:before {
      content: 'Заголовок 2';
  }

  .ql-snow .ql-picker.ql-header .ql-picker-item[data-value="3"]:before,
  .ql-snow .ql-picker.ql-header .ql-picker-label[data-value="3"]:before {
      content: 'Заголовок 3';
  }

  .ql-snow .ql-picker.ql-header .ql-picker-item:before, 
  .ql-snow .ql-picker.ql-header .ql-picker-label:before {
      content: 'Нормальный';
  }
}
</style>