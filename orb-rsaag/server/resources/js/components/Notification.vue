<template>
<div
class="notification"
:class="{'notification_error': type === 'error', 'notification_info': type === 'info'}">
  <icon class="notification__icon" :name="type === 'success' ? 'success' : 'error'"/>
  <h2 class="heading-2 notification__title"><slot name="title"/></h2>
  <div class="wysiwyg notification__content"><slot/></div>
  <div class="notification__footer">
    <slot name="footer">
      Для продолжения работы перейдите на <a href="/" class="link">главную страницу</a>.
    </slot>
  </div>
</div>
</template>
<script>
export default {
  name: 'Notification',
  props: {
    type: { type: String, default: 'success' }
  }
}
</script>
<style lang="scss">
.notification {
  max-width: 560px;
  padding: 20px;
  margin: 10px auto;

  text-align: center;
  background-color: #fff;
  border-radius: 10px;

  flex: none;

  &__icon {
    margin: 0 auto;
    margin-bottom: 20px;
    width: 40px;
    height: 40px;
    color: #1abc9c;

    .notification_error & {
      color: #ff5555;
    }

    .notification_info & {
      color: #3AB5EA;
    }
  }

  &__title {
    margin-bottom: 10px;
  }

  &__content {
    margin-bottom: 10px;
  }

  &__footer {
    padding-top: 20px;
    border-top: 1px solid #ccc;
  }
}
</style>

