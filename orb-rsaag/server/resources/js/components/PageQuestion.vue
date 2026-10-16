<template>
<notification class="page-question">
  <template #title>Здравствуйте!</template>
  <template class="">
    <p>Вы впервые входите в личный кабинет через {{methodName}}.</p>
    <p>Возможно, ранее Вы уже входили используя другой сервис для авторизации</p>
    <p>Хотите объединить эти учётные записи?</p>

  </template>
  <template #footer >
    <div class="page-question__buttons">
      <form method="get" :action="`/register/add_to_user`" class="page-question__button">
        <input type="hidden" name="_token" :value="$store.getters.csrf">
        <button class="page-question__btn-node" v-text="'Уже входил, объединить'" />
      </form>
      <form method="post" action="/register/new_social" class="page-question__button">
        <input type="hidden" name="_token" :value="$store.getters.csrf">
        <button class="page-question__btn-node" v-text="'Вхожу впервые, зарегистрировать'" />
      </form>
    </div>
  </template>

</notification>

</template>

<script>
  import Notification from "./Notification";

export default {
  name: 'PageQuestion',
  components: { Notification },

  props: {
    method: { type: String },
  },

  data: () => ({

  }),

  computed: {
    methodName(){
      return {
        vk: 'Вконтакте',
        fb: 'Facebook',
        esia: 'Госуслуги'
      }[this.method] || '';
    }
  }
};
</script>

<style lang="scss">
.page-question {


  &__main {
    flex: 1 1 auto;
    max-width: 500px;

    > * + * {
      margin-top: 20px;
    }
  }
  
  &__btn-node{
   --accent-color: #06c;
    display: block;
    width: 100%;
    padding: 14px;
    border: 1px solid transparent;
    text-align: center;
    text-transform: uppercase;
    text-decoration: none;
    font-size: 13px;
    font-weight: 700;
    line-height: 20px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    outline: none;
    border-radius: 2px;
    cursor: pointer;
    background-color: white;
    color: black;
    border: 1px solid #00b42d;
    transition: .3s;
    &:hover {
      background-color: rgb(0, 200, 50);
    }

    
  }
  
  

  &__buttons {
    display: grid;
    gap: 20px;
    grid-template-columns: repeat(2, 1fr);
    align-items: center;
    background: white;
    color: black;
  }




}
</style>
