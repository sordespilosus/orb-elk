<template>
  <div class="base-tiny">
    <editor
      v-bind="$attrs"
      v-on="listeners"
      :value="value"
      :init="editorParams"
      class="base-tiny__textarea"
      :class="{ 'base-tiny__textarea_error': isError }"
      :api-key="'no-api-key'"
    />
  </div>
</template>
<script>
import Editor from "@tinymce/tinymce-vue";
import tinymce from "tinymce/tinymce";
import "tinymce/themes/silver";
import "tinymce/icons/default";

import "tinymce/plugins/advlist";
import "tinymce/plugins/autolink";
import "tinymce/plugins/lists";
import "tinymce/plugins/link";
import "tinymce/plugins/image";
import "tinymce/plugins/charmap";
import "tinymce/plugins/print";
import "tinymce/plugins/preview";
import "tinymce/plugins/anchor";
import "tinymce/plugins/searchreplace";
import "tinymce/plugins/visualblocks";
import "tinymce/plugins/code";
import "tinymce/plugins/fullscreen";
import "tinymce/plugins/insertdatetime";
import "tinymce/plugins/media";
import "tinymce/plugins/table";
import "tinymce/plugins/paste";

export default {
  components: { Editor },
  name: "BaseTiny",
  inheritAttrs: false,
  props: {
    value: { type: null },
    isError: { type: Boolean },
  },
  computed: {
    listeners() {
      return {
        ...this.$listeners,
        input: (event) => {
          this.$emit("input", event);
        },
      };
    },

    editorParams() {
      return {
        paste_as_text: true,
        //theme: 'modern',
        menubar: false,
        remove_script_host: false,
        relative_urls: true,
        convert_urls: false,
        document_base_url: process.env.APP_URL,
        valid_children: "+body[style]",
        //skin_url: '/tiny/skins',
        language_url: "/tiny/langs/ru.js",
        language: "ru",
        plugins: [
          "advlist autolink lists link image charmap print preview anchor",
          "searchreplace visualblocks code fullscreen",
          "insertdatetime media table paste code",
        ],
        toolbar:
          "undo redo | styleselect | bold italic | alignleft aligncenter alignjustify alignright | bullist numlist  | link | code preview",
      };
    },
  },
};
</script>
<style lang="scss">
.base-editor {
  &__inner {
    position: relative;
    width: 100%;
    height: 100%;
    min-height: 300px;
  }

  &__textarea {
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;

    width: 100%;

    padding: 19px;
    border: 1px solid #d3d8db;

    color: #3d3d3d;
    font-size: 18px;
    line-height: 20px;

    outline: none;
    resize: none;

    &:focus,
    &:hover {
      border-color: #32ba91;
    }

    &_error {
      border-color: #ff5555;

      &:hover,
      &:focus {
        border-color: #ff5555;
      }
    }

    &_error:disabled,
    &:disabled {
      pointer-events: none;

      background-color: #ebebe4;
      color: #999;
      border-color: #d3d8db;
    }
  }

  &__limit {
    position: absolute;
    left: 2px;
    bottom: 2px;

    padding: 0 20px;

    font-size: 13px;
    line-height: 30px;

    color: #3d3d3d;
    background-color: rgba(#fff, 0.8);
  }
}
</style>
