<template>
  <div
  class="image-loader"
  :style="`width: ${width}px;`"
  :class="getModifiers">
    <div class="image-loader__inner">
      <label :for="uniqueId" class="image-loader__label" :style="`background-image: url(${value.preview})`">
        <icon name="photo" class="image-loader__placeholder" v-show="!value.preview"/>
      </label>
      
      <icon 
      v-if="value.preview" 
      @click="deleteImage" 
      name="close" 
      class="image-loader__delete"/>
      
      <input
      :id="uniqueId"
      @change="loadImage"
      type="file"
      class="image-loader__input"
      :disabled="'disabled' in this.$attrs && this.$attrs.disabled !== false || 'readonly' in this.$attrs && this.$attrs.readonly !== false"/>
    </div>
  </div>
</template>
<script>
export default {
  name: 'ImageLoader',
  props: {
    value: { type: Object, required: true },
    width: { type: [String, Number], default: 100}
  },

  computed: {
    uniqueId() {
      return `image-loader-${Math.random().toString(16).slice(2)}`;
    },

    getModifiers(){
      return {
        'image-loader--disabled': 'disabled' in this.$attrs && this.$attrs.disabled !== false,
        'image-loader--readonly': 'readonly' in this.$attrs && this.$attrs.readonly !== false,
      }
    }
  },

  methods: {
    async loadImage(event) {
      const files = event.target.files;
      if(files.length) {
        const file = files[0],
              preview = await this.createPreview(files[0]);
        this.$emit('input', {file, preview});
        event.target.value = ''; //сразу же очищаем value, чтобы можно было прикреплять один файл дважды
      }
    },

    createPreview(image) {
      return new Promise(resolve => {
        const reader = new FileReader();
        reader.onload = () => resolve(reader.result);
        reader.readAsDataURL(image);
      });
    },

    deleteImage() {
      this.$emit('input', {});
    }
  } 
}
</script>
<style lang="scss">
.image-loader {
  &__inner {
    position: relative;
    padding-bottom: calc(80% - 2px);
    border: 1px solid #d3d8db;
    
    overflow: hidden;
    border-radius: 10px;
  }

  &__label {
    position: absolute;
    left: 0;
    right: 0;
    top: 0;
    bottom: 0;

    background-repeat: no-repeat;
    background-size: cover;
    background-position: center;

    cursor: pointer;
  }

  &__placeholder {
    position: absolute;
    left: 50%;
    top: 50%;
    transform: translate(-50%, -50%);

    width: 45px;
    height: 45px;

    color: #d3d8db;
  }

  &__input {
    display: none;
  }

  &__delete {
    position: absolute;
    top: 5px;
    right: 5px;

    padding: 2px;
    border: 1px solid #ededed;
    width: 15px;
    height: 15px;

    background-color: #fff;
    border-radius: 100%;
    cursor: pointer;
  }

  &--readonly, &--disabled {
    .image-loader__label {
      cursor: default;
    }
    .image-loader__delete {
      display: none;
    }
    .image-loader__input {
      pointer-events: none;
    }
  }

  &--disabled {
    opacity: 0.4;
  }
}
</style>