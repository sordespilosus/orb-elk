<template>
	<div
		class="app"
		:class="appModifiers">

		<div
			v-if="!$store.state.isHeaderHidden"
			class="app__header">
			<app-header class="container"/>
			<a
				v-if="hasNotifyProfile"
				href="/profile"
				class="app__header-notify">
				<icon
					name="error"
					class="app__header-notify-icon"/>
				<div>Заполните адрес электронной почты в профиле, чтобы пользоваться всеми
					функциями
				</div>
			</a>

			<a
				v-if="$store.state.appCircle === 'preproduction'"
				href="https://cabinet.permkrai.ru"
				class="app__header-notify app__header-notify-preproduction">
				<icon
					name="error"
					class="app__header-notify-icon"/>
				<div>ВНИМАНИЕ! Вы находитесь на тестовом портале ГИС ЕЛК ОО. Для входа в
					электронные системы Оренбургской области перейдите на сайт <span
						class="link">lk.orb.ru</span></div>
			</a>

			<div
				v-if="!issetCookie && !!$store.state.alarmText"
				class="app__header-notify app__header-notify-preproduction">
				<icon
					name="error"
					class="app__header-notify-icon"/>
				<div v-text="$store.state.alarmText"/>
				<icon
					v-if="!!$store.state.alarmCookieName"
					name="close"
					@click="setAlarmCookie"
					class="app__header-notify-close-icon"/>
			</div>
		</div>

		<div class="app__main">
			<slot/>
		</div>

		<div class="app__footer" v-if="!$store.state.isFooterHidden">
			<app-footer class="container"/>
		</div>

		<alerts
			:duration="4000"
			position="bottom right"/>

		<portal-target
			@change="lockScroll"
			:transition="transitionComponent"
			name="modals"
			multiple/>

		<spinner v-if="$store.state.isLoading"/>
	</div>
</template>
<script>
import AppHeader from "@/js/components/AppHeader";
import AppFooter from "@/js/components/AppFooter";
import Modal from "@/js/components/Modal";
import Alerts from "@/js/components/Alerts";
import Spinner from "@/js/components/Spinner";
import {setCookie, getCookie} from "@/js/helpers";

export default {
	name: "App",
	props: {
		vuexData: { type: Object },
	},

	components: { AppHeader, AppFooter, Modal, Alerts, Spinner },

	data() {
		return {
			issetCookie: false,
		};
	},

	computed: {

		hasNotifyProfile() {
			return this.$store.state.user && Object.keys(this.$store.state.user).length && !this.$store.state.user.email;
		},

		appModifiers() {
			// необходимо поддерживать как адаптивную, так и статичную версию сайта (пока - только админка)
			// для этого присваиваем модификатор static там, где адаптив не нужен
			const isAdminPage = this.$route.path.includes("/admin");
			const { isAuth } = this.$store.getters;
			const isCompletedProfile = this.hasNotifyProfile;
			return {
				"app--static": isAdminPage,
				"app--not-auth": !isAuth,
				"app--with-notify": isCompletedProfile,
			};
		},

		transitionComponent() {
			return {
				functional: true,
				render(h, context) {
					const props = {
						enterActiveClass: "animated faster fadeIn",
						leaveActiveClass: "animated faster fadeOut",
					};

					return h("transition-group", { props }, context.children);
				},
			};
		},
	},

	methods: {
		init() {
			this.setVuexData();
			if (!!this.$store.state.alarmCookieName) {
				const cookie = getCookie(this.$store.state.alarmCookieName);
				this.issetCookie = cookie === 1;
			}

		},

		setVuexData() {
			Object.entries(this.vuexData).forEach(([path, value]) => {
				this.$store.commit("setData", { path, value });
			});
		},

		lockScroll(hasModals) {
			document.body.classList.toggle("overflow-hidden", hasModals);
		},

		setAlarmCookie() {
			setCookie(this.$store.state.alarmCookieName, 1);
			this.issetCookie = true;
		},
	},

	created() {
		this.init();
	},
};
</script>
<style lang="scss">
@import "~@/sass/variables";

.app {
	position: relative;

	display: flex;
	flex-direction: column;

	min-height: 100%;
	padding-top: 90px;
	padding-bottom: 70px;

	&--with-notify {
		padding-top: 120px;
		@media (max-width: $break_md) {
			padding-top: 140px;
		}
		@media (max-width: $break_xs) {
			padding-top: 160px;
		}
	}

	&__header {
		flex: 0 0 auto;

		position: fixed;
		left: 0;
		right: 0;
		top: 0;
		z-index: 100;

		background-color: #fff;
		box-shadow: 0 3px 26px rgba(0, 0, 0, 0.16);
	}

	&__main {
		flex: 1 1 auto;

		display: flex;
		flex-direction: column;

		> * {
			flex: 1 1 auto;
		}
	}

	&__footer {
		flex: 0 0 auto;

		position: absolute;
		left: 0;
		right: 0;
		bottom: 0;
		background-color: #7b898d;
	}

	&--not-auth {
		.app__header .container {
			max-width: 500px;
		}

		.app-header {
			padding: 7px 0;

			&__logo-title {
				margin: 0;
			}
		}

		.app__footer {
			background-color: #f0f3f5;
		}

		.app-footer__contact {
			color: #7b898d;
			opacity: 0.6;
		}

		.app-footer__contact:hover {
			opacity: 1;
		}
	}

	&--static,
	&--static .container {
		min-width: 1200px;
	}

	&__header-notify {
		display: flex;
		justify-content: center;
		align-items: center;
		line-height: 20px;
		padding: 5px;
		text-align: center;
		background-color: #0066cc;
		color: #fff;

		> * + * {
			margin-left: 10px;
		}
	}

	&__header-notify-icon {
		width: 20px;
		height: 20px;
		@media (max-width: $break_md) {
			display: none;
		}
	}

	&__header-notify-preproduction {
		color: #2A3E4C;
		background-color: #ffec44;
	}

	&__header-notify-close-icon {
		width: 20px;
		height: 20px;
		cursor: pointer;

		&:hover {
			color: lighten(#2A3E4C, 4%);
		}
	}
}

// адаптив для ui-компонентов делаю здесь,
// чтобы не загрязнять их (@todo: вынести в отд. css)
.modal {
	@media (max-width: $break_sm) {
		padding-left: 20px;
		padding-right: 20px;

		&__title {
			text-align: left;
		}

		&__body {
			width: 100%;
		}
	}
}

.overflow-hidden {
	overflow: hidden !important;
}
</style>
