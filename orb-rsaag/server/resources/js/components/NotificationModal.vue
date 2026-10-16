<template>
  <modal
  v-on="$listeners"
  v-bind="$attrs"
  class="notification-modal">

    <template #title>
      <div class="modal__title notification-modal__title">
        <h2
        v-html="`Заявление ${data.service_number}`"
        class="heading-2 notification-modal__subtitle"/>

        <div class="notification-modal__icons">
          <icon
          @click="refreshData"
          name="refresh"
          class="notification-modal__icon notification-modal__icon--refresh"/>

          <router-link
          v-if="data.body_id"
          :to="{ name: 'notificationPrint', params: { id: data.service_number }, query: { body_id: data.body_id, print: 1 } }"
          target="_blank"
          class="notification-modal__icon">
            <icon name="print"/>
          </router-link>
        </div>
      </div>
    </template>

  <template #text>
    <div class="modal__text notification-modal__content">
      <div class="notification-modal__list">
        <div
        v-for="item in data.notification"
        :key="item.value"
        class="notification-modal__item">
          <div
          v-text="item.name"
          class="notification-modal__item-title"/>
          <div
          class="notification-modal__item-text">
            {{item.value}}
            <router-link
            v-if="data.body_id && item.name == 'Единый номер обращения'"
            :to="{ name: 'notificationPrint', params: { id: data.service_number }, query: { body_id: data.body_id} }"
            target="_blank"
            v-text="'Скачать сведения из заявления'"
            class="link"/>
          </div>
        </div>
      </div>

      <div
      v-if="data.statusHistory"
      class="notification-modal__history">
        <div
        v-text="'История обработки'"
        class="notification-modal__history-title"/>

        <div class="notification-modal__history-list">
          <div
          v-for="item in data.statusHistory"
          :key="item.status_id"
          class="notification-modal__history-item"
          :class="{
            'notification-modal__history-item--positive': item.status_code == 1075,
            'notification-modal__history-item--negative': item.status_code == 1080
          }">
            <div class="notification-modal__history-header">
              <div
              v-text="item.status_name"
              class="notification-modal__history-status"/>
              <div
              v-text="item.status_date"
              class="notification-modal__history-date"/>
            </div>
            <div
            v-if="item.note"
            v-text="item.note"
            class="notification-modal__history-section"/>
            <div
            v-if="item.docs"
            class="notification-modal__history-section">
              <div
              v-text="'Прикрепленные документы'"
              class="notification-modal__history-section-title"/>
              <div
              v-for="doc in item.docs">
                  <a v-for="file in doc.files"
                  target="_blank"
                  v-text="file.file_name"
                  :href="file.file_link"
                  class="link notification-modal__history-section-link"/>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </template>

  </modal>
</template>

<script>
import Modal from '@/js/components/Modal';

export default {
  name: 'NotificationModal',
  components: { Modal },
  props: {
    data: { type: Object, required: true }
  },

  methods: {
    refreshData(){
      this.$emit("modal:refresh", this.data.service_number);
    },

    close() {
      this.$emit('modal:close', false);
    },
  }
};
</script>

<style lang="scss">
.notification-modal {
  &__content > * + * {
    margin-top: 30px;
  }

  &__title {
    position: relative;
    margin-right: 60px;
    padding-right: 0;
    border-bottom: none;

    > * {
      display: inline-block;
    }

    @media (max-width: 680px) {
      > * {
        display: block;
        padding-bottom: 0;
      }
    }
  }

  &__subtitle {
    margin-right: 80px;
  }

  &__icons {
    position: absolute;
    right: 0;
    top: 0;

    @media (max-width: 800px) {
      float: none;
      margin-right: 0;
    }

    > * {
      display: inline-block;
    }
  }

  &__icon {
    padding: 5px;
    height: 30px;
    width: 30px;

    &--refresh {
      cursor: pointer;
    }
  }

  &__content {
    margin-top: 0;
    padding-top: 20px;
    border-top: 1px solid #d3d8db;
  }

  &__list > * + * {
    margin-top: 20px;
  }

  &__item > * + * {
    margin-top: 10px;
  }

  &__item-title {
    text-transform: uppercase;
    font-size: 13px;
    line-height: 15px;
    font-weight: bold;
    color: #7b898d;
  }

  &__item-text {
    font-size: 15px;
    line-height: 20px;
  }

  &__history > * + * {
    margin-top: 20px;
  }

  &__history-title {
    font-size: 13px;
    line-height: 15px;
    font-weight: bold;
    text-transform: uppercase;
  }

  &__history-list > * + * {
    margin-top: 10px;
  }

  &__history-item {
    padding: 10px;
    background-color: #f0f3f5;
    border-radius: 10px;

    > * + * {
      margin-top: 10px;
    }

    &--positive {
      .notification-modal__history-header {
        color: #32ba91;
      }
    }

    &--negative {
      .notification-modal__history-header {
        color: #ff5555;
      }
    }
  }

  &__history-header {
    display: flex;
    align-items: flex-start;

    > * + * {
      margin-left: 20px;
    }

    @media (max-width: 680px) {
      flex-direction: column;

      > * + * {
        margin-left: 0;
      }
    }
  }

  &__history-status {
    flex: 1 1 auto;

    font-size: 15px;
    line-height: 20px;
    font-weight: bold;
    text-transform: uppercase;
  }

  &__history-date {
    flex: 0 0 auto;

    font-size: 15px;
    line-height: 20px;
    color: #7b898d;
  }

  &__history-section {
    display: flex;
    flex-direction: column;
  }

  &__history-section-title {
    flex: 0 0 auto;

    font-size: 15px;
    line-height: 20px;
    color: #7b898d;
  }

  &__history-section-link {
    flex: 0 0 auto;

    font-size: 15px;
    line-height: 20px;
  }
}
</style>
