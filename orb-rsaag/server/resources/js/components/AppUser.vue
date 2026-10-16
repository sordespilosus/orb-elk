<template>
  <div class="app-user">
    <div class="app-user__inner">
      <div class="app-user__navigation">
        <a
        v-for="link in $store.state.headerLinks"
        v-text="link.name"
        :key="link.name"
        :href="link.url"
        class="app-user__navigation-item"
        :class="{'app-user__navigation-item--active': link.active}"/>
      </div>

      <base-popover class="app-user__popover">
        <template #trigger>
          <div
          class="app-user__trigger"
          name="user-trigger">
            <div
            v-text="$store.state.user.fio"
            class="app-user__name"/>

            <icon
            name="user"
            class="app-user__icon"/>
          </div>
        </template>

        <template #default="{ data: { hide } }">
          <div class="app-user__menu">
            <a
            v-for="link in $store.state.userLinks"
            @click="hide"
            :key="link.name"
            :href="link.url"
            class="app-user__link">
              <icon
              :name="link.icon"
              class="app-user__link-icon"/>

              <div
              v-text="link.name"
              class="app-user__link-text"/>
            </a>
          </div>

          <div class="app-user__mobile-menu">
            <a
            v-for="link in $store.state.mobileUserLinks"
            @click="hide"
            :key="link.name"
            :href="link.url"
            class="app-user__link">
              <icon
              :name="link.icon"
              class="app-user__link-icon"/>

              <div
              v-text="link.name"
              class="app-user__link-text"/>
            </a>
          </div>
        </template>
      </base-popover>
    </div>
  </div>
</template>

<script>
import BasePopover from '@/js/components/BasePopover';

export default {
  name: 'AppUser',
  components: { BasePopover }
};
</script>

<style lang="scss">
@import '~@/sass/variables';

.app-user {
  &__inner {
    display: flex;
    align-items: center;
  }

  &__navigation {
    flex: 1 1 auto;
    align-self: stretch;

    margin-right: 20px;

    display: flex;

    > * + * {
      margin-left: 20px;
    }

    @media (max-width: $break_xl) {
      display: none;
    }
  }

	&__navigation-item {
		position: relative;
		text-transform: uppercase;
		color: #7b898d;
		font-weight: 600;
		flex: 0 0 auto;
		font-size: 12px;
		line-height: 58px;

		&:after {
			content: "";
			width: 100%;
			border-bottom: 2px solid transparent;
			position: absolute;
			transform: translateX(-50%);
			bottom: 0;
			left: 50%;
		}

		&--active {
			color: #2a3e4c;

			&:after {
				content: "";
				width: 100%;
				border-bottom: 2px solid #2a3e4c;
				position: absolute;
				transform: translateX(-50%);
				bottom: 0;
				left: 50%;
			}
		}

    &:hover {
      color: #2a3e4c;
    }
  }

	&__menu {
		@media (max-width: $break_xl) {
			display: none;
		}
	}

  &__mobile-menu {
    display: none;

    @media (max-width: $break_xl) {
      display: block;
    }
  }

	&__name {
		font-size: 13px;
		line-height: 20px;
		text-transform: uppercase;
		color: #2a3e4c;
		font-weight: 600;
	}

  &__icon {
    display: none;

    @media (max-width: $break_md) {
      display: block;
    }
  }

  &__name {
    @media (max-width: $break_md) {
      display: none;
    }
  }

  &__link {
    display: flex;
    align-items: center;
    width: 100%;
    padding: 15px 20px;
    color: #7b898d;

    &:hover {
      background-color: rgba(#f0f3f5, 0.7);
    }

    > * + * {
      margin-left: 20px;
    }
  }

  &__link-icon {
    flex: 0 0 auto;
  }

  &__link-text {
    flex: 1 1 auto;

    font-size: 13px;
    line-height: 20px;
    font-weight: bold;

    text-transform: uppercase;
    white-space: nowrap;
  }
}
</style>
