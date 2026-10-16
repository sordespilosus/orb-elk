<template>
  <div class="clients">
    <div class="clients__inner">
      <h2
      v-text="'К сервису уже подключены'"
      class="heading-2 clients__title"/>

      <carousel
      v-bind="carouselOptions"
      class="clients__list">
        <slide
        v-for="client in data"
        :key="client.id"
        class="clients__item">
          <img
          v-if="client.logo"
          :src="client.logo"
          :alt="client.name"
          class="clients__image"/>
          <icon v-else name="photo" class="clients__image-placeholder"/>

          <a
          v-text="client.name"
          :href="client.url_site"
          target="_blank"
          class="link clients__link"/>
        </slide>
      </carousel>
    </div>
  </div>
</template>

<script>
import { Carousel, Slide } from 'vue-carousel';

export default {
  name: 'Clients',
  components: { Carousel, Slide },

  props: {
    data: { type: Array, required: true }
  },

  data: () => ({
    carouselOptions: {
      perPageCustom: [
        [0, 1],
        [560, 2],
        [680, 3],
        [840, 4],
        [960, 5],
        [1100, 6]
      ],
      paginationEnabled: true,
      navigationEnabled: true,
      navigationPrevLabel: '',
      navigationNextLabel: '',
      centerMode: true
    }
  })
};
</script>

<style lang="scss">
@import '~@/sass/variables';

.clients {
  &__inner {
    padding: 20px 80px;
    padding-bottom: 35px;

    > * + * {
      margin-top: 20px;
    }

    @media (max-width: $break_sm) {
      padding-left: 20px;
      padding-right: 20px;
    }
  }

  &__title {
    text-align: center;
  }

  &__list {
  }

  &__item {
    min-height: 170px;
    padding: 20px 15px;

    display: flex;
    flex-direction: column;
    align-items: center;

    > * + * {
      margin-top: 20px;
    }
  }

  &__image {
    display: block;

    height: 60px;
    width: 60px;
    object-fit: contain;

    border-radius: 100%;
  }

  &__image-placeholder {
    display: block;
    height: 60px;
    width: 60px;
    padding: 5px;
    color: #7b898d;
  }

  &__link {
    font-size: 12px;
    line-height: 16px;
    text-align: center;
  }

  .VueCarousel-navigation-button {
    padding: 0 !important;
    margin: 0 !important;

    width: 40px;
    height: 40px;

    background-repeat: no-repeat;
    background-position: center;
    background-size: 20px;

    background-color: #f0f3f5;

    border-radius: 100%;

    opacity: 0.6;
    transition: opacity 0.3s;

    &:hover,
    &:focus {
      opacity: 1;
      outline: none;
    }
  }

  .VueCarousel-navigation {
    @media (max-width: $break_sm) {
      display: none;
    }
  }

  .VueCarousel-navigation-prev {
    background-image: url(data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxNiIgaGVpZ2h0PSIxNCIgdmlld0JveD0iMCAwIDE2IDE0Ij48ZGVmcz48c3R5bGU+LmF7ZmlsbDojN2I4OThkO308L3N0eWxlPjwvZGVmcz48cGF0aCBjbGFzcz0iYSIgZD0iTTIuMywxMC44NjZjLjEuMS4yLjEuMy4ybDUuNiw1LjcyM2EuOTUyLjk1MiwwLDAsMCwxLjQsMCwxLDEsMCwwLDAsMC0xLjQzMUw1LjQsMTEuMDcxSDE3YS45NTUuOTU1LDAsMCwwLDEtMS4wMjIuOTU1Ljk1NSwwLDAsMC0xLTEuMDIySDUuNWw0LjItNC4xOWExLDEsMCwwLDAsMC0xLjQzMS45NTIuOTUyLDAsMCwwLTEuNCwwTDIuNyw5LjEyOWMtLjEsMC0uMi4xLS4zLjJzLS4yLjItLjIuMzA3YS42NjkuNjY5LDAsMCwwLS4yLjUxMS42MjUuNjI1LDAsMCwwLC4xLjQwOUMyLjEsMTAuNjYyLDIuMiwxMC43NjQsMi4zLDEwLjg2NloiIHRyYW5zZm9ybT0idHJhbnNsYXRlKC0yIC0zLjEpIi8+PC9zdmc+);
  }

  .VueCarousel-navigation-next {
    background-image: url(data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxNiIgaGVpZ2h0PSIxNCIgdmlld0JveD0iMCAwIDE2IDE0Ij48ZGVmcz48c3R5bGU+LmF7ZmlsbDojN2I4OThkO308L3N0eWxlPjwvZGVmcz48cGF0aCBjbGFzcz0iYSIgZD0iTTE3LjcsMTAuODY2Yy0uMS4xLS4yLjEtLjMuMmwtNS42LDUuNzIzYS45NTIuOTUyLDAsMCwxLTEuNCwwLDEsMSwwLDAsMSwwLTEuNDMxbDQuMi00LjI5MkgzYS45NTUuOTU1LDAsMCwxLTEtMS4wMjJBLjk1NS45NTUsMCwwLDEsMyw5LjAyN0gxNC41bC00LjItNC4xOWExLDEsMCwwLDEsMC0xLjQzMS45NTIuOTUyLDAsMCwxLDEuNCwwbDUuNiw1LjcyM2MuMSwwLC4yLjEuMy4ycy4yLjIuMi4zMDdhLjY2OS42NjksMCwwLDEsLjIuNTExLjYyNS42MjUsMCwwLDEtLjEuNDA5QzE3LjksMTAuNjYyLDE3LjgsMTAuNzY0LDE3LjcsMTAuODY2WiIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoLTIgLTMuMSkiLz48L3N2Zz4=);
  }

  .VueCarousel-navigation--disabled {
    display: none;
  }

  .VueCarousel-pagination {
    display: none;

    @media (max-width: $break_sm) {
      display: block;
    }
  }

  .VueCarousel-dot-container {
    margin-top: 0 !important;
  }

  .VueCarousel-dot {
    margin-top: 0 !important;
    background-color: #d3d8db;

    &:hover,
    &:focus {
      outline: none;
      background-color: #2a3e4c !important;
    }
  }

  .VueCarousel-dot--active {
    background-color: #2a3e4c !important;
  }
}
</style>
