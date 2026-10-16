import {get, set, isEmpty, extendWith} from "lodash";

import Vue from "vue";
import Vuex from "vuex";
import API from "../services";

const REGISTRATION_CODE_MINUTES_LIFETIME = process.env.MIX_REGISTRATION_CODE_MINUTES_LIFETIME;
const PIC_URL = process.env.MIX_PIC_URL;

Vue.use(Vuex);

const store = new Vuex.Store({
	state: {
		isLoading: false,

		user: {},

		imageIsLoading: false,

		age_periods: [
			{ id: 1, name: "до 18 лет" },
			{ id: 2, name: "18 – 35 лет" },
			{ id: 3, name: "35 -55 лет" },
			{ id: 4, name: "более 55 лет" },
		],

		isSmsSecurity: true,
		needRecaptcha: false,
		recaptchaVersion: 3,
		picUrl: PIC_URL,

		description: null,

		isHeaderHidden: false,
		isFooterHidden: false,
		registrationCodeMinutesLifetime: REGISTRATION_CODE_MINUTES_LIFETIME ? Number(REGISTRATION_CODE_MINUTES_LIFETIME) : 60,

		appCircle: null,
		alarmText: null,
		alarmCookieName: null,
	},

	getters: {
		csrf: () => document.getElementById("csrf").content,

		redirectURL: () => `redirect=${encodeURIComponent(window.location)}`,

		isAuth: state => !isEmpty(state.user),

		getErrors() {
			return (field, clientErrors, serverErrors = {}) => {
				const hasClientErrors = clientErrors.has(field);
				// в том случае, если есть клиентские ошибки - удаляем серверные
				if (hasClientErrors) {
					set(serverErrors, field, null);
					return clientErrors.collect(field);
				} else {
					return get(serverErrors, field) || [];
				}
			};
		},
	},

	mutations: {
		setImageLoading(state, value) {
			state.imageIsLoading = value;
		},

		setData(state, { path, value }) {
			return set(state, path, value);
		},

		setLoader(state, value) {
			state.isLoading = value;
		},
	},

	actions: {
		//REGISTER:
		async verifyRegisterPhone(payload, params = {}) {
			const response = await API.post("/register/send_sms", params);
			return response.data;
		},

		async verifyRegisterSmsCode(payload, params = {}) {
			const response = await API.post("/register/confirm_sms", params);
			return response.data;
		},

		async register(payload, params = {}) {
			const response = await API.post("/register", params);
			return response.data;
		},

		async sendFileImage(payload, params) {
			const response = await API.post(
				"/profile/send_image",
				params,
				{
					headers: {
						"Content-Type": "multipart/form-data",
					},
				});
			return response.data;
		},

		async activateAgain(payload, params = {}) {
			const response = await API.get("/user/activate_again", { params });
			return response.data;
		},

		async sendRegistrationCode(payload, params = {}) {
			const response = await API.post("/user/activate_by_code", params);
			return response.data;
		},

		//PROFILE:
		async removeSocial(payload, name) {
			const response = await API.get(`/profile/remove_${name}`),
				hasErrors = !response.data.status,
				errors = hasErrors ? respose.data.errors : null;

			return errors;
		},

		async editProfile(payload, params = {}) {
			const response = await API.post("/profile", params),
				hasErrors = !response.data.status,
				errors = hasErrors ? response.data.errors : null;

			return errors;
		},

		async changePassword(payload, params = {}) {
			const response = await API.post("/profile/password_change", params),
				hasErrors = !response.data.status,
				errors = hasErrors ? response.data.errors : null;

			return errors;
		},

		async resetPassword(payload, params = {}) {
			const response = await API.post("/profile/password_reset", params),
				hasErrors = !response.data.status,
				errors = hasErrors ? response.data.errors : null;

			return errors;
		},

		async removeOrganization(payload, params = {}) {
			//TODO На будущее, может быть, сейчас не используется
			const response = await API.post("/profile/remove_org_user", params),
				hasErrors = !response.data.status;

			return hasErrors;
		},

		async verifyProfilePhone(payload, params = {}) {
			const response = await API.post("/profile/send_sms", params);
			return response.data;
		},

		async verifyProfileSmsCode(payload, params = {}) {
			const response = await API.post("/profile/confirm_sms", params);
			return response.data;
		},

		async changeEmail(payload, params = {}) {
			const response = await API.post("/change_email", params);
			return response.data;
		},

		async fetchAccessToken(payload, params = {}) {
			const response = await API.post(
				"/profile/get_access_token",
				params,
			);
			return response.data;
		},

		//USERS:
		async fetchUsers(payload, params = {}) {
			const response = await API.get("/admin/api/users", { params });
			return response.data.data;
		},

		async fetchUserRoles({ state, commit }, id) {
			const response = await API.get(`/admin/api/users/${id}/roles`);
			return response.data.data;
		},

		async editUserRoles(payload, { id, roles }) {
			const response = await API.post(`/admin/api/users/${id}/roles`, {
				roles,
			});
			return response.data.status;
		},

		async blockUser({ commit, dispatch }, params = {}) {
			const response = await API.post(
				"/admin/api/users/blocking",
				params,
			);
			return response.data.status;
		},

		async restoreUser(payload, id) {
			const response = await API.post(`/admin/api/users/${id}/restore`);
			return response.data.status;
		},

		//ROLES:

		async fetchRoles({ state, commit }) {
			const response = await API.get("/admin/api/roles");
			return response.data.data;
		},

		async createRole({ commit, dispatch }, params = {}) {
			const response = await API.post("/admin/api/roles/new", params);
			return response.data.status;
		},

		async editRole({ commit, dispatch }, params = {}) {
			const response = await API.post("/admin/api/roles/edit", params);
			return response.data.status;
		},

		async deleteRole({ commit, dispatch }, role_id) {
			const response = await API.post("/admin/api/roles/delete", {
				role_id,
			});
			return response.data.status;
		},

		//CLIENTS
		async fetchClients(payload) {
			const response = await API.get("/admin/api/clients");
			return response.data.data;
		},

		async createClient(payload, params = {}) {
			const response = await API.post("/admin/api/clients/new", params);
			return response.data;
		},

		async editClient(payload, params = {}) {
			const response = await API.post("/admin/api/clients/edit", params);
			return response.data;
		},

		async deleteClient(payload, params = {}) {
			const response = await API.post("/admin/api/clients/delete", {
				client_id: params.client_id,
			});
			return response.data.status;
		},

    // Mail
		async sendMail(payload, params = {}) {
			const response = await API.post("/admin/api/mailing/mailing_module", params);
			return {
          status: response.data.status_id,
          dataText: response.data.status
      };
		},

    async unsubscribed(payload, params = {}) {
      const response = await API.post("/mailing/unsubscribed", params);

      return response.data
    },

    async unsubscribedUser(payload, params = {}) {
      const response = await API.post("/admin/api/mailing/unsubscribed_user", params);

      return response.data.result;
    },

    async deleteUnsubscribedUser(payload, params = {}) {
      const response = await API.post("/admin/api/mailing/update_unsubscribed", params);

      return response.data
    },

    async fetchUnsubscribedUser(payload, params = {}) {
      const response = await API.get("/admin/api/mailing/get_unsubscribed", { params });
      return response.data.data;
    },

    async fetchQueue(payload, params = {}) {
      const response = await API.get("/admin/api/mailing/get_queue", { params });
			return response.data.data;
    },

		//LOGS
		async fetchLogs(payload, params = {}) {
			const response = await API.get("/admin/api/logs", { params });
			return response.data.data;
		},

		//AGREEMENT
		async fetchAgreement(payload) {
			const response = await API.get("/get_agreement");
			return response.data.data;
		},

		async editAgreement(payload, params = {}) {
			const response = await API.post("/admin/api/set_agreement", params);
			return response.data.status;
		},

		//DESCRIPTION
		async fetchDescription(payload) {
			const response = await API.get("/admin/api/get_description");
			return response.data.data;
		},

		async editDescription(payload, params = {}) {
			const response = await API.post(
				"/admin/api/set_description",
				params,
			);
			return response.data.status;
		},

		//NOTIFICATIONS
		async fetchNotifications(payload, params = {}) {
			const response = await API.post("/my_notifications", params);
			return response.data;
		},
		async fetchNotification(payload, params = {}) {
			const response = await API.post(
				`/my_notifications/${params.service_number}`, params,
			);
			return response.data;
		},

		// DICTIONARIES
		async getCities(payload, params = {}) {
			const response = await API.get("/api/cities", params);
			return response.data;
		},

		// MY CLIENTS
		async editMyClient(payload, params = {}) {
			const response = await API.post("/my_clients/edit", params);
			return response.data;
		},

		async resendMyClient(payload, params = {}) {
			const response = await API.post("/my_clients/resend", params);
			return response.data;
		},

		async fetchMyClients(payload) {
			const response = await API.post("/my_clients");
			return response.data.data;
		},

		async cancelMyClient(payload, params = {}) {
			const response = await API.post("/my_clients/cancel", params);
			return response.data;
		},

		//CLIENTS MODERATION
		async fetchClientsModeration(payload, params = {}) {
			const response = await API.get("/admin/api/clients_moderation", { params });
			return response.data;
		},

		async setStatusClientModeration(payload, params = {}) {
			const response = await API.post("/admin/api/clients_moderation/set_status", params);
			return response.data;
		},

		//CLIENTS CHANGES
		async fetchClientsChanges(payload) {
			const response = await API.get("/admin/api/clients_changes");
			return response.data.data;
		},

		async setStatusClientChanges(payload, params = {}) {
			const response = await API.post("/admin/api/clients_changes/set_status", params);
			return response.data;
		},

		// HOME
		async getNotices(payload, params = {}) {
			const response = await API.post("/notices", params);
			return response.data;
		},

		async getClients(payload, params = {}) {
			const response = await API.post("/clients", params);
			return response.data;
		},

	},
});

export default store;
