<template>
  <div class="change">
    <div class="change__title">
      <slot name="title"/>
    </div>
    <div class="change__values">
      <div class="change__old-value">
        <slot name="old_value"/>
      </div>
      <div class="change__delimiter">
        <slot name="delimiter">
          <icon name="arrow" class="change__arrow"/>
        </slot>
      </div>
      <div class="change__new-value">
        <slot name="new_value"/>
      </div>
    </div>
  </div>
</template>

<script>
  export default {
    name: "Change"
  }
</script>

<style lang="scss">
.change {
  &__values {
    display: flex;
    line-height: 20px;

    > * + * {
      margin-left: 10px;
    }
  }

  &__delimiter {
    display: flex;
    align-items: center;
    flex: 0 1 auto;
  }

  &__arrow {
    width: 20px;
    height: 20px;
  }

  &__old-value {
    color: #ff5555;
    flex: 0 1 auto;
  }

  &__new-value {
    color: #32BA91;
    flex: 0 1 auto;
  }
}
</style>