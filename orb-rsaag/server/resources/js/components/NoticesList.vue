<template>
  <div class="notices-list">
    <div class="notices-list__inner">
      <touch-scroll>
        <div class="notices-list__clients">
          <div
              v-for="client in clients"
              :key="client.id"
              @click="setClient(client.id)"
              class="notices-list__client"
              :class="{'notices-list__client--active': client.id == clientId}">
            <div class="notices-list__client-image-container">
              <div
                  v-if="client.logo"
                  class="notices-list__client-image"
                  :style="`background-image: url(${client.logo});`"/>
              <icon v-else name="photo" class="notices-list__client-image-placeholder"/>
            </div>

            <div
                v-text="client.name"
                class="notices-list__client-name"/>
            <div
                v-if="client.notice_cnt"
                v-text="getNotices(client.notice_cnt)"
                class="notices-list__client-notices"/>
          </div>
        </div>
      </touch-scroll>

      <touch-scroll>
        <div class="notices-list__notices">

          <div
              v-for="notice in notices"
              :key="notice.notice_id"
              class="notices-list__notice"
              :class="{'notices-list__notice--new': notice.notice_new}">
            <div
                v-text="notice.notice_date"
                class="notices-list__notice-date"/>
            <div
                v-if="notice.notice_new"
                v-text="'Новое'"
                class="notices-list__notice-new"/>
            <div
                v-if="notice.notice_name"
                v-text="notice.notice_name"
                class="notices-list__notice-title"/>
            <div
                v-text="notice.notice_text"
                class="notices-list__notice-text"/>
            <a
                v-text="'Перейти'"
                :href="notice.notice_link"
                target="_blank"
                class="link notices-list__notice-link"/>
          </div>

          <div
              v-text="'Уведомлений нет'"
              v-if="!notices.length"
              class="heading-2 notices-list__empty-notices"/>

          <base-pagination
              v-if="totalPages > 1"
              :selected="page"
              :size="totalPages"
              @page:select="setPage"
              class="notices-list__pagination"/>
        </div>
      </touch-scroll>
    </div>

    <div class="notices-list__inner-mobile">
      <div
      v-if="!isShownNotices"
      class="notices-list__clients">
        <div
            v-for="client in clients"
            :key="client.id"
            @click="setClient(client.id)"
            class="notices-list__client"
            :class="{'notices-list__client--active': client.id == clientId}">
          <div class="notices-list__client-image-container">
            <div
                v-if="client.logo"
                class="notices-list__client-image"
                :style="`background-image: url(${client.logo});`"/>
            <icon v-else name="photo" class="notices-list__client-image-placeholder"/>
          </div>

          <div
              v-text="client.name"
              class="notices-list__client-name"/>
          <div
              v-if="client.notice_cnt"
              v-text="getNotices(client.notice_cnt)"
              class="notices-list__client-notices"/>
        </div>
      </div>

      <div
          v-if="isShownNotices"
          ref="notices"
          class="notices-list__notices">

        <div
            v-if="currentClient"
            class="notices-list__current-client notices-list__client--active notices-list__client">
          <div class="notices-list__client-image-container">
            <div
                v-if="currentClient.logo"
                class="notices-list__client-image"
                :style="`background-image: url(${currentClient.logo});`"/>
            <icon v-else name="photo" class="notices-list__client-image-placeholder"/>
          </div>

          <div
              v-text="currentClient.name"
              class="notices-list__client-name"/>

          <div
              v-if="currentClient.notice_cnt"
              v-text="getNotices(currentClient.notice_cnt)"
              class="notices-list__client-notices"/>

          <icon
              @click="closeClient"
              name="close"
              class="notices-list__client-close"/>
        </div>

        <div
            v-for="notice in notices"
            :key="notice.notice_id"
            class="notices-list__notice"
            :class="{'notices-list__notice--new': notice.notice_new}">
          <div
              v-text="notice.notice_date"
              class="notices-list__notice-date"/>
          <div
              v-if="notice.notice_new"
              v-text="'Новое'"
              class="notices-list__notice-new"/>
          <div
              v-if="notice.notice_name"
              v-text="notice.notice_name"
              class="notices-list__notice-title"/>
          <div
              v-text="notice.notice_text"
              class="notices-list__notice-text"/>
          <a
              v-text="'Перейти'"
              :href="notice.notice_link"
              target="_blank"
              class="link notices-list__notice-link"/>
        </div>

        <div
            v-text="'Уведомлений нет'"
            v-if="!notices.length"
            class="heading-2 notices-list__empty-notices"/>

        <base-pagination
            v-if="totalPages > 1"
            :selected="page"
            :size="totalPages"
            @page:select="setPage"
            class="notices-list__pagination"/>
      </div>

    </div>

  </div>
</template>
<script>

import { pickBy, find } from 'lodash';
import BasePagination from './BasePagination'
import TouchScroll from './TouchScroll'

export default {
  name: 'NoticeList',
  components: { BasePagination, TouchScroll },
  props: {
    clients: { type: Array, required: true },
  },

  data: () => ({
    clientId: null,
    notices: [],
    page: 1,
    perPage: 25,
    totalPages: 1,

    isShownNotices: false,
  }),

  computed: {
    queryParams() {
      const query = {
        page: this.page,
        per_page: this.perPage,
        client_id: this.clientId
      };
      const defaults = {
        page: 1,
        per_page: 25,
        client_id: null
      };

      return pickBy(query, (value, key) =>
          value != defaults[key]);
    },

    currentClient() {
      return find(this.clients, item => item.id == this.clientId);
    }
  },

  methods: {
    init(){
      this.clientId = this.$route.query.client_id || null;
      if (this.clientId){
        this.isShownNotices = true;
        this.fetchNotices();
      }
    },

    async fetchNotices(){
      const { dispatch, commit } = this.$store;
      commit('setLoader', true);
      const response = await dispatch('getNotices', {
        client_id: this.clientId,
        page: this.page,
        per_page: this.perPage
      });
      commit('setLoader', false);
      if (response && response.status){
        this.notices = response.data.notices;
        this.totalPages = response.data.totalPages;
        this.$emit('notices:update', { id: this.clientId, cnt: response.data.newNoticeCnt });
      }
      else{
        this.$notify({type: 'error', text: 'Получить уведомления не удалось'});
      }
    },

    setPage(page){
      this.page = page;
      this.$router.push({name: this.$route.name, query: this.queryParams});
      this.fetchNotices();
      this.$nextTick(() => {
        this.scrollTo();
      });
    },

    setClient(clientId){
      this.clientId = clientId;
      this.page = 1;
      this.$router.push({name: this.$route.name, query: this.queryParams});
      this.isShownNotices = true;
      this.fetchNotices();
      this.$nextTick(() => {
        this.scrollTo();
      });
    },

    getNotices(cnt){
      return cnt >= 100 ? '+99' : cnt;
    },

    scrollTo() {
      const element = this.$refs['notices'];
      if(element) {
        let { top } = element.getBoundingClientRect();
        const topOffset = 200; //оффсет задается навесающей шапкой сайта (80px) + 20px дополнительный отступ.
        top = pageYOffset + top - topOffset;
        window.scrollTo({ top, behavior: 'smooth'});
      }
    },

    closeClient(){
      this.isShownNotices = false;
      this.clientId = null;
    }

  },

  async beforeMount() {
    this.init();
  }
};
</script>
<style lang="scss">
@import '~@/sass/variables';

.notices-list {

  &__inner {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 0;
    background-color: #fff;
    @media (max-width: $break_md) {
      display: none;
    }
  }

  &__inner-mobile {
    background-color: #fff;
    @media (min-width: $break_md) {
      display: none;
    }
  }

  &__clients {
    border-right: 1px solid #D3D8DB;

    > * + * {
      &:before {
        content: '';
        position: absolute;
        top: 0;
        margin-left: -40px;
        height: 1px;
        background-color: #D3D8DB;
        width: calc(100% - 40px);
      }
    }
  }

  &__client {
    position: relative;
    padding: 20px 60px;
    cursor: pointer;

    &:hover {
      background-color: darken(#ffffff, 4%);
    }

    &--active {
      background-color: #0066CC;

      &:hover {
        background-color: lighten(#0066CC, 4%);
      }

      .notices-list__client-name {
        color: #fff;
      }
      .notices-list__client-notices {
        color: #0066CC;
        background-color: #fff;
      }
    }
  }

  &__client-image-container {
    position: absolute;
    top: 20px;
    left: 20px;
    width: 24px;
    height: 24px;
    padding: 5px;
    border-radius: 100%;
    background-color: #fff;
  }

  &__client-image {
    width: 14px;
    height: 14px;
    border-radius: 100%;
    background-size: contain;
    background-repeat: no-repeat;
    background-position: center;
  }

  &__client-image-placeholder {
    width: 14px;
    height: 14px;
    color: #7b898d;
  }

  &__client-name {
    flex: 1 1 auto;
    text-transform: uppercase;
    font-size: 13px;
    line-height: 24px;
    font-weight: bold;
    color: #3D3D3D;
  }

  &__client-notices {
    position: absolute;
    top: 20px;
    right: 20px;
    border-radius: 30px;
    font-size: 12px;
    line-height: 20px;
    background-color: #0066CC;
    color: #fff;
    padding: 0 5px;
    height: 20px;
    min-width: 20px;
    text-align: center;
  }

  &__current-client {
    border-radius: 10px;
    border: 1px solid #F0F3F5;
    padding-right: 65px;
    padding-left: 40px;
    cursor: auto;

    .notices-list__client-image-container {
      left: 10px;
    }

    .notices-list__client-notices {
      right: 35px;
    }
  }

  &__client-close {
    position: absolute;
    top: 20px;
    right: 10px;
    width: 20px;
    height: 20px;
    cursor: pointer;
    color: #fff;
  }

  &__notices {
    padding: 20px;

    > * + * {
      margin-top: 20px;
    }
  }

  &__notice {
    position: relative;
    padding: 20px;
    border-radius: 10px;
    border: 1px solid #F0F3F5;

    &--new {
      background-color: #F0F3F5;
    }
  }

  &__notice-date {
    color: #7B898D;
    font-size: 13px;
  }

  &__notice-new {
    position: absolute;
    right: 20px;
    top: 20px;
    text-transform: uppercase;
    color: #fff;
    padding: 0 5px;
    font-size: 12px;
    line-height: 20px;
    border-radius: 30px;
    background-color: #0066CC;
    font-weight: bold;
    height: 20px;
  }

  &__notice-title {
    font-weight: bold;
    text-transform: uppercase;
    margin-top: 20px;
    font-size: 13px;
    line-height: 20px;
  }

  &__empty-notices {
    text-align: center;
  }

  &__notice-text {
    margin: 20px 0 10px 0;
    font-size: 13px;
    line-height: 20px;
  }

  &__notice-link {
    font-size: 13px;
    line-height: 20px;
  }

}
</style>
