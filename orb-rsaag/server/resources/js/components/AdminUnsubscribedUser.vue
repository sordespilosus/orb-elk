<template>
  <div class="admin-unsubscribed-user">
    <div class="admin-unsubscribed-user__addition" :style="{ padding: '10px' }">
      <p class="admin-unsubscribed-user__addition-info" :style="showingUsersCount">
        {{ countUsersString }}
      </p>
      <div class="admin-unsubscribed-user__addition-actions">
        <button
          :style="showingUsersCount"
          class="button button--negative admin-unsubscribed-user__addition-button"
          @click="deleteUser"
        >
          Удалить пользователя
        </button>
        <div class="admin-unsubscribed-user__addition-upload file-upload">
          <input
            id="input-file"
            ref="file-upload"
            class="file-upload__input"
            @change="uploadFile"
            type="file"
          />

          <label for="input-file" class="button file-upload__label">
            <div class="file-upload__placeholder">Исключить пользователей через файл</div>
          </label>
        </div>
      </div>
    </div>
    <div v-if="users.length" class="admin-unsubscribed-user__journal">
      <base-table v-model="currentUser" v-bind="tableData" class="admin-unsubscribed-user__table">
        <template #td="{ data: { cell } }">
          <td class="base-table__td">{{ cell.value }}</td>
        </template>
      </base-table>
      <pagination v-model="pageModel" :size="paginationLength" />
    </div>
    <div
      v-else
      style="align-items: center; justify-content: center; display: flex; margin: 40px 0 40px 0"
    >
      <div style="font-size: 24px; font-weight: bolder">
        Нет пользователей отписавшихся от рассылки
      </div>
    </div>
  </div>
</template>

<script>
import BaseTable from "./BaseTable";
import Pagination from "./Pagination";
import { induce } from "../helpers";

const unsubscribeHandler = (status) => {
  return {
    true: "Отписался",
    false: "Не отписался",
  }[status];
};

export default {
  name: "AdminUnsubscribedUser",
  components: {
    Pagination,
    BaseTable,
  },

  data: () => ({
    queryParams: {
      page: 1,
      per_page: 50,
      order_by: null,
    },

    tableHeader: [
      { key: "id", label: "ID", field: "id" },
      { key: "email", label: "E-mail", field: "email" },
      { key: "status", label: "Статус", field: "status", handler: unsubscribeHandler },
      {
        key: "created_at",
        label: "Дата отписки",
        field: "created_at",
      },
    ],
    currentUser: null,
    users: [],
    paginationLength: null,
    countUsers: null,
  }),

  computed: {
    showingUsersCount() {
      return !this.users.length ? "visibility: hidden" : "";
    },

    countUsersString() {
      return (
        induce(this.countUsers, ["Найден ", "Найдено ", "Найдено "]) +
        this.countUsers +
        " " +
        induce(this.countUsers, ["пользователь", "пользователя", "пользователей"])
      );
    },

    pageModel: {
      get() {
        return this.queryParams.page;
      },

      async set(value) {
        this.queryParams.page = value;
        await this.fetchUsers();
        window.scroll(0, 0);
      },
    },

    tableData() {
      const header = [
        { label: "ID", field: "id" },
        { label: "E-mail", field: "email" },
        { label: "Статус", field: "status" },
        { label: "Дата отписки", field: "created_at" },
      ];

      return { header, body: this.users, hasRadio: true };
    },
  },

  methods: {
    async init() {
      await this.fetchUsers();
    },

    async uploadFile(event) {
      try {
        const { commit, dispatch } = this.$store;
        const [file] = event.target.files;
        const fileData = file ? await this.getDataFromFile(file) : null;

        commit("setLoader", true);
        const response = await dispatch("unsubscribedUser", {
          user_mails: fileData?.replace(/\n/g, " ").trim().split(" "),
        });
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
      } catch (error) {
        this.$notify({
          type: "error",
          text: "Не возможно прочитать содержимое файла",
        });
      }
    },

    getDataFromFile(file) {
      return new Promise((resolve) => {
        const reader = new FileReader();
        reader.readAsText(file);
        reader.onload = () => resolve(reader.result);
      });
    },

    async deleteUser() {
      const { dispatch, commit } = this.$store;

      if (!this.currentUser) {
        this.$notify({
          type: "error",
          text: "Пользователь не выбран",
        });

        return;
      }

      commit("setLoader", true);
      const response = await dispatch("deleteUnsubscribedUser", { id: this.currentUser.id.value });
      commit("setLoader", false);

      if (response.status) {
        this.$notify({
          type: "success",
          text: "Пользователь успешно удален",
        });

        this.$nextTick(() => {
          this.fetchUsers();
        });
      } else {
        this.$notify({
          type: "error",
          text: "Удаление не прошло",
        });
      }
    },

    prepareUser(user) {
      return _.reduceRight(
        this.tableHeader,
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

    async fetchUsers() {
      const { dispatch, commit } = this.$store;

      commit("setLoader", true);
      const { users, countUsers } = await dispatch("fetchUnsubscribedUser", {
        ...this.queryParams,
      });

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
.admin-unsubscribed-user {
  &__table {
    margin-bottom: 10px;
  }

  &__addition {
    display: flex;
    align-items: center;
    justify-content: space-between;

    &-actions {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 10px;

      &-button {
        max-width: min-content;
      }
    }

    .file-upload {
      &__input {
        position: absolute;
        border: 0;
        margin: -1px;
        padding: 0;
        width: 1px;
        height: 1px;
        overflow: hidden;
        clip: rect(0px, 0px, 0px, 0px);
      }
    }
  }

  &__button {
    &:disabled {
      opacity: 1;
      pointer-events: none;

      &:hover,
      &:focus {
        border-color: #d3d8db;
      }
    }
  }
}
</style>
