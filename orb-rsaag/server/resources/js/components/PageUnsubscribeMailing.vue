<template>
  <div class="page-unsubscribe-mailing">
    <template v-if="!message">
      <template v-if="!unsubscribe">
        <h2 class="heading-2 page-unsubscribe-mailing__title">
          Подтверждение отказа от рассылки {{ unsubscribed_email }}
        </h2>
        <div class="page-unsubscribe-mailing__confirm">
          <button @click="sendData" class="button button--positive">Подтвердить</button>
          <a href="/" class="button button--negative"> Отменить </a>
        </div>
      </template>
      <notification v-else type="error" :is-error="true">
        <template #title>Ошибка отказа от рассылки</template>
        <template>Возможно вы уже отписались от рассылки</template>
      </notification>
    </template>
    <notification v-else type="success">
      <template #title>Успешная отписка от рассылки</template>
      <template>{{ message }}</template>
    </notification>
  </div>
</template>

<script>
import Notification from "./Notification";
import FormField from "./FormField";
import { cloneDeep } from "lodash";

export default {
  name: "PageUnsubscribeMailing",
  components: { Notification, FormField },

  props: {
    isUnsubscribe: { type: Boolean, default: false },
    serverErrors: { type: Object },
    serverData: { type: Object },
    successMessage: { type: String },
    token: { type: String },
    email: { type: String },
  },

  data() {
    return {
      unsubscribe: this.isUnsubscribe,
      unsubscribed_email: cloneDeep(this.email),
      message: cloneDeep(this.successMessage),
    };
  },

  methods: {
    async sendData() {
      const { dispatch, commit } = this.$store;

      commit("setLoader", true);
      const response = await dispatch("unsubscribed", {
        mail: this.email,
        unsubscribe: true,
      });
      commit("setLoader", false);

      if (response.status_id) {
        this.message = response.successMessage;
      } else {
        this.unsubscribe = response.unsubscribed;
      }
    },
  },
};
</script>

<style lang="scss">
.page-unsubscribe-mailing {
  max-width: 520px;
  margin: 0 auto;

  &__title {
    text-align: center;
    margin-bottom: 20px;
  }

  &__confirm {
    * {
      margin: 0 0 10px;
    }
  }

  &__field {
    margin-bottom: 20px;
  }
}
</style>
