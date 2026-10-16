<template>
  <div class="profiles">
    <div class="profiles__inner">
      <template v-for="(profile, index) in profiles">
        <div
        v-if="profile.isActive"
        :key="profile.name"
        class="profiles__item">
          <img
          :src="require(`@/images/${profile.id}.svg`)"
          width="40"
          height="40"
          :alt="profile.name"
          class="profiles__image"/>

          <div
          v-html="`Профиль ${profile.name} подключен: ${profile.updatedAt}`"
          class="profiles__content"/>

          <div
          v-text="'Отключить'"
          @click="removeSocial(profile, index)"
          class="link profiles__link"/>
        </div>

        <div
        v-else
        :key="profile.name"
        class="profiles__item profiles__item--disabled">
          <img
          :src="require(`@/images/${profile.id}-gray.svg`)"
          width="40"
          height="40"
          :alt="profile.name"
          class="profiles__image"/>

          <div
          v-html="`Профиль ${profile.name} не подключен`"
          class="profiles__content"/>

          <a
          v-text="'Подключить'"
          :href="`${profile.url}?${$store.getters.redirectURL}`"
          class="link profiles__link"/>
        </div>
      </template>
    </div>
  </div>
</template>

<script>
import { set, cloneDeep } from 'lodash';

export default {
  name: 'Profiles',
  props: {
    profiles: { type: Array, required: true }
  },

  methods: {
    async removeSocial(profile, index) {
      const { commit, dispatch } = this.$store;

      commit('setLoader', true);
      const hasErrors = await dispatch('removeSocial', profile.id);
      commit('setLoader', false);

      if (!hasErrors) {
        const result = set(
          cloneDeep(this.profiles),
          `[${index}].isActive`,
          false
        );
        this.$emit('update:profiles', result);
      } else {
        this.$notify({ type: 'error', text: 'Ошибка при отключении профиля!' });
      }
    }
  }
};
</script>

<style lang="scss">
.profiles {
  &__inner {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    grid-auto-rows: 1fr;
    gap: 20px;
  }

  &__item {
    display: flex;
    flex-direction: column;
    align-items: center;

    padding: 19px 9px;
    border: 1px solid #fff;
    background-color: #fff;
    border-radius: 10px;

    > * + * {
      margin-top: 20px;
    }

    &--disabled {
      border-color: #d3d8db;
      background-color: transparent;
    }
  }

  &__image {
    flex: 0 0 auto;
    width: 40px;
    height: 40px;
  }

  &__content {
    flex: 1 1 auto;
    font-size: 11px;
    line-height: 16px;
    text-align: center;
  }

  &__link {
    flex: 0 0 auto;
    font-size: 13px;
    line-height: 20px;
  }
}
</style>
