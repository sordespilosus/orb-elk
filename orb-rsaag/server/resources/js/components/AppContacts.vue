<template>
  <div class="app-contacts">
    <div class="app-contacts__inner">
      <span class="app-contacts__text">По всем вопросам, касающихся работы сервиса, а также возможности интеграции с сервисом, обращайтесь по адресу</span>
      <a href="mailto:ag@mail.orb.ru" class="app-contacts__link">ag@mail.orb.ru</a>
    </div>
  </div>
</template>
<script>
export default {
  name: 'AppContacts'
}
</script>
<style lang="scss">
@import '../../sass/variables';

.app-contacts {
  &__inner {
    font-size: 14px;
  }

  &__text {
    .app_adaptive & {
      @media screen and (max-width: $break_xs) {
        display: none;
      }
    }
  }

  &__link {
    text-decoration: underline;
    cursor: pointer;
    white-space:nowrap
  }
}

</style>

