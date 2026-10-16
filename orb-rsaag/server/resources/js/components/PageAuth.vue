<template>
	<div class="page-auth">

		<div class="container page-auth__main">

			<div
				v-text="`Единый личный кабинет для всех сервисов`"
				class="page-auth__client"/>

			<base-tabs
				v-model="sectionsModel"
				:data="sections"
				value-name="section_value"
				label-name="section_name"
				class="page-auth__sections"/>

			<transition enter-active-class="animated fast fadeIn">
				<component
					:is="activeComponent"
					v-bind="attrs"
					class="page-auth__content"/>
			</transition>
		</div>

		<portal-target
			name="page-auth-buttons"
			class="page-auth__buttons"/>
	</div>
</template>

<script>
import {noop, pick} from "lodash";

import BaseTabs from "@/js/components/BaseTabs";
import Login from "@/js/components/Login";
import Register from "@/js/components/Register";
import Clients from "@/js/components/Clients";

export default {
	name: "PageAuth",
	components: { BaseTabs, Login, Register, Clients },

	props: {
		cities: { type: Array, required: true },
		clients: { type: Array, required: true },
		serverData: { type: Object, default: () => ({}) },
		serverErrors: { type: Object, default: () => ({}) },
		clientName: { type: String },
		isProfileVerified: { type: Boolean },
		registerData: { type: Object, default: () => ({}) },
		onlyLogin: { type: Boolean, default: false },
		authMethods: { type: Array, default: () => (["vk", "fb", "login", "esia"]) },
		withoutMethod: { type: String, default: null },
		withoutRegistration: { type: Boolean, default: false },
		clientId: { type: Number },
	},

	computed: {
		sectionsModel: {
			get() {
				return this.$route.name;
			},

			set(name) {
				this.$router.push({ name }, noop);
			},
		},

		activeComponent() {
			const { name } = this.$route;
			return name === "register" ? "Register" : "Login";
		},

		attrs() {
			return this.$route.name === "register"
				? pick(this, ["cities", "registerData", "clientId"])
				: pick(this, [
					"serverData",
					"serverErrors",
					"clientName",
					"isProfileVerified",
					"withoutMethod",
					"authMethods",
					"onlyLogin",
				]);
		},

		sections() {
			return this.withoutRegistration ? [{
				section_name: "Вход",
				section_value: "login",
			}] : [
				{ section_name: "Вход", section_value: "login" },
				{ section_name: "Регистрация", section_value: "register" },
			];
		},
	},
};
</script>

<style lang="scss">
.page-auth {
	display: flex;
	flex-direction: column;
	justify-content: stretch;

	&__main {
		flex: 1 1 auto;
		max-width: 500px;

		> * + * {
			margin-top: 20px;
		}
	}

	&__sections {
		.base-tabs__item {
			flex: 1 0 auto;
			width: 50%;

			font-size: 14px;
			line-height: 30px;
			//text-transform: uppercase;
			text-align: center;
		}
	}

	&__buttons {
		flex: 0 0 auto;

		position: sticky;
		bottom: 0;

		margin-top: 20px;

		> * {
			max-width: 500px;
			margin-left: auto;
			margin-right: auto;
			padding: 20px;
		}

		&:empty {
			display: none;
		}

		&:empty + * {
			margin-top: 20px;
		}
	}

	&__client {
		text-align: center;
		margin: 40px 0 30px 0;
		color: #2A3E4C;
		font-size: 26px;
		line-height: 40px;
		font-weight: 400;
	}
}
</style>
