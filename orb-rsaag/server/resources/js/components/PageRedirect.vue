<template>
<notification class="page-redirect" type="info">

  <template #title>Внимание!</template>

  <template>
    <p>Вход в систему <span class="page-redirect--bold">{{client.name}}</span> осуществляется через Госуслуги.</p>
    <p>Вы будете перенаправлены для входа в Госуслуги через <span class="page-redirect--bold">{{counter}} сек.</span></p>
  </template>

  <template #footer>Нажмите <a class="link" href="/loginESIA">здесь</a> для мгновенного перехода.</template>

</notification>
</template>

<script>
import Notification from "./Notification";

export default {
  name: 'PageRedirect',
  components: { Notification },

  props: {
    client: { type: Object }
  },

  data: () => ({
    counter: 10
  }),

  methods:{
    countDown(){
      if (this.counter){
        return setTimeout(() => {
          this.counter--;
          this.countDown();
        }, 1000)
      }
      window.location.href = `/loginESIA`;
    }
  },

  mounted() {
    this.countDown();
  }
};
</script>

<style lang="scss">
.page-redirect {

  &__main {
    flex: 1 1 auto;
    max-width: 500px;

    > * + * {
      margin-top: 20px;
    }
  }

  &__buttons {
    display: grid;
    gap: 20px;
    grid-template-columns: repeat(2, 1fr);
    align-items: center;
  }

  &--bold {
    font-weight: bold;
  }


}
</style>
