<template>
	<div class="page-password-reset">
		<template v-if="!successMessage">
			<template v-if="!isEmailSent">
				<h2 class="heading-2 page-password-reset__title">Сброс пароля</h2>
				<form
					@submit.prevent="sendForm"
					action="/password/email"
					method="POST"
					class="page-password-reset__form">
					<input type="hidden" name="_token" :value="$store.getters.csrf"/>
					<form-field
						:errors="getErrors('email')"
						label="E-mail"
						class="page-password-reset__field">
						<base-input
							v-model="formData.email"
							v-validate="'required|email'"
							:isError="!!getErrors('email').length"
							name="email"
							placeholder="E-mail"/>
					</form-field>
					<button class="button">Сбросить пароль</button>
				</form>
			</template>

			<template v-else>
				<template v-if="serverErrors.email === null">

					<h2 class="heading-2 page-password-reset__title">
						{{ "Восстановление доступа для " + email }}</h2>
					<form
						@submit.prevent="sendForm"
						action="/password/reset"
						method="POST"
						class="page-password-reset__form">
						<input type="hidden" name="_token" :value="$store.getters.csrf"/>
						<input type="hidden" name="email" :value="email"/>
						<div
							v-for="error in getErrors('email')"
							:key="error"
							class="page-password-reset__error">
							{{ error }}
						</div>
						<input type="hidden" name="token" :value="token"/>
						<form-field
							:errors="getErrors('password')"
							label="Новый пароль"
							class="page-password-reset__field">
							<base-input
								v-model="formData.password"
								v-validate="'required|password'"
								:isError="!!getErrors('password').length"
								name="password"
								data-vv-as="Пароль"
								type="password"
								placeholder="Пароль"/>
						</form-field>

						<form-field
							:errors="getErrors('password_confirmation')"
							label="Подтверждение нового пароля"
							class="page-password-reset__field">
							<base-input
								v-model="formData.password_confirmation"
								v-validate="{required: true, password_confirmation: formData.password, password: true}"
								:isError="!!getErrors('password_confirmation').length"
								name="password_confirmation"
								data-vv-as="Подтверждение пароля"
								type="password"
								placeholder="Подтверждение пароля"/>
						</form-field>

						<button class="button">Установить новый пароль</button>

					</form>
				</template>
				<notification v-else :is-error="true">
					<template #title>Ошибка установки нового пароля</template>
					<template>{{ getErrors("email").join(", ") }}</template>
				</notification>
			</template>
		</template>

		<notification v-else>
			<template #title>Установка нового пароля</template>
			<template>{{ successMessage }}</template>
		</notification>
	</div>


</template>
<script>
import Notification from "./Notification";
import BaseInput from "./BaseInput";
import FormField from "./FormField";
import {cloneDeep} from "lodash";

export default {
	name: "PagePasswordReset",
	components: { Notification, BaseInput, FormField },
	props: {
		isEmailSent: { type: Boolean, default: false },
		serverErrors: { type: Object },
		serverData: { type: Object },
		successMessage: { type: String },
		token: { type: String },
		email: { type: String },
	},

	data() {
		return {
			formData: {
				...cloneDeep(this.serverData),
				password: null,
				password_confirmation: null,
			},
		};
	},

	methods: {

		init() {
			if (!this.formData.email && this.$route.query.email) {
				this.formData.email = this.$route.query.email;
			}
		},

		getErrors(field) {
			return this.$store.getters.getErrors(field, this.veeErrors, this.serverErrors);
		},

		async sendForm(event) {
			const form = event.target;
			const hasNoErrors = await this.$validator.validateAll();
			if (hasNoErrors) {
				const { commit } = this.$store;
				commit("setLoader", true);
				form.submit();
			}
		},
	},

	mounted() {
		this.init();
	},
};
</script>
<style lang="scss">
.page-password-reset {
	max-width: 520px;
	margin: 0 auto;

	&__title {
		text-align: center;
		margin-bottom: 20px;
	}

	&__form {}

	&__field {
		margin-bottom: 20px;
	}

	&__error {
		width: 100%;

		//font-size: 12px;
		//line-height: 15px;
		color: #ff5555;

		//white-space: nowrap;
		//overflow: hidden;
		//text-overflow: ellipsis;
	}
}
</style>
