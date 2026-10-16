<template>
  <div class="page-admin-mailing">
    <admin-actions>
      <button @click="shownMalingJournal = true" class="button">Журнал рассылок</button>
      <button @click="shownUnsubscribedUser = true" class="button button--neutral">
        Исключения из рассылки
      </button>
      <button @click="addInExclusion" :disabled="!!!selectedUser" class="button button--negative">
        Удалить пользователя из рассылки
      </button>
    </admin-actions>

    <h1 class="heading-1 page-admin-mailing__title">Пользователи</h1>

    <card title="Поиск пользователей" :init-expand="false" :need-expanding="true">
      <form action="">
        <div class="card__content-row">
          <form-field label="Имя">
            <base-input
              v-model="filterData.first_name"
              data-vv-scope="filterData"
              data-vv-as="Имя"
              name="first_name"
              placeholder="Имя"
            />
          </form-field>

          <form-field label="Фамилия">
            <base-input
              v-model="filterData.second_name"
              data-vv-scope="filterData"
              data-vv-as="Фамилия"
              name="second_name"
              placeholder="Фамилия"
            />
          </form-field>

          <form-field label="Отчество">
            <base-input
              v-model="filterData.patroname"
              data-vv-scope="filterData"
              data-vv-as="Отчество"
              name="patroname"
              placeholder="Отчество"
            />
          </form-field>
        </div>

        <div class="card__content-row">
          <form-field label="Идентификатор">
            <base-input
              v-model="filterData.id"
              type="number"
              data-vv-scope="filterData"
              data-vv-as="Идентификатор"
              name="id"
              placeholder="Идентификатор"
            />
          </form-field>

          <form-field label="Населенный пункт">
            <field-dropdown
              v-model="filterData.city"
              name="city"
              data-vv-scope="filterData"
              data-vv-as="Населенный пункт"
              label-name="area_name"
              track-by="area_id"
              value-name="area_id"
              :data="cities"
              placeholder="Выберите город"
              searchable
            >
              <template #option="{ option }">
                <div v-text="option.area_name" />
              </template>
            </field-dropdown>
          </form-field>
        </div>

        <div class="card__content-row">
          <form-field label="Минимальная граница возраста">
            <base-input
              v-model="filterData.age_period_from"
              type="number"
              data-vv-scope="filterData"
              data-vv-as="От"
              name="age_period_from"
              placeholder="от"
            />
          </form-field>
          <form-field label="Максимальная граница возраста">
            <base-input
              v-model="filterData.age_period_to"
              type="number"
              data-vv-scope="filterData"
              data-vv-as="До"
              name="age_period_to"
              placeholder="до"
            />
          </form-field>
        </div>

        <div class="card__content-row">
          <form-field label="Пол">
            <field-dropdown
              v-model="filterData.gender"
              name="gender"
              data-vv-scope="filterData"
              data-vv-as="Пол"
              label-name="gender_name"
              track-by="gender_title"
              value-name="gender_title"
              :data="genders"
              placeholder="Выберите пол"
              searchable
            >
            </field-dropdown>
          </form-field>

          <form-field label="Госуслуги">
            <field-dropdown
              v-model="filterData.esia"
              name="esia"
              data-vv-scope="filterData"
              data-vv-as="Госуслуги"
              label-name="title"
              value-name="value"
              :data="esia_filter"
              placeholder="Госуслуги"
              searchable
            >
            </field-dropdown>
          </form-field>
        </div>

        <div class="card__content-row">
          <form-field label="Заблокирован">
            <field-dropdown
              v-model="filterData.is_blocked"
              name="is_blocked"
              data-vv-scope="filterData"
              data-vv-as="Заблокирован"
              label-name="title"
              value-name="value"
              :data="[
                { title: 'Отключить фильтр' },
                { value: 0, title: 'Нет' },
                { value: 1, title: 'Да' },
              ]"
              :placeholder="'Заблокирован'"
              searchable
            >
            </field-dropdown>
          </form-field>
          <form-field label="Подтвержден">
            <field-dropdown
              v-model="filterData.is_accepted"
              name="is_accepted"
              data-vv-scope="filterData"
              data-vv-as="Подтвержден"
              label-name="title"
              value-name="value"
              searchable
              :data="[
                { title: 'Отключить фильтр' },
                { value: 0, title: 'Нет' },
                { value: 1, title: 'Да' },
              ]"
              :placeholder="'Подтвержден'"
            >
            </field-dropdown>
          </form-field>
        </div>
        <div class="card__content-row">
          <button class="button" type="button" @click="clearFilter">Сбросить</button>
          <button class="button button--positive" type="button" @click="fetchUsers">
            Фильтровать
          </button>
        </div>
      </form>
      <div class="send__mail">
        <form action="">
          <form-field :style="{ marginBottom: '30px' }" label="Введите текст темы">
            <base-input
              v-model="mailingTheme"
              type="text"
              name="mailing_theme"
              placeholder="Тема сообщения"
            />
          </form-field>
          <form-field label="Введите сообщение">
            <base-tiny v-model="sendText" />
          </form-field>
        </form>
        <div @click="sendToMessage" class="mail_button button button--positive">
          Отправить сообщение
        </div>
      </div>
    </card>

    <template v-if="isUsersExist">
      <div>
        <span class="page-admin-users__addition-info">{{ countUsersString }}</span>
      </div>
      <base-table
        v-if="users.length"
        v-bind="tableData"
        v-model="selectedUser"
        class="page-admin-users__table"
      >
        <template #td="{ data: { cell } }">
          <td class="base-table__td">{{ cell.value }}</td>
        </template>
      </base-table>

      <pagination v-model="pageModel" :size="paginationLength" />
    </template>
    <div
      v-else
      style="align-items: center; justify-content: center; display: flex; margin-top: 40px"
    >
      <div style="font-size: 24px; font-weight: bolder">
        Пользователи с указанными данными не найдены
      </div>
    </div>

    <modal
      v-if="shownMalingJournal"
      @modal:close="shownMalingJournal = $event === 'overlay'"
      title="Журнал рассылок"
      class="page-admin-mailing__modal"
    >
      <template #text>
        <admin-mailing-journal class="page-admin-mailing__journal" />
      </template>
    </modal>

    <modal
      v-if="shownUnsubscribedUser"
      @modal:close="shownUnsubscribedUser = $event === 'overlay'"
      title="Исключения из рассылки"
      class="page-admin-mailing__modal"
    >
      <template #text>
        <admin-unsubscribed-user
          @modal:close="shownUnsubscribedUser = $event === 'overlay'"
          class="page-admin-mailing__unsubscribed"
        />
      </template>
    </modal>
  </div>
</template>

<script>
import { maskPhone, handlers, induce } from "../helpers";
import AdminActions from "./AdminActions";
import AdminUserEdit from "./AdminUserEdit";
import AdminMailingJournal from "@/js/components/AdminMailingJournal";
import AdminUnsubscribedUser from "@/js/components/AdminUnsubscribedUser";

import FormField from "@/js/components/FormField";
import BaseInput from "@/js/components/BaseInput";
import BaseCheckbox from "@/js/components/BaseCheckbox";
import FieldDropdown from "@/js/components/FieldDropdown";
import Card from "@/js/components/Card";
import Pagination from "./Pagination";
import Modal from "@/js/components/Modal";

import BaseTextarea from "./BaseTextarea";
import BaseTable from "./BaseTable";
import BaseTiny from "./BaseTiny";

export default {
  name: "PageMailingModule",
  components: {
    AdminActions,
    AdminUserEdit,
    Pagination,
    BaseTable,
    FormField,
    BaseInput,
    BaseCheckbox,
    FieldDropdown,
    Card,
    BaseTextarea,
    BaseTiny,
    AdminMailingJournal,
    Modal,
    AdminUnsubscribedUser,
  },

  data: () => ({
    queryParams: {
      page: 1,
      per_page: 50,
      order_by: null,
    },

    countUsers: 0,
    filterData: {
      id: null,
      first_name: null,
      second_name: null,
      patroname: null,
      city: null,
      is_blocked: null,
      is_accepted: null,
      gender: null,
      age_period_from: null,
      age_period_to: null,
      esia: null,
    },

    esia_filter: [
      {
        value: -1,
        title: "Отключить фильтр",
      },
      {
        value: 0,
        title: "Нет",
      },
      {
        value: 1,
        title: "Да",
      },
    ],

    genders: [
      {
        gender_id: 0,
        gender_name: "Отключить фильтр",
        gender_title: "none",
      },
      {
        gender_id: 1,
        gender_name: "Мужской",
        gender_title: "male",
      },
      {
        gender_id: 2,
        gender_name: "Женский",
        gender_title: "female",
      },
    ],

    cities: [
      {
        area_id: -2,
        area_name: "Отключить фильтр",
        area_prnt_id: 0,
        has_children: 1,
        lvl: 1,
      },
    ],

    fields: [
      { key: "id", label: "ID" },
      { key: "email", label: "E-mail", handler: handlers["maskEmail"] },
      { key: "phone", label: "Телефон", handler: maskPhone },
      {
        key: "blocked",
        label: "Заблокирован",
        handler: handlers.booleanHandler,
      },
      { key: "fio", label: "ФИО" },
      {
        key: "email_verified_at",
        label: "Подтвержден",
        handler: handlers.dateHandler,
      },
      {
        key: "is_deleted",
        label: "Удаленный",
        handler: handlers.booleanHandler,
      },

      { key: "surname", label: "Фамилия" },
      { key: "name", label: "Имя" },
      { key: "patroname", label: "Отчество" },
      {
        key: "birthday",
        label: "Дата рождения",
        handler: handlers.dateHandler,
      },
      {
        key: "age_period",
        label: "Возраст",
        handler: handlers.ageHandler,
      },
      { key: "gender", label: "Пол", handler: handlers.genderHandler },
      { key: "esia_user_id", label: "Профиль ЕСИА" },
      { key: "vk_user_id", label: "Профиль ВКонтакте" },
      { key: "fb_user_id", label: "Профиль Facebook" },
      {
        key: "updated_at",
        label: "Дата регистрации",
        handler: handlers.dateHandler,
      },
      {
        key: "created_at",
        label: "Дата последнего изменения",
        handler: handlers.dateHandler,
      },
      {
        key: "is_accepted",
        label: "Согласен с пользовательским соглашением",
        handler: handlers.booleanHandler,
      },
    ],
    users: [],
    paginationLength: null,
    sendText: "",
    mailingTheme: "",
    selectedUser: null,

    distributionQueue: [],

    shownMalingJournal: false,
    shownUnsubscribedUser: false,
  }),

  computed: {
    countUsersString() {
      return (
        "Найдено " +
        this.countUsers +
        " " +
        induce(this.countUsers, ["пользователь", "пользователя", "пользователей"])
      );
    },

    isUsersExist() {
      return this.countUsers > 0;
    },

    tableData() {
      const header = [
        { label: "ID", field: "id" },
        { label: "Логин", field: "email" },
        { label: "ФИО", field: "fio" },
        { label: "Заблокированный", field: "blocked" },
        { label: "Подтвержденный", field: "is_accepted" },
      ];

      return { header, body: this.users, hasRadio: true };
    },

    pageModel: {
      get() {
        return this.queryParams.page;
      },

      async set(value) {
        this.queryParams.page = value;
        await this.fetchUsers();
        window.scrollTo(0, 0);
      },
    },
  },

  methods: {
    openMailingJournal() {
      this.shownMalingJournal = !this.shownMalingJournal;
    },

    async addInExclusion() {
      const { dispatch, commit } = this.$store;
      const userMail = this.selectedUser.email.value;

      commit("setLoader", true);
      const response = await dispatch("unsubscribedUser", { user_mails: [userMail] });
      commit("setLoader", false);

      if (response.status_id) {
        this.$notify({
          type: "success",
          text: `${response.status}`,
        });

        this.$nextTick(() => {
          this.fetchUsers();
        });
      } else {
        this.$notify({
          type: "error",
          text: `${response.status}`,
        });
      }
    },

    async init() {
      await this.fetchUsers();
      await this.fetchCities();
    },

    async fetchCities() {
      const { dispatch } = this.$store;
      const response = await dispatch("getCities");
      this.cities = [...this.cities, ...response];
    },

    clearFilter() {
      let isNeedFetch = false;

      let values = _.values(this.filterData);
      for (let key in values) {
        if (values[key] != null) {
          isNeedFetch = true;
          break;
        }
      }

      this.filterData.city = null;
      this.filterData.first_name = null;
      this.filterData.second_name = null;
      this.filterData.patroname = null;
      this.filterData.id = null;
      this.filterData.is_accepted = null;
      this.filterData.is_blocked = null;
      this.filterData.gender = null;
      this.filterData.age_period_from = null;
      this.filterData.age_period_to = null;
      this.filterData.esia = null;

      if (isNeedFetch) this.fetchUsers();
    },

    prepareUser(user) {
      return _.reduceRight(
        this.fields,
        function (result, field) {
          const key = field.key;
          if (key in user) {
            const system_value = user[key];
            const value = field.handler ? field.handler(system_value) : system_value;
            result[key] = { ...field, system_value, value };
          }

          return result;
        },
        {},
      );
    },

    async sendToMessage() {
      const { commit, dispatch } = this.$store;
      const objForSend = {
        per_page: 1000000,
        description: this.sendText,
        subject: this.mailingTheme ? this.mailingTheme : "Активный гражданин Оренбургской области",
        only_verified: true,
        ...this.filterData,
      };

      const { status, dataText } = await dispatch("sendMail", objForSend);

      if (status) {
        this.$notify({
          type: "success",
          text: `${dataText}`,
        });
      } else {
        this.$notify({
          type: "error",
          text: `${dataText}`,
        });
      }
    },

    async fetchUsers() {
      const { commit, dispatch } = this.$store;
      commit("setLoader", true);
      const { users, countUsers } = await dispatch(
        "fetchUsers",
        Object.assign(this.queryParams, this.filterData),
      );

      this.users = users.map(this.prepareUser);
      this.countUsers = countUsers;

      this.paginationLength = Math.ceil(countUsers / this.queryParams.per_page);
      commit("setLoader", false);
    },
  },

  created() {
    this.init();
  },
};
</script>

<style lang="scss">
.page-admin-mailing {
  &__title {
    margin-bottom: 20px;
  }

  .send__mail {
    margin: 0 10px 10px;

    .mail_button {
      margin: 20px 0 0 0;
    }
  }

  &__modal-title {
    text-align: center;
  }

  &__journal {
    margin: 0 10px 20px;
  }

  &__unsubscribed {
    margin: 0 10px 20px;
  }

  &__addition-info {
    color: #7b898d;
    font-weight: 400;
  }

  &__table {
    margin-bottom: 20px;
  }

  .card__content-row button {
    margin: 0 10px;
  }
}
</style>
