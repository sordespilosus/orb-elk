<template>
    <div class="login">
        <div class="login__inner">
            <transition
                enter-active-class="animated faster fadeIn"
                leave-active-class="animated faster fadeOut"
                mode="out-in"
            >
                <div class="login__list" key="login-list">
                    <form
                        action="/login"
                        method="POST"
                        class="login__form"
                        key="login-form"
                    >
                        <input
                            name="_token"
                            type="hidden"
                            :value="$store.getters.csrf"
                        />

                        <form-field
                            :errors="serverErrors.login"
                            class="login__field"
                        >
                            <base-input
                                :value="serverData.login || $route.params.login"
                                :isError="!!serverErrors.login"
                                name="login"
                                :smallest="true"
                                placeholder="E-mail или логин"
                                class="login__input login__input--login"
                                autofocus
                            />
                        </form-field>

                        <form-field
                            :errors="serverErrors.password"
                            class="login__field"
                        >
                            <base-input
                                :isError="!!serverErrors.password"
                                name="password"
                                type="password"
                                :smallest="true"
                                placeholder="Пароль"
                                class="login__input login__input--password"
                            />
                        </form-field>

                        <div class="login__buttons">
                            <button class="login__button-login">Войти</button>
                            <a
                                href="/password/reset"
                                class="link login__button-restore"
                                >Забыли пароль?</a
                            >
                        </div>
                    </form>

                    <div class="login__list--links">
                        <a
                            :href="esiaLinks.url"
                            class="login__esia"
                            :class="{
                                'login__item--disabled': !esiaLinks.active,
                            }"
                        >
                            <img
                                :src="require(`@/images/${esiaLinks.id}.svg`)"
                                :alt="esiaLinks.text"
                                width="40"
                                height="40"
                                class="login__esia-image"
                            />

                            <div
                                v-text="esiaLinks.text"
                                class="link login__esia-link"
                            />
                        </a>

                        <a
                            v-for="link in links"
                            :key="link.id"
                            :href="link.url"
                            class="login__item"
                            :class="{ 'login__item--disabled': !link.active }"
                        >
                            <img
                                :src="require(`@/images/${link.id}.svg`)"
                                :alt="link.text"
                                width="40"
                                height="40"
                                class="login__item-image"
                            />

                            <div
                                v-text="link.text"
                                class="link login__item-link"
                            />
                        </a>
                    </div>
                </div>
            </transition>

            <modal
                v-if="isModalShown"
                @modal:close="isModalShown = false"
                title="Внимание!"
                :text="`Профиль, привязанный к почте ${serverData.login}, не подтверждён. На Вашу почту после регистрации было отправлено письмо со ссылкой для подтверждения аккаунта. Если письмо не пришло, мы можем отправить его повторно.<br>Отправить письмо еще раз?`"
                max-width="800"
            >
                <template #buttons>
                    <div
                        v-text="'Отправить письмо'"
                        @click="activateAgain"
                        class="button"
                    />
                    <a
                        v-text="'Отмена'"
                        @click="isModalShown = false"
                        class="button button--neutral"
                    />
                </template>
            </modal>

            <modal
                v-if="isActivationAgainModalShown"
                @modal:close="isActivationAgainModalShown = false"
                title="Письмо для подтверждения аккаунта отправлено"
                :text="`На ваш e-mail <b>${serverData.login}</b> отправлено письмо для подтверждения регистрации`"
                max-width="480"
                class="profile-page-register__modal"
            >
                <template #buttons>
                    <a
                        v-text="'Хорошо'"
                        @click="isActivationAgainModalShown = false"
                        class="button button--neutral"
                    />
                </template>
            </modal>
        </div>
    </div>
</template>

<script>
import FormField from "@/js/components/FormField";
import BaseInput from "@/js/components/BaseInput";
import Modal from "@/js/components/Modal";

export default {
    name: "Login",
    components: { FormField, BaseInput, Modal },
    props: {
        serverData: { type: Object, default: () => ({}) },
        serverErrors: { type: Object, default: () => ({}) },
        isProfileVerified: { type: Boolean, required: true },
        withoutMethod: { type: String, default: null },
        authMethods: { type: Array, default: () => [] },
        onlyLogin: { type: Boolean, default: false },
    },

    data() {
        return {
            isModalShown: !this.isProfileVerified,
            isLoginListShown:
                !this.serverData.login &&
                !this.$route.params.login &&
                !this.onlyLogin,
            isActivationAgainModalShown: false,
        };
    },

    computed: {
        esiaLinks() {
            return {
                id: "esia",
                text: "Вход через Госуслуги",
                url: "/loginESIA",
                active:
                    this.withoutMethod !== "esia" &&
                    this.authMethods.includes("esia"),
            };
        },
        links() {
            return [
                {
                    id: "vk",
                    text: "Вход через Вконтакте",
                    url: "/login_from_vk",
                    active:
                        this.withoutMethod !== "vk" &&
                        this.authMethods.includes("vk"),
                },
                // {
                // 	id: "fb",
                // 	text: "Вход через Facebook",
                // 	url: "/login_from_fb",
                // 	active: this.withoutMethod !== "fb" && this.authMethods.includes("fb"),
                // },
            ];
        },

        existSocialMethods() {
            const existingMethods = this.links.filter(
                (method) => method.active
            );
            return existingMethods.length > 0;
        },
    },

    methods: {
        async activateAgain() {
            const { commit, dispatch } = this.$store;
            commit("setLoader", true);
            const response = await dispatch("activateAgain", {
                email: this.serverData.login,
            });
            commit("setLoader", false);
            if (response && response.status) {
                this.isModalShown = false;
                this.isActivationAgainModalShown = true;
            } else
                this.$notify({
                    type: "error",
                    text: "Отправить письмо не удалось",
                });
        },
    },
};
</script>

<style lang="scss">
@import "~@/sass/variables";

.login {
    &__list {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        grid-column-gap: 20px;

        &--links {
            display: grid;
            grid-template-rows: 79px repeat(2, 40px);
            grid-row-gap: 10px;
        }
    }

    &__esia {
        display: flex;
        align-items: center;
        justify-content: center;

        background-color: #fff;
        border-radius: 10px;

        opacity: 0.8;
        transition: opacity 0.3s;
        cursor: pointer;

        &--disabled {
            pointer-events: none;
            opacity: 0.6;

            color: #7b898d;
        }

        &:hover {
            opacity: 1;
        }

        &--disabled {
            pointer-events: none;
            opacity: 0.6;

            .login__item-link {
                color: #7b898d;
            }
        }

        &-image {
            flex: 0 1 auto;
            width: 50px;
            height: 50px;
        }

        &-link {
            margin-left: 20px;
            flex: 1 0 auto;
            max-width: 100px;

            font-size: 15px;
            font-weight: bold;
            line-height: 16px;
        }
    }

    &__item {
        display: flex;
        align-items: center;
        justify-content: center;

        background-color: #fff;
        border-radius: 10px;

        opacity: 0.8;
        transition: opacity 0.3s;
        cursor: pointer;

        &--disabled {
            pointer-events: none;
            opacity: 0.6;

            color: #7b898d;
        }

        &:hover {
            opacity: 1;
        }

        &--disabled {
            pointer-events: none;
            opacity: 0.6;

            .login__item-link {
                color: #7b898d;
            }
        }
    }

    &__item-image {
        flex: 0 0 auto;
        width: 25px;
        height: 25px;
    }

    &__item-link {
        margin-left: 15px;
        flex: 1 0 auto;
        max-width: 150px;

        font-size: 13.5px;
        font-weight: bold;
        line-height: 16px;
    }

    &__form {
        > * + * {
            margin-bottom: 10px;
        }
    }

    &__input {
        position: relative;

        > * {
            box-shadow: none !important;
        }
    }

    &__buttons {
        display: flex;
        align-items: center;
        justify-content: space-between;

        > * + * {
            margin-left: 20px;
        }
    }

    &__button-login {
        width: 100%;
        background-color: $color-accent;
        border-radius: 10px;
        cursor: pointer;
        color: white;
        padding: 5px 12px;
        flex: 0 1 auto;
        max-width: 220px;
    }

    &__button-restore {
        flex: 0 0 auto;

        font-size: 14px;
        line-height: 15px;
        font-weight: bold;
    }

    &__links {
        margin-top: 20px;

        > * + * {
            margin-top: 20px;
        }
    }

    &__links-title {
        display: flex;
        align-items: center;
        font-size: 13px;
        line-height: 15px;
        text-align: center;

        color: #d3d8db;

        &::before,
        &::after {
            content: "";
            flex: 1 1 auto;
            height: 1px;
            background-color: currentColor;
        }

        &::before {
            margin-right: 10px;
        }

        &::after {
            margin-left: 10px;
        }
    }

    &__links-list {
        display: flex;
        align-items: center;
        justify-content: center;

        > * + * {
            margin-left: 20px;
        }
    }

    &__links-item {
        flex: 0 0 auto;
    }

    &__links-image {
        display: block;

        width: 40px;
        height: 40px;
    }

    &__modal-text {
        margin-top: 20px;
    }
}
</style>
