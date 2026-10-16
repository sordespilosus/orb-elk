<template>
	<div class="app-header">
		<div class="app-header__inner">
			<a
				href="/"
				class="app-header__logo">

				<img
					src="~@/images/logo_image.svg"
					alt="Региональный сервис аутентификации и авторизации гражданина Оренбургской области"
					width="auto"
					class="app-header__logo-image"/>

				<div
					v-text="'Цифровая экосистема Оренбуржья'"
					class="app-header__logo-title"/>
			</a>

			<app-user
				v-if="$store.getters.isAuth"
				class="app-header__user"/>
		</div>
	</div>
</template>

<script>
import AppUser from './AppUser';
import Modal from '@/js/components/Modal';

export default {
  name: 'AppHeader',
  components: { AppUser, Modal },
  data: () => ({
    isModalShown: false
  })
};
</script>

<style lang="scss">
@import '~@/sass/variables';

.app-header {
  &__inner {
    display: flex;
    align-items: center;
    justify-content: space-between;

    > * + * {
      margin-left: 20px;
    }
  }

  &__logo {
    flex: 1 1 auto;

    display: flex;
    align-items: center;

		@media (max-width: $break-md) {
			div {
				font-size: 12px;
				line-height: 13px;
			}

			img {
				width: 35px;
			}
		}

    > * + * {
      margin-left: 15px;
    }
  }

  &__logo-image {
    flex: 0 0 auto;
    display: block;
		width: 40px;
		height: 50px;
	}

  &__logo-title {
    flex: 1 1 auto;
		font-size: 20px;
		text-align: center;
		font-weight: 700;
	}

  &__help {
    flex: 0 0 auto;

    color: $color-accent;
    cursor: pointer;
  }

  &__user {
    flex: 0 0 auto;
  }
}
</style>
