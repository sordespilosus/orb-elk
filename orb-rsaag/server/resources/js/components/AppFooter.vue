<template>
  <div class="app-footer">
    <div class="app-footer__inner">
      <a 
      href="mailto:ag@mail.orb.ru" 
      class="app-footer__contact">
        <icon 
        name="mail"
        class="app-footer__contact-icon"/>

        <div class="app-footer__contact-text">
          По всем вопросам, касающихся работы сервиса, а также возможности интеграции с сервисом, обращайтесь по адресу 
          <span
          v-text="'ag@mail.orb.ru'"
          class="link app-footer__contact-link"/>
        </div>
      </a>
    </div>
  </div>
</template>

<script>
export default {
  name: 'AppFooter'
};
</script>

<style lang="scss">
@import '~@/sass/variables';

.app-footer {
  &__inner {
    display: flex;
    align-items: center;
    justify-content: center;

    height: 70px;

    > * + * {
      margin-left: 30px;
    }

    @media (max-width: $break_md) {
      
      > * + * {
        margin-left: 0;
      }
    }
  }

  &__contact {
    flex: 1 1 auto;
    max-width: 400px;

    display: flex;
    align-items: center;

    color: #fff;
    opacity: 0.5;
    transition: opacity 0.3s;

    > * + * {
      margin-left: 10px;
    }

    &:hover {
      opacity: 1;
    }
  }

  &__contact-icon {
    flex: 0 0 auto;
  }

  &__contact-text {
    flex: 1 1 auto;

    font-size: 10px;
    line-height: 13px;
  }

  &__contact-link {
    color: inherit;
    text-decoration: underline;
  }
}
</style>