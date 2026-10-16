<template>
    <modal
        @modal:close="closes">
        <template #title>Заявление</template>
        <template #text>
            <base-spinner v-if="isLoading" class="page-my-notifications__spinner"/>
            <div class="my-notification__content" v-else>
                <div v-for="(field, key) in dataMore.notification" :key=key class="my-notification__field">
                    <div class="my-notification__field-header">{{field.name}}</div>
                    <div class="my-notification__field-text">{{field.value}} </div>
                </div>
                <template v-if="dataMore.statusHistory">
                    <div class="my-notification__field-header">История обработки</div>
                    <ul class="my-notification__statuses-list">
                        <li v-for="(status, key) in dataMore.statusHistory" :key="key">
                            <div class="my-notification__row">
                                <div class="my-notification__additional-date" v-text="status.status_date"/>
                                <div clas="my-notification__column">
                                    <div class="my-notification__additional-title" v-text="status.status_name"/>
                                    <div class="wysiwyg my-notification__additional-text" v-text="status.note"/>
                                    <div v-if="status.docs" class="my-notification__files">
                                        <div class="my-notification__files-title">Прикрепленные документы</div>
                                        <div v-for="(doc, key) in status.docs" class="my-notification__file" :key="key">
                                            <div v-for="(file, key) in doc.files" :key="key">
                                                <a class="link" :href="file.file_link" target="_blank">{{file.file_name}}</a>
                                                <a style="margin-left: 25px;" class="link" v-if="file.file_sign" :href="file.file_sign" target="_blank">Скачать подпись документа</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                    </ul>
                </template>
                <template v-if="data.additional_fields">
                    <div v-for="(item, key) in data.additional_fields" :key="key">
                        <span class="my-notification__additional-title">{{key}}</span>
                        <span class="wysiwyg my-notification__additional-text" v-html="item"></span>
                    </div>
                </template>
            </div>
        </template>
        <template #buttons>
            <button
                @click="closes"
                class="button-gray">Закрыть</button>
        </template>
    </modal>
</template>

<script>
    import Modal from './Modal';
    import BaseSpinner from './BaseSpinner';
    export default {
        name: "MyNotification",
        components: { Modal, BaseSpinner },
        props: {
            data: {type: Object, required: true}
        },
        data:() => ({
            isShow: false,
            dataMore: null,
            isLoading: true,
        }),
        computed: {

        },
        methods: {
            async fetchData(){
                this.isLoading = true;
                const { dispatch } = this.$store;
                const query = {
                    service_number: this.data.service_number
                };
                const response = await dispatch('fetchNotification', query);

                if (response.status){
                    this.dataMore = response.data;
                    this.isLoading = false;
                }
                else {
                    this.$notify({type: 'error', text: 'Получить уведомление не удалось'});
                    this.isShow = false;
                }
            },
            getFull(){
                this.isShow = true;
                if (!this.dataMore)
                    this.fetchData()
            },
            closes(){
                this.$emit('notify:close');
            }
        },
        created() {
            this.fetchData()
        }
    }
</script>

<style lang="scss">
    @import '../../sass/variables';
.my-notification {
    &__inner {
        display: flex;
        flex-wrap: nowrap;
        background-color: #ffffff;
        padding: 15px;
        & > * + * {
            margin-left: 10px;
        }
        &:hover{
            cursor: pointer;
            background-color: darken(#ffffff, 4%);
        }
    }
    &__field {
        &-header {
            font-weight: bolder;
        }
        &-text {

        }
    }

    &__descr {
        flex: 1 1 auto;
        display: flex;
        & > * + * {
            margin-left: 10px;
        }
        @media screen and (max-width: $break_sm) {
            flex-direction: column;
            & > * + * {
                margin-left: 0;
                margin-top: 10px;
            }
        }
    }

    &__icon {
        flex: 0 0 auto;
        width: 20px;
        height: 20px;
    }

    &__title {
        flex: 1 1 auto;
    }
    &__status {
        width: 30%;
        font-weight: bold;
        flex: 0 0 auto;
    }
    &__date {
        flex: 0 0 auto;
    }
    &__row {
        margin: 10px;
        display: flex;
        flex-direction: row;
        align-items: flex-start;
    }
    &__column {

    }

    &__additional {
        &-title {
            font-weight: bold;
            margin-left: 10px;
        }
        &-text {
            color: #7B898D;
            margin-left: 10px;
        }
        &-date {
        }
    }

    &__files {
        margin-left: 10px;
        &-title {
            font-weight: 400;
            font-style: italic;
        }
    }
    &__file {
        margin-left: 20px;
    }

    &__content{
        & > * + * {
            margin-top: 10px;
        }
    }

    &__spinner {
        background-color: inherit;
        box-shadow: none;
    }

    &__statuses-list {
        margin-top: 0;
        list-style-type: disc;
        list-style-position: outside;
        li {
            margin-left: 20px;
        }
    }
}
</style>
