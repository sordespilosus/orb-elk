<template>
	<div class="user">
		<div class="user__inner">
			<div class="user__inner--about">
				<div class="wrapped">
					<div class="profile-avatar">
						<div class="profile-avatar__avatar-container">
							<div class="profile-avatar__avatar-change">
								<label
									class="label"
									:class="{ 'label-empty': !(!!showAvatar) }">
									<img
										v-if="!!showAvatar"
										:src="showAvatar"
										class="preview-image"
										alt="Preview Image"/>
									<span v-else class="empty-image">
										{{ getInitials }}
									</span>
									<span class="text">
											Изменить
									</span>
									<input
										v-validate="'fileType'"
										data-vv-as="Фотография"
										name="avatar"
										@change="loadImage"
										type="file"
										accept=".jpg,.jpeg,.png"/>
								</label>
							</div>
						</div>
					</div>

					<div class="profile-info">
						<p class="profile-info__fullname">
							{{ serverData.name }}
							<br>
							{{ serverData.surname }}
						</p>
						<span class="profile-info__points">
							{{
								userData.points || 0
							}} баллов
						</span>
						<span class="profile-info__id">
							ID: {{ $store.state.user.id }}
						</span>
					</div>
				</div>
			</div>

			<div class="user__inner--form">
				<div class="user__inner--form-title">
					<p class="title">Изменить учетные данные</p>
				</div>
			</div>

			<form @submit.prevent="changeUserData" class="user__form" id="user-form">
				<div class="user__form-name_surname">
					<form-field
						:errors="getErrors('userData.name')"
						label="Имя"
						class="user__field"
						isRequired
					>
						<base-input
							v-model="userData.name"
							v-validate="'required|cyrillic'"
							:isError="!!getErrors('data.name').length"
							data-vv-scope="userData"
							data-vv-as="Имя"
							name="name"
							placeholder="Имя"
						/>
					</form-field>

					<form-field
						:errors="getErrors('userData.patroname')"
						label="Отчество"
						class="user__field"
					>
						<base-input
							v-model="userData.patroname"
							v-validate="'cyrillic'"
							:isError="!!getErrors('userData.patroname').length"
							data-vv-scope="userData"
							data-vv-as="Отчество"
							name="patroname"
							placeholder="Отчество"
						/>
					</form-field>
				</div>

				<form-field
					:errors="getErrors('userData.surname')"
					label="Фамилия"
					class="user__field"
					is-required
				>
					<base-input
						v-model="userData.surname"
						v-validate="'required|cyrillic'"
						:isError="!!getErrors('userData.surname').length"
						data-vv-scope="userData"
						data-vv-as="Фамилия"
						name="surname"
						placeholder="Фамилия"
					/>
				</form-field>

				<form-field
					label="Уникальный код пользователя"
					class="user__field"
				>
					<base-input
						v-model="hashEmail"
						disabled
					/>
				</form-field>
				<vue-qrcode :value="hashEmail" :width="150"></vue-qrcode>
				<form-field v-show="isEmailShown" label="E-mail" class="user__field">
					<template #toggle v-if="userVerifiedData.email">
						<span v-text="'скрыть'" @click="isEmailShown = false"/>
					</template>
					<div v-if="userVerifiedData.email" class="user__icon-wrapper">
						<base-input
							v-if="userVerifiedData.email"
							:value="userVerifiedData.email"
							name="email"
							placeholder="E-mail"
							:disabled="true"
						/>

						<icon
							v-if="userVerifiedData.email"
							v-tippy="'Изменить'"
							name="edit"
							@click="isChangeEmailModalShown = true"
							class="user__icon"
						/>
					</div>
				</form-field>

				<form-field v-show="!isEmailShown" label="E-mail" class="user__field">
					<template #toggle v-if="userVerifiedData.email">
						<span @click="isEmailShown = true" v-text="'показать'"/>
					</template>

					<div v-if="userVerifiedData.email" class="user__icon-wrapper">
						<base-input
							:value="maskEmail(userVerifiedData.email)"
							name="email"
							placeholder="E-mail"
							disabled
						/>
						<icon
							v-if="userVerifiedData.email"
							v-tippy="'Изменить'"
							name="edit"
							@click="isChangeEmailModalShown = true"
							class="user__icon"
						/>
					</div>
					<button
						v-else
						v-text="'Прикрепить E-mail'"
						@click="isChangeEmailModalShown = true"
						type="button"
						class="button button--invert user__button"
					/>
				</form-field>

				<form-field
					v-show="isChangePhone"
					label="Номер телефона"
					:errors="getErrors('userData.phone')"
					class="user__field"
				>
					<span v-text="'скрыть'" @click="clearPhone" slot="toggle"/>

					<base-input
						v-model="userData.phone"
						v-validate="'phone'"
						:mask="'+7 (999) 999-99-99'"
						:isError="!!getErrors('userData.phone').length"
						:readonly="userData.sms_hash"
						data-vv-scope="userData"
						data-vv-as="Номер телефона"
						name="phone"
						placeholder="+7 (___) ___-__-__"
					/>

					<input
						v-validate="{ required: isPhoneVerificationNeeded }"
						:value="userData.sms_hash"
						data-vv-scope="userData"
						name="sms_hash"
						type="hidden"
					/>

					<button
						v-if="$store.state.isSmsSecurity"
						v-show="isPhoneVerificationNeeded"
						v-text="'Подтвердить номер телефона'"
						@click="isPhoneVerificationShown = true"
						type="button"
						class="button"
					/>
				</form-field>

				<phone-verification
					v-show="isChangePhone"
					v-if="isPhoneVerificationShown"
					@verification:error="verifyError"
					@verification:success="handlePhoneVerification($event)"
					:phone="userData.phone"
					:isRegisterPage="false"
				/>

				<form-field
					v-show="!isChangePhone"
					label="Номер телефона"
					class="user__field"
				>
					<template #toggle>
            <span
	            v-if="!isFieldDisabled('phone')"
	            v-text="'изменить'"
	            @click="isChangePhone = true"
            />
					</template>
					<base-input :value="maskPhone(userVerifiedData.phone)" disabled/>
				</form-field>

				<form-field label="Населенный пункт" class="user__field">
					<field-dropdown
						v-model="userData.city"
						v-validate
						name="city"
						data-vv-scope="userData"
						data-vv-as="Населенный пункт"
						label-name="area_name"
						track-by="area_id"
						value-name="area_id"
						:data="newCities"
						:disabled="isFieldDisabled('address')"
						placeholder="Выберите город"
						class="user__city-dropdown"
						searchable
					>
						<template #option="{ option }">
							<div
								v-text="option.area_name"
								:class="getCityOptionModifiers(option)"
							/>
						</template>
					</field-dropdown>
				</form-field>

				<form-field
					label="Дата рождения"
					:errors="getErrors('userData.birthday')"
					class="register__field"
				>
					<base-input
						ref="date"
						v-model="userData.birthday"
						:disabled="isFieldDisabled('birthday')"
						v-validate="{
              date_format: 'dd.MM.yyyy',
              date_between: birthdayRange,
            }"
						:mask="{
              alias: 'datetime',
              inputFormat: 'dd.mm.yyyy',
              placeholder: 'ДД.ММ.ГГГГ',
              insertModeVisual: false,
              showMaskOnHover: true,
            }"
						data-vv-as="Дата рождения"
						data-vv-scope="userData"
						:isError="!!getErrors('userData.birthday').length"
						name="birthday"
					/>
				</form-field>

				<form-field
					label="Пол"
					:errors="getErrors('userData.gender')"
					class="user__field"
				>
					<radio-group
						v-model="userData.gender"
						v-validate="'gender'"
						data-vv-scope="userData"
						data-vv-as="Пол"
						:items="[
              { value: 'male', label: 'Мужской' },
              { value: 'female', label: 'Женский' },
            ]"
						name="gender"
					/>
				</form-field>

				<div
					@click="isAdditionalShown = !isAdditionalShown"
					v-text="`${isAdditionalShown ? '- Скрыть' : '+ Добавить'} данные`"
					class="link user__additional-trigger"
				/>

				<div v-show="isAdditionalShown" class="user__additional">
					<form-field
						:errors="getErrors('userData.international_passport')"
						label="Номер загранпаспорта"
						class="user__field"
					>
						<base-input
							v-model="userData.international_passport"
							v-validate="'length_numbers:9'"
							:mask="'99 9999999'"
							:isError="!!getErrors('userData.international_passport').length"
							data-vv-scope="userData"
							data-vv-as="Номер загранпаспорта"
							name="international_passport"
							placeholder="Номер загранпаспорта"
						/>
					</form-field>

					<form-field
						:errors="getErrors('userData.inn')"
						label="ИНН"
						class="user__field"
					>
						<base-input
							v-model="userData.inn"
							:mask="'999999999999'"
							v-validate="'length_numbers:12'"
							:isError="!!getErrors('userData.inn').length"
							data-vv-scope="userData"
							data-vv-as="ИНН"
							name="inn"
							placeholder="ИНН"
						/>
					</form-field>

					<form-field
						:errors="getErrors('userData.pension_certificate')"
						label="Пенсионное свидетельство (СНИЛС)"
						class="user__field"
					>
						<base-input
							v-model="userData.pension_certificate"
							:mask="'999-999-999 99'"
							v-validate="'length_numbers:11'"
							:isError="!!getErrors('userData.pension_certificate').length"
							data-vv-scope="userData"
							data-vv-as="Пенсионное свидетельство (СНИЛС)"
							name="pension_certificate"
							placeholder="Пенсионное свидетельство (СНИЛС)"
						/>
					</form-field>

					<form-field
						:errors="getErrors('userData.driver_license')"
						label="Водительское удостоверение"
						class="user__field"
					>
						<base-input
							v-model="userData.driver_license"
							:mask="'99 99 999999'"
							v-validate="'length_numbers:10'"
							:isError="!!getErrors('userData.driver_license').length"
							data-vv-scope="userData"
							data-vv-as="Водительское удостоверение"
							name="driver_license"
							placeholder="Водительское удостоверение"
						/>
					</form-field>
				</div>

				<portal to="page-profile-buttons">
					<div class="user__buttons">
						<button
							v-text="'Сохранить'"
							form="user-form"
							class="user__button"
							type="submit"
							:class="'button'"
						/>

						<button
							v-text="'Отменить'"
							@click="revertChanges"
							type="button"
							class="button button--neutral user__button"
						/>

						<base-popover class="user__popover">
							<template #trigger>
								<div v-text="'Еще'" class="link user__popover-trigger"/>
							</template>
							<template #default="{ data: { hide } }">
								<div class="user__popover-menu">
									<div
										v-text="'Удалить профиль'"
										@click="showDeleteProfileModal(hide)"
										class="user__popover-item"
									/>
								</div>
							</template>
						</base-popover>
					</div>
				</portal>
			</form>


			<modal
				v-if="isChangeEmailModalShown"
				@modal:close="isChangeEmailModalShown = false"
				title="Изменение адреса электронной почты"
				max-width="800"
			>
				<template #text>
					<div class="modal__text">
						<p
							v-if="isSuccessChangeEmail"
							v-text="
                'Письмо для подтверждения смены адреса электронной почты отправленно на новую почту'
              "
						/>

						<template v-else>
							<form-field
								:errors="getErrors('changeEmail.email')"
								label="E-mail"
								class="user__field"
								isRequired
							>
								<base-input
									v-model="changeEmail"
									v-validate="'required|email'"
									:isError="!!getErrors('changeEmail.email').length"
									data-vv-scope="changeEmail"
									data-vv-as="e-mail"
									name="email"
									placeholder="E-mail"
								/>
							</form-field>
							<div
								v-text="
                  'На этот адрес будет отправлено письмо для подтверждения изменения электронной почты'
                "
								class="user-change-email-text"
							/>
						</template>
					</div>
				</template>
				<template #buttons v-if="!isSuccessChangeEmail">
					<button v-text="'Изменить'" @click="changeUserEmail" class="button"/>
					<button
						v-text="'Отмена'"
						@click="isChangeEmailModalShown = false"
						class="button button--neutral"
					/>
				</template>
			</modal>

			<modal
				v-if="isOrganizationModalShown"
				@modal:close="isOrganizationModalShown = false"
				title="Как привязать ООО или ИП"
				text="Чтобы совершать вход во внешние системы как ЮЛ или ИП, необходимо привязать к профилю учётную запись Госуслуг с привязанными юридическими лицами или индивидуальными предпринимателями."
				max-width="800"
			/>

			<modal
				v-if="isAgreementModalShown"
				@modal:close="isAgreementModalShown = false"
				title="Пользовательское соглашение"
				:text="agreement"
			/>

			<modal
				v-if="isDeleteProfileModalShown"
				@modal:close="isDeleteProfileModalShown = false"
				title="Внимание!"
				:text="`Текущий профиль будет удален, но создать новый под тем же адресом электронной почты будет невозможно.<br>Вы уверены?`"
				:has-buttons="false"
				max-width="800"
			>
				<template #footer>
					<form
						action="/profile/delete"
						method="POST"
						class="user__delete-buttons"
					>
						<input type="hidden" name="_token" :value="$store.getters.csrf"/>

						<button
							v-text="'Удалить'"
							class="button button--negative user__delete-button"
						/>

						<button
							v-text="'Отмена'"
							@click="isDeleteProfileModalShown = false"
							class="button button--neutral user__delete-button"
							type="button"
						/>
					</form>
				</template>
			</modal>
		</div>
	</div>
</template>

<script>
import {format, addYears, isDate, parse} from "date-fns";
import {cloneDeep, every, isEqual, forEach} from "lodash";
import {maskPhone, maskEmail} from "@/js/helpers";

import FormField from "@/js/components/FormField";
import BaseInput from "@/js/components/BaseInput";
import BaseCheckbox from "@/js/components/BaseCheckbox";
import FieldDropdown from "@/js/components/FieldDropdown";
import BasePopover from "@/js/components/BasePopover";
import RadioGroup from "@/js/components/RadioGroup";
import PhoneVerification from "@/js/components/PhoneVerification";
import Modal from "@/js/components/Modal";
import md5 from "js-md5";
import VueQrcode from "vue-qrcode";

export default {
	name: "User",

	components: {
		FormField,
		BaseInput,
		BaseCheckbox,
		FieldDropdown,
		BasePopover,
		RadioGroup,
		PhoneVerification,
		Modal,
		VueQrcode,
	},

	props: {
		serverData: { type: Object, default: () => ({}) },
		cities: { type: Array, required: true }, // деду надо данное поле
		isUserOrganization: { type: Boolean, required: true },
	},

	data() {
		return {
			avatar: {},

			newCities: [
				...cloneDeep(this.cities),
			],

			userData: {
				...cloneDeep(this.serverData),
				sms_hash: null,
			},

			userVerifiedData: {
				...cloneDeep(this.serverData),
				sms_hash: null,
			},

			serverErrors: {
				userData: null,
				changeEmail: null,
			},

			isPhoneVerificationShown: false,
			isChangePhone: false,

			isEmailShown: false,
			isChangeEmailModalShown: false,
			changeEmail: null,
			isSuccessChangeEmail: false,

			agreement: null,
			isAgreementModalShown: false,

			isDeleteProfileModalShown: false,
			isOrganizationModalShown: false,

			isAdditionalShown: false,
		};
	},

	computed: {
		getInitials() {
			return `${this.serverData.name[0]}. ${this.serverData.surname[0]}.`;
		},

		showAvatar() {
			return !!this.avatar.preview
				? this.avatar.preview
				: !!this.userData.photo
					? this.getPhoto(this.userData.photo) : "";
		},

		isPhoneVerificationNeeded() {
			const hasPhoneData = !!this.userData.phone;
			const hasErrors = !!this.getErrors("userData.phone").length;
			const isVerified = !!this.userData.sms_hash;
			const isTheSame = this.userData.phone === this.userVerifiedData.phone;

			return (
				hasPhoneData &&
				!hasErrors &&
				!isVerified &&
				!this.isPhoneVerificationShown &&
				!isTheSame &&
				this.$store.state.isSmsSecurity
			);
		},

		birthdayRange() {
			const formatter = (val) => format(val, "dd.MM.yyyy");
			return [addYears(new Date(), -125), new Date()]
				.map(formatter)
				.concat("[]");
		},

		hashEmail() {
			const md5Email = md5(this.userVerifiedData.email).substr(0, 5);
			return (
				md5Email
			);
		},

	},
	methods: {
		getPhoto(photo) {
			return `${this.$store.state.picUrl}/${photo}`;
		},

		async loadImage({ target }) {
			const isSuccess = await this.$validator.validate("avatar");
			const { commit, dispatch } = this.$store;
			const newItems = [...target.files].map((file) => ({ file }));

			if (isSuccess) {
				const formData = new FormData();
				formData.append("file", newItems[0]["file"]);

				commit("setLoader", true);
				await dispatch("sendFileImage", formData);
				commit("setLoader", false);
			} else {
				this.$notify({ type: "error", text: "Файл имеет не тот тип" });
			}

			this.handlerInputValue(newItems)
				.then(() => {
					commit("setImageLoading", true);
				});

			target.value = "";
		},

		async handlerInputValue(files) {
			this.avatar = await Promise.any(
				(files || []).map(async (item) => {
					const { file, hash, datetime, rotate_angle } = item;
					let result = {};

					result["preview"] = file
						? URL.createObjectURL(file)
						: null;

					result.rotate_angle = rotate_angle || 0;
					result.datetime = null;

					if (datetime) {
						result.datetime = isDate(datetime)
							? datetime
							: parse(datetime, "dd.MM.yyyy HH:mm", new Date());
					}

					result.datetime = isDate(datetime)
						? datetime
						: datetime
							? parse(datetime, "dd.MM.yyyy HH:mm", new Date())
							: null;

					if (file) result.file = file;

					if (hash) result.hash = hash;

					return result;
				}),
			);
		},

		getErrors(field) {
			return this.$store.getters.getErrors(
				field,
				this.veeErrors,
				this.serverErrors,
			);
		},

		getCityOptionModifiers(option) {
			const isParent = option.lvl === 1;

			return {
				"page-profile__city-dropdown__parent": isParent,
				"page-profile__city-dropdown__child": !isParent,
			};
		},

		maskEmail(email) {
			return maskEmail(email);
		},

		maskPhone(phone) {
			return maskPhone(phone);
		},

		handlePhoneVerification(sms_hash) {
			this.userData.sms_hash = sms_hash;
			this.isPhoneVerificationShown = false;
		},

		isButtonDisabled(name) {
			return every(this.veeFields[`$${name}`], { dirty: false });
		},

		isFieldDisabled(field) {
			const ESIA = this.$attrs["esiaInfo"];
			return !!ESIA && Object.keys(ESIA).includes(field);
		},

		async showAgreementModal() {
			if (!this.agreement) {
				const { commit, dispatch } = this.$store;
				commit("setLoader", true);
				this.agreement = await dispatch("fetchAgreement");
				commit("setLoader", false);
			}
			this.isAgreementModalShown = true;
		},

		showDeleteProfileModal(hide) {
			hide();
			this.isDeleteProfileModalShown = true;
		},

		async changeUserData() {
			const isFormValid = await this.$validator.validateAll("userData");
			if (!isFormValid) return false;

			const { commit, dispatch } = this.$store;
			const params = cloneDeep(this.userData);

			if (!this.isChangePhone) {
				delete params.phone;
				delete params.sms_hash;
			}

			commit("setLoader", true);
			this.serverErrors.userData = await dispatch("editProfile", params);
			commit("setLoader", false);

			if (!this.serverErrors.userData) {
				this.$notify({ type: "success", text: "Профиль успешно обновлен!" });
				this.userVerifiedData = cloneDeep(this.userData);
				this.$emit("user:update", this.userData);
			} else {
				this.$notify({ type: "error", text: "Обновить профиль не удалось!" });
			}

			// сбрасываем информацию о статусе полей, для корректной проверки
			// в функции isButtonDisabled
			await this.$validator.reset();
		},

		async changeUserEmail() {
			const hasNoErrors = await this.$validator.validateAll("changeEmail");
			if (!hasNoErrors)
				return this.$notify({
					type: "error",
					text: "Введите Email правильно!",
				});

			const { commit, dispatch } = this.$store;
			const params = { email: this.changeEmail };

			commit("setLoader", true);
			const response = await dispatch("changeEmail", params);
			commit("setLoader", false);

			if (response.status) {
				this.$notify({ type: "success", text: "Профиль успешно обновлен!" });
				this.isSuccessChangeEmail = true;
			} else {
				this.serverErrors.changeEmail = response.errors;
				this.$notify({ type: "error", text: "Изменить Email не удалось!" });
			}
		},

		revertChanges() {
			this.userData = {
				...cloneDeep(this.serverData),
				sms_hash: null,
			};
			this.$nextTick(() => {
				this.$validator.validateAll("userData");
			});
		},

		verifyError(errors) {
			this.isPhoneVerificationShown = false;
			this.serverErrors.userData =
				this.serverErrors.userData === null
					? (this.serverErrors.userData = errors)
					: { ...this.serverErrors.userData, ...errors };
		},

		clearPhone() {
			this.isChangePhone = false;
			this.isPhoneVerificationShown = false;
			this.userData.phone = this.userVerifiedData.phone;
			this.userData.sms_hash = null;
		},

		closeWindow(event) {
			if (!isEqual(this.userData, this.userVerifiedData)) {
				// Cancel the event as stated by the standard.
				event.preventDefault();
				// Chrome requires returnValue to be set.
				event.returnValue =
					"Внимание! Внесённые изменения в профиль не были сохранены. Хотите покинуть страницу?";
			}
		},
	},

	created() {
		if (this.$attrs["esiaInfo"]) {

			const fieldAddress = this.$attrs["esiaInfo"]["address"];
			const arr = [];

			Object.keys(fieldAddress).forEach((key) => {
                forEach(fieldAddress[key], (val, keyObj) => {
                    keyObj === "city" || keyObj === "region"
                        ? arr.push([`${key}_${keyObj}`, val])
                        : null;
                });
            });

            const city = Object.fromEntries(arr)["registration_city"]
                ? Object.fromEntries(arr)["registration_city"]
                : Object.fromEntries(arr)["residence_city"]
                ? Object.fromEntries(arr)["residence_city"]
                : Object.fromEntries(arr)["registration_region"]
                ? Object.fromEntries(arr)["registration_region"]
                : Object.fromEntries(arr)["residence_region"]
                ? Object.fromEntries(arr)["residence_region"]
                : null;

			this.userData = {
				...this.userData,
				city: this.newCities.length + 1,
			};

			this.userVerifiedData = {
				...this.userVerifiedData,
				city: this.newCities.length + 1,
			};

			const citiesExists = this.newCities.filter((obj) => obj["area_name"] === city);

			if (!citiesExists.length) {
				this.newCities.push({
					area_id: this.newCities.length + 1,
					area_prnt_id: 0,
					area_name: city,
					lvl: 1,
					has_children: 0,
				});
			}
		}

		//window.onunload = this.closeWindow;
		window.addEventListener("beforeunload", this.closeWindow);
		//window.onbeforeunload = this.closeWindow;
	},
};
</script>

<style lang="scss">
@import "~@/sass/variables";

.user {
	&__inner {
		&--about {
			margin: 30px 0 20px 0;
			background-color: white;
			border-radius: 10px;
			padding: 15px;
			box-shadow: 0 2px 7px rgba(0, 0, 0, 0.2);

			.wrapped {
				display: grid;
				grid-template-columns: 140px 1fr;
				grid-column-gap: 15px;

				.profile-info {
					width: 100%;
					display: flex;
					position: relative;
					flex: 1 1 auto;
					align-items: baseline;
					flex-direction: column;

					> * {
						margin-bottom: 8px;
					}

					&__fullname {
						width: 100%;
						font-size: 22px;
						line-height: 28px;
						font-weight: 700;
						color: #0c2248;
						letter-spacing: .5px;
						text-overflow: ellipsis;
						overflow: hidden;

						@media (max-width: $break_xs) {
							width: 100px;
							text-overflow: ellipsis;
							overflow: hidden;
						}
					}

					&__points {
						position: relative;
						color: white;
						border-radius: 7px;
						font-size: 17px;
						font-weight: 550;
						padding: 2px 10px;
						text-transform: capitalize;
						background: linear-gradient(#f57302, #eeca53);
					}

					&__id {
						font-size: 19px;
						color: #0c2248;
						font-weight: 600;
					}
				}

				.profile-avatar {
					width: 140px;
					height: 140px;

					&__avatar-container {
						position: relative;
						display: block;
						cursor: pointer;
						width: inherit;
						height: inherit;
					}

					&__avatar-change {
						width: inherit;
						height: inherit;
						position: relative;

						.preview-image {
							width: 140px;
							height: 140px;
							border-radius: 10px;
						}

						input[type="file"] {
							top: 0;
							display: none;
							pointer-events: none;
							user-select: none;
						}

						.label-empty {
							width: inherit;
							height: inherit;

							&::before {
								border-radius: 10px;
								width: inherit;
								height: inherit;
								position: absolute;
								display: block;
								content: "";
								z-index: 3;
								background: linear-gradient(45deg, #3482ff 0%, #a8c9ff 100%);
							}

							.empty-image {
								color: #fff;
								font-size: 35px;
								font-weight: 600;
								position: absolute;
								z-index: 10;
								width: 100%;
								height: 100%;
								display: flex;
								justify-content: center;
								align-items: center;
							}
						}

						.label {
							height: 100%;
							border-radius: 5px;
							display: block;
							cursor: pointer;

							.text {
								color: #fff;
								opacity: .7;
								text-transform: uppercase;
								font-weight: 500;
								font-size: 13px;
								position: absolute;
								left: 50%;
								bottom: 5px;
								z-index: 10;
								transform: translateX(-50%);
								transition: opacity .3s;

							}

							&:hover {
								.text {
									opacity: 1;
								}
							}
						}
					}

					&__avatar {
						width: 100%;
						height: 100%;
					}
				}
			}
		}

		&--form {
			&-title {
				.title {
					font-size: 30px;
					font-weight: 600;
					line-height: 40px;
					margin: 20px 0;
					color: #0c2248;
				}
			}
		}
	}

	&__form,
	&__additional {
		> * + * {
			margin-top: 15px;
		}
	}

	&__form {
		&-name_surname {
			display: grid;
			grid-template-columns: repeat(2, 1fr);
			gap: 15px;
		}
	}

	&__icon {
		margin-top: 0;
		position: absolute;
		top: 7px;
		right: 5px;
		width: 40px;
		height: 40px;
		padding: 10px;
		opacity: 0.8;
		cursor: pointer;
		transition: opacity 0.3s;
	}

	&__icon-wrapper {
		position: relative;
	}

	&__field--bordered {
		margin-top: 30px;
		padding-top: 20px;
		border-top: 1px solid #d3d8db;
	}

	&__additional-trigger {
		padding-top: 20px;
		border-top: 1px solid #d3d8db;

		display: block;

		margin-top: 30px;

		font-size: 20px;
		line-height: 30px;
	}

	&__buttons {
		display: flex;
		align-items: center;

		> * + * {
			margin-left: 20px;
		}
	}

	&__button {
		flex: 1 1 auto;
		max-width: 230px;
		font-size: 16px;
		font-weight: 500;
	}

	&__popover {
		flex: 0 0 auto;
	}

	&__popover-trigger {
		font-size: 16px;
		line-height: 15px;
		font-weight: 600;
		text-transform: uppercase;
	}

	&__popover-item {
		padding: 15px 20px;
		color: #7b898d;

		cursor: pointer;

		&:hover {
			background-color: rgba(#f0f3f5, 0.7);
		}
	}

	&__delete-buttons {
		display: flex;
		align-items: center;
	}

	&__delete-button {
		flex: 1 1 auto;
		max-width: 220px;

		& + & {
			margin-left: 20px;
		}
	}
}
</style>
