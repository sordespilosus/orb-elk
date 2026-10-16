<template>
	<div class="page-profile">
		<div class="container page-profile__main">
			<base-tabs
				:modify="true"
				@input="changeComponent"
				:value="activeSection"
				:data="sections"
				label-name="section_name"
				value-name="section_value"
				class="page-profile__sections"/>

			<transition enter-active-class="animated fast fadeIn">
				<component
					@user:update="handleUserUpdate"
					:is="activeComponent"
					:serverData="serverDataCloned"
					:profiles.sync="profiles"
					:cities="cities"
					:organizations="userOrganizations"
					:isUserOrganization="isUserOrganization"
					:esiaInfo="esiaInfo"/>
			</transition>
		</div>

		<portal-target
			name="page-profile-buttons"
			class="page-profile__buttons"/>

		<modal
			v-if="isQrModalShown"
			@modal:close="isQrModalShown = false"
			title="Для входа в ГИС ЕЛК ОО на другом устройстве отсканируйте QR-код"
			max-width="450">
			<template #text>
				<qr :text="accessToken"/>
			</template>
		</modal>

	</div>
</template>

<script>
import {cloneDeep} from "lodash";

import BaseTabs from "@/js/components/BaseTabs";
import User from "@/js/components/User";
import Profiles from "@/js/components/Profiles";
import ChangePassword from "@/js/components/ChangePassword";
import Qr from "@/js/components/Qr";
import Modal from "@/js/components/Modal";
import MyOrganizations from "@/js/components/MyOrganizations";
import EsiaInfo from "@/js/components/EsiaInfo";

export default {
	name: "PageProfile",
	components: {
		BaseTabs,
		User,
		Profiles,
		ChangePassword,
		Qr,
		Modal,
		MyOrganizations,
		EsiaInfo,
	},

	props: {
		serverData: { type: Object, default: () => ({}) },
		serverSocial: { type: Array, default: () => [] },

		cities: { type: Array, required: true },

		userOrganizations: { type: Array, default: () => [] },
		isUserOrganization: { type: Boolean, required: true },
		esiaInfo: { type: Object, default: null },
	},

	data() {
		let activeSection = this.$route.hash ? this.$route.hash.replace("#", "") : "user";
		return {
			activeSection,
			profiles: cloneDeep(this.serverSocial),
			isQrModalShown: false,
			accessToken: null,
			serverDataCloned: cloneDeep(this.serverData),
		};
	},

	computed: {
		activeComponent() {
			let components = {
				user: "User",
				profiles: "Profiles",
				changePassword: "ChangePassword",
				myOrganizations: "MyOrganizations",
			};
			if (this.userOrganizations && this.userOrganizations.length) {
				components.myOrganizations = "MyOrganizations";
			}
			if (this.esiaInfo) {
				components.esiaInfo = "EsiaInfo";
			}
			return components[this.activeSection] || "User";
		},
		sections() {
			let sections = [
				{ section_name: "Учетные данные", section_value: "user" },
				{ section_name: "Профили", section_value: "profiles" },
				{ section_name: "Смена пароля", section_value: "changePassword" },
			];
			if (this.userOrganizations && this.userOrganizations.length)
				sections.push({
					section_name: "Мои организации",
					section_value: "myOrganizations",
				});
			if (this.esiaInfo)
				sections.push({
					section_name: "Данные из Госуслуг",
					section_value: "esiaInfo",
				});

			return sections;
		},
	},

	methods: {
		copyId() {
			this.$refs.user_id.select();
			document.execCommand("copy");
			this.$notify({ type: "success", text: "ID скопирован в буфер обмена" });
		},

		async openQr() {
			const { commit, dispatch } = this.$store;
			commit("setLoader", true);
			const response = await dispatch("fetchAccessToken");
			commit("setLoader", false);
			if (!response.status) {
				this.$notify({ type: "error", text: "Получить QR-код не удалось" });
				return;
			}
			this.accessToken = response.data;
			this.isQrModalShown = true;
		},

		changeComponent(component) {
			this.$router.replace({ ...this.$route, hash: `#${component}` });
			this.activeSection = component;
		},

		handleUserUpdate(userData) {
			this.serverDataCloned = cloneDeep(userData);
		},
	},
};
</script>

<style lang="scss">
@import "~@/sass/variables";

.page-profile {
	&__main {
		max-width: 500px;

		> * + * {
			margin-top: 20px;
		}
	}

	&__header {
		display: flex;
		align-items: flex-start;
	}

	&__title {
		flex: 1 1 auto;
		text-align: left;
	}

	&__qr-icon {
		flex: 0 0 auto;
		height: 35px;
		width: 35px;
		cursor: pointer;

		&:hover {
			color: $color-accent;
		}
	}

	&__link {
		background-image: linear-gradient(
				90deg,
				rgba(#0066cc, 0.3),
				rgba(#0066cc, 0.3) 75%,
				transparent 75%,
				transparent 100%
		);
		background-size: 6px 1px;
		background-repeat: repeat-x;
		background-position: bottom; // @todo: правильно написать эти стили
	}

	&__sections {
		.base-tabs__inner {
			margin-top: 5px;
		}

		.base-tabs__item {
			flex: 0 0 auto;
			min-width: 33.3333%;
			font-size: 13px;
			text-transform: uppercase;
			text-align: center;

			@media (max-width: $break_sm) {
				width: auto;
				font-size: 11px;
				padding-right: 1px;
			}
		}
	}

	&__buttons {
		position: sticky;
		bottom: 0;

		margin-top: 20px;
		z-index: 20;
		> * {
			max-width: 500px;
			margin-left: auto;
			margin-right: auto;
			padding: 20px;
		}

		&:empty {
			border-top: none;
		}
	}
}
</style>
