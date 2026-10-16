<template>
  <div class="mailing-journal">
    <div v-if="distributionQueue.length" class="mailing-journal__journal">
      <div :style="{ padding: '10px' }">
        <span class="mailing-journal__addition-info">{{
          tableContent === "themes" ? countQueueString : countUsersString
        }}</span>
      </div>
      <base-table
        @table:select-row.once="selectTheme"
        v-bind="switchDataTable"
        class="mailing-journal__table"
      >
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
      <div style="font-size: 24px; font-weight: bolder">Журнал рассылок пуст</div>
    </div>
  </div>
</template>

<script>
import BaseTable from "./BaseTable";
import Pagination from "./Pagination";

import { induce, handlers } from "../helpers";

const statusFormat = (contents) => {
  return (
    contents
      .map((content, _) => {
        return content.status;
      })
      .find((item) => item === "wait") || "send"
  );
};

export default {
  name: "AdminMailingJournal",

  components: {
    BaseTable,
    Pagination,
  },

  data: () => ({
    queryParams: {
      page: 1,
      per_page: 50,
      order_by: null,
    },

    tableHeader: [
      { key: "id", label: "ID", field: "id" },
      { key: "email", label: "Тема рассылки", field: "email" },
      { key: "status", label: "Статус", field: "status", handler: handlers.statusHandler },
      // { key: "user_count", label: "Количество пользователей", field: "user_count" },
    ],

    tableUserHeader: [
      { key: "id", label: "ID", field: "id" },
      { key: "email", label: "E-mail", field: "email" },
      { key: "status", label: "Статус", field: "status", handler: handlers.statusHandler },
      // { key: "user_count", label: "Количество пользователей", field: "user_count" },
    ],

    paginationLength: null,
    countQueue: null,

    tableContent: "themes",
    allUsers: [],

    distributionQueue: [],
  }),

  computed: {
    pageModel: {
      get() {
        return this.queryParams.page;
      },

      async set(value) {
        this.queryParams.page = value;
        await this.fetchQueue();
        window.scroll(0, 0);
      },
    },

    switchDataTable() {
      return {
        themes: this.dataTable,
        users: this.dataUserTable,
      }[this.tableContent];
    },

    countQueueString() {
      return (
        induce(this.countQueue, ["Найдена ", "Найдено ", "Найдено "]) +
        this.countQueue +
        " " +
        induce(this.countQueue, ["рассылка", "рассылок", "рассылок"])
      );
    },

    countUsersString() {
      return (
        induce(this.countQueue, ["Найден ", "Найдено ", "Найдено "]) +
        this.countQueue +
        " " +
        induce(this.countQueue, ["пользователь", "пользователя", "пользователей"])
      );
    },

    dataTable() {
      const header = [
        { label: "ID", field: "id" },
        { label: "Тема рассылки", field: "email" },
        { label: "Статус", field: "status" },
      ];

      return { header, body: this.distributionQueue };
    },

    dataUserTable() {
      const header = [
        { label: "ID", field: "id" },
        { label: "E-mail", field: "email" },
        { label: "Статус", field: "status" },
      ];

      return { header, body: this.distributionQueue };
    },
  },

  methods: {
    async init() {
      await this.fetchQueue();
    },

    prepareQueue(user) {
      this.allUsers = [...this.allUsers, { id: user.id, contents: user.contents }];

      return _.reduceRight(
        this.tableHeader,
        function (result, field) {
          const key = field.key;
          if (key in user) {
            const system_value = key === "status" ? statusFormat(user.contents) : user[key];
            const value = field.handler ? field.handler(system_value) : system_value;
            result[key] = { ...field, system_value, value };
          }

          return result;
        },
        {},
      );
    },

    selectTheme(theme) {
      const selectedTheme = this.allUsers.find((user) => user.id === theme.id.value);

      this.tableContent = "users";
      this.countQueue = selectedTheme.contents.length;

      this.distributionQueue = selectedTheme.contents.map((user) => {
        return _.reduceRight(
          this.tableUserHeader,
          (result, field) => {
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
      });
    },

    async fetchQueue() {
      const { dispatch, commit } = this.$store;

      commit("setLoader", true);
      const { queue, countQueue } = await dispatch("fetchQueue", this.queryParams);

      this.distributionQueue = queue.map(this.prepareQueue);
      this.countQueue = countQueue;

      this.paginationLength = Math.ceil(countQueue / this.queryParams.per_page);
      commit("setLoader", false);
    },
  },

  created() {
    this.init();
  },
};
</script>

<style lang="scss">
.mailing-journal {
  &__table {
    margin-bottom: 10px;
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
