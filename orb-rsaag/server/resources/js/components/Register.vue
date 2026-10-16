<template>
  <div class="register">
    <div class="register__inner">
      <form
      v-if="!hasSuccessRegister"
      action="/register"
      method="POST"
      class="register__form">

        <input
        type="hidden"
        name="_token"
        :value="$store.getters.csrf"/>

        <form-field
        label="Имя"
        :isRequired="true"
        :errors="getErrors('name')"
        class="register__field">
          <base-input
          v-model="formData.name"
          v-validate="'required|onlyCyrillic'"
          :isError="!!getErrors('name').length"
          data-vv-as="Имя"
          name="name"
          placeholder="Имя"/>
        </form-field>

        <form-field
        label="Фамилия"
        :isRequired="true"
        :errors="getErrors('surname')"
        class="register__field">
          <base-input
          v-model="formData.surname"
          v-validate="'required|onlyCyrillic'"
          :isError="!!getErrors('surname').length"
          data-vv-as="Фамилия"
          name="surname"
          placeholder="Фамилия"/>
        </form-field>

        <form-field
        label="Отчество"
        :errors="getErrors('patroname')"
        class="register__field">
          <base-input
          v-model="formData.patroname"
          v-validate="'cyrillic'"
          :isError="!!getErrors('patroname').length"
          data-vv-as="Отчество"
          name="patroname"
          placeholder="Отчество"/>
        </form-field>

        <form-field
        label="E-mail"
        :isRequired="true"
        :errors="getErrors('email')"
        class="register__field">
          <base-input
          v-model="formData.email"
          v-validate="'required|email'"
          :isError="!!getErrors('email').length"
          data-vv-as="E-mail"
          name="email"
          placeholder="E-mail"/>
        </form-field>

        <div
        v-if="notVerifiedEmail && formData.email && notVerifiedEmail === formData.email.toLowerCase()"
        class="">
          Профиль, привязанный к почте {{notVerifiedEmail}}, не подтверждён. На Вашу почту после регистрации было отправлено письмо со ссылкой для подтверждения аккаунта. Если письмо не пришло, мы можем отправить его повторно. <span @click="activateAgain" class="link">Отправить письмо еще раз</span>?
        </div>
        <div
        v-if="hasUser && formData.email  && hasUser === formData.email.toLowerCase()"
        class="register__links-container">
          <router-link
          :to="{name: 'login', params: { login: hasUser } }"
          v-text="'Войти'"
          class="link" />
          <a
          :href="`/password/reset?email=${encodeURIComponent(hasUser)}`"
          v-text="'Восстановить пароль'"
          class="link" />
        </div>


        <form-field
        label="Населенный пункт"
        class="register__field">

          <input
          type="hidden"
          name="city"
          :value="formData.city"/>

          <field-dropdown
          v-model="formData.city"
          v-validate
          label-name="area_name"
          track-by="area_id"
          value-name="area_id"
          :data="cities"
          placeholder="Выберите город"
          class="register__city-dropdown"
          searchable>
            <template #option="{option}">
              <div
              v-text="option.area_name"
              :class="getCityOptionModifiers(option)"/>
            </template>
          </field-dropdown>

        </form-field>

        <form-field
        label="Дата рождения"
        :errors="getErrors('birthday')"
        class="register__field">
          <base-input
          v-model="formData.birthday"
          v-validate="{
            date_format: 'dd.MM.yyyy',
            date_between: birthdayRange
          }"
          :mask="{
            alias: 'datetime', 
            inputFormat: 'dd.mm.yyyy', 
            placeholder: 'ДД.ММ.ГГГГ',
            insertModeVisual: false,
            showMaskOnHover: true,
          }"
          data-vv-as="Дата рождения"
          :isError="!!getErrors('birthday').length"
          name="birthday"/>
        </form-field>

        <form-field
        label="Пол"
        :errors="getErrors('gender')"
        class="register__field">

          <radio-group
          v-model="formData.gender"
          :items="[{value: 'male', label: 'Мужской'}, {value: 'female', label: 'Женский'}]"
          v-validate="'gender'"
          data-vv-as="Пол"
          name="gender"/>
        </form-field>


        <form-field
        label="Пароль"
        :isRequired="true"
        :errors="getErrors('password')"
        class="register__field">
          <base-input
          v-model="formData.password"
          v-validate="'required|min:6'"
          :isError="!!getErrors('password').length"
          data-vv-as="Пароль"
          name="password"
          type="password"
          placeholder="Пароль"/>
        </form-field>

        <form-field
        label="Подтверждение пароля"
        :isRequired="true"
        :errors="getErrors('password_confirmation')"
        class="register__field">
          <base-input
          v-model="formData.password_confirmation"
          v-validate="{required: true, password_confirmation: formData.password, password: true}"
          :isError="!!getErrors('password_confirmation').length"
          data-vv-as="Подтверждение пароля"
          name="password_confirmation"
          type="password"
          placeholder="Подтверждение пароля"/>
        </form-field>

        <form-field
        :errors="veeErrors.collect('is_accepted')"
        class="register__field">
          <base-checkbox
          v-model="formData.is_accepted"
          v-validate="'required|is_equal'"
          :input-value="formData.is_accepted"
          data-vv-as="Принимаю условия пользовательского соглашения"
          name="is_accepted">
            <template slot="label">
              Я принимаю условия
              <a
              v-text="'Пользовательского соглашения'"
              @click="showAgreementModal"
              class="link"/>
               и ознакомлен с <a class="link" v-text="'Политикой в отношении обработки персональных данных'" target="_blank" href="https://cit.orb.ru/upload/uf/46a/Politika-GKU-TSIT-po-PDn.pdf" />
            </template>
          </base-checkbox>
        </form-field>

        <form-field
        v-if="$store.state.needRecaptcha"
        :errors="$store.state.recaptchaVersion == 2 ? veeErrors.collect('g-recaptcha-response') : []"
        class="register__field">
          <input
          type="hidden"
          name="g-recaptcha-response"
          :value="formData['g-recaptcha-response']"
          v-validate="$store.state.recaptchaVersion == 2 ? 'required' : ''"
          data-vv-as="Я не робот"/>
          <recaptcha
          @recaptcha:validate="fromRecaptcha"
          @recaptcha:token="setRecaptcha($event)"
          @recaptcha:expired="recaptchaExpired"
          @recaptcha:error="recaptchaError"
          :version="$store.state.recaptchaVersion"
          class="register__recaptcha"/>
        </form-field>

        <input
        v-if="formData.vk_user_id"
        id="vk_user_id"
        type="hidden"
        name="vk_user_id"
        :value="formData.vk_user_id"/>

        <input
        v-if="formData.fb_user_id"
        id="fb_user_id"
        type="hidden"
        name="fb_user_id"
        :value="formData.fb_user_id"/>

        <input
        v-if="formData.esia_user_id"
        id="esia_user_id"
        type="hidden"
        name="esia_user_id"
        :value="formData.esia_user_id"/>

        <portal to="page-auth-buttons">
          <div class="container register__buttons">
            <button
            v-text="'Зарегистрироваться'"
            @click="sendForm(false)"
            :disabled="isButtonDisabled"
            type="button"
            class="register__button"
            :class="isButtonDisabled ? 'button button--neutral' : 'button'"/>
          </div>
        </portal>

      </form>

      <div
      v-else
      class="register__success">
        <div
        v-text="'Благодарим за регистрацию'"
        class="heading-2 register__success-title" />
        <div
        class="register__success-text wysiwyg">
          На Ваш e-mail <b>{{formData.email || hashParams.email}}</b> отправлено письмо для подтверждения регистрации
        </div>
        <form
        class="register__form">
          <form-field
          label="Код подтверждения"
          :errors="getErrors('code')"
          class="register__field">
            <base-input
            v-model="registrationCode"
            v-validate="'required|length_numbers:6'"
            :mask="'999999'"
            :isError="!!getErrors('code').length"
            data-vv-as="Код подтверждения"
            name="code"
            placeholder="Код подтверждения"/>
          </form-field>

          <div class="register__buttons register__send-code-buttons">
            <button
            v-text="'Подтвердить'"
            @click="sendCode"
            :disabled="isButtonDisabled || (registrationCode && registrationCode.length !== 6)"
            type="button"
            class="register__button button button--positive"/>
          </div>

        </form>

        <div
        class="register__back-to-registration">
          <span
          @click="backToRegistration"
          v-text="'Зарегистрироваться заново'"
          class="link"/>
        </div>

      </div>

      <modal
      v-if="isModalShown"
      @modal:close="isModalShown = false"
      :text="agreement"/>
    </div>
  </div>
</template>

<script>
/* global yaCounter43923864 */

import { format, addYears } from 'date-fns';
import { cloneDeep, every, map, filter } from 'lodash';

import FormField from '@/js/components/FormField';

import BaseInput from '@/js/components/BaseInput';
import BaseCheckbox from '@/js/components/BaseCheckbox';
import FieldDropdown from '@/js/components/FieldDropdown';

import RadioGroup from '@/js/components/RadioGroup';

import PhoneVerification from '@/js/components/PhoneVerification';

import Recaptcha from '@/js/components/Recaptcha';
import Modal from '@/js/components/Modal';

import { setCookie, getCookie, deleteCookie } from '@/js/helpers';

export default {
  name: 'Register',
  components: {
    FormField,
    BaseInput,
    BaseCheckbox,
    FieldDropdown,
    RadioGroup,
    PhoneVerification,
    Recaptcha,
    Modal
  },

  props: {
    cities: { type: Array },
    registerData: { type: Object, default: () => ({}) },
    clientId: { type: Number }
  },

  data() {
    return {
      serverErrors: {},

      formData: {
        name: null,
        surname: null,
        patroname: null,
        email: null,
        phone: null,
        city: null,
        birthday: null,
        gender: null,
        is_accepted: null,
        vk_user_id: null,
        fb_user_id: null,
        esia_user_id: null,
        sms_hash: null,
        password: null,
        password_confirmation: null,
        'g-recaptcha-response': null,
        client_id: this.clientId,
        ...this.registerData
      },

      isPhoneVerificationShown: false,
      recaptchaToken: null,

      agreement: null,
      isModalShown: false,
      hasSuccessRegister: false,
      registrationCode: null,
      hasUser: null,
      notVerifiedEmail: null,

      phoneValidator: 'phone',

      hashParams: {},
    };
  },

  computed: {
    isPhoneVerificationNeeded() {
      const hasPhoneData = !!this.formData.phone;
      const hasErrors = !!this.getErrors('phone').length;
      const isVerified = !!this.formData.sms_hash;

      return (
        hasPhoneData &&
        !hasErrors &&
        !isVerified &&
        !this.isPhoneVerificationShown &&
        this.$store.state.isSmsSecurity
      );
    },

    isButtonDisabled() {
      return every(this.veeFields, { dirty: false });
    },

    birthdayRange() {
      const formatter = val => format(val, 'dd.MM.yyyy');
      return [addYears(new Date, -125), new Date()]
        .map(formatter)
        .concat('[]');
    }
  },

  methods: {
    getErrors(field) {
      return this.$store.getters.getErrors(
        field,
        this.veeErrors,
        this.serverErrors
      );
    },

    getCityOptionModifiers(option) {
      const isParent = option.lvl == 1;

      return {
        'page-register__city-dropdown__parent': isParent,
        'page-register__city-dropdown__child': !isParent
      };
    },

    handlePhoneVerification(sms_hash) {
      this.formData.sms_hash = sms_hash;
      this.isPhoneVerificationShown = false;
    },

    async sendForm(fromRecaptcha = false) {

      if (this.$store.state.needRecaptcha && this.$store.state.recaptchaVersion == 2 && !fromRecaptcha){
        grecaptcha.execute();
        return;
      }
      const hasNoErrors = await this.$validator.validateAll();
      const isProduction = process.env.MIX_APP_ENV === 'production';
      if (!hasNoErrors) {
        this.$notify({ type: 'error', text: 'Заполните форму правильно' });
        return;
      }
      const { commit, dispatch } = this.$store;
      commit('setLoader', true);
      const response = await dispatch('register', this.formData);
      commit('setLoader', false);
      if (response && response.status) {
        this.hasSuccessRegister = true;
        let hash = `#mode=code&email=${this.formData.email}`;
        if (this.clientId || this.formData.client_id){
          hash += `&client_id=${this.clientId || this.formData.client_id}`;
        }
        this.$router.push({name: this.$route.name, hash });
        if (isProduction && typeof yaCounter56536234 !== 'undefined')
          yaCounter56536234.reachGoal('rsaag-registration');

        setCookie('rsaag_registration', JSON.stringify({
          email: this.formData.email,
          client_id: this.clientId || this.formData.client_id
        }), {
          expires: this.$store.state.registrationCodeMinutesLifetime * 60
        });

      } else {
        this.$notify({ type: 'error', text: response && response.need_phone ? 'Для завершения регистрации необходимо указать номер телефона' : 'Зарегистрироваться не удалось' });
        if (response){
          if (response.errors) this.serverErrors = response.errors;
          if (response.not_verified_email) this.notVerifiedEmail = response.not_verified_email;
          if (response.has_user) this.hasUser = response.has_user;
          if (response.need_phone) this.phoneValidator = 'required|phone'
        }
      }
    },

    async showAgreementModal() {
      if (!this.agreement) {
        const { commit, dispatch } = this.$store;
        commit('setLoader', true);
        this.agreement = await dispatch('fetchAgreement');
        commit('setLoader', false);
      }

      this.isModalShown = true;
    },

    setRecaptcha(token) {
      this.formData['g-recaptcha-response'] = token;
      this.$nextTick(() => {
        this.$validator.validate('g-recaptcha-response');
      });
    },

    backToRegistration(){
      this.$router.push({name: this.$route.name});
      this.hashParams = {};
      deleteCookie('rsaag_registration');
      this.hasSuccessRegister =false
    },

    async sendCode(){
      const hasNoErrors = await this.$validator.validateAll();
      if (!hasNoErrors) {
        this.$notify({ type: 'error', text: 'Заполните форму правильно' });
        return;
      }
      const { commit, dispatch } = this.$store;
      commit('setLoader', true);
      const response = await dispatch('sendRegistrationCode', { code: this.registrationCode, email: this.formData.email || this.hashParams.email, client_id: this.clientId || this.formData.client_id || this.hashParams.client_id, password: this.formData.password});
      commit('setLoader', false);
      if (response.status){
        this.$notify({ type: 'success', text: 'Профиль успешно активирован' });
        deleteCookie('rsaag_registration');
        window.location.href = '/';
      }
      else{
        this.$notify({ type: 'error', text: 'Введите правильный код' });
        if (response && response.errors) this.serverErrors = response.errors;
      }
    },

    async activateAgain() {
      const { commit, dispatch } = this.$store;
      commit('setLoader', true);
      const response = await dispatch('activateAgain', {
        email: this.notVerifiedEmail
      });
      commit('setLoader', false);
      if (response && response.status) {
        this.$notify({ type: 'success', text: `На Ваш e-mail ${this.notVerifiedEmail} отправлено письмо для подтверждения регистрации` });
        this.formData.email = this.notVerifiedEmail;
        this.$router.push({name: this.$route.name, hash: `#mode=code&email=${this.notVerifiedEmail}`});
        this.notVerifiedEmail = null;
        this.hasSuccessRegister = true;
      } else
        this.$notify({ type: 'error', text: 'Отправить письмо не удалось' });
    },

    verifyError(errors){
      this.isPhoneVerificationShown = false;
      this.serverErrors = {...this.serverErrors, ...errors};
    },

    fromRecaptcha(key){
      this.formData['g-recaptcha-response'] = key;
      this.$nextTick(() => {
        this.sendForm(true);
      });
    },

    recaptchaExpired(){
      this.formData['g-recaptcha-response'] = null;
    },

    recaptchaError(error){
      //this.$notify({type: 'error', text: 'При проверке Recaptcha произошла ошибка, попробуйте обновить страницу'});
      this.formData['g-recaptcha-response'] = null;
    },
  },

  created() {
    let hashParams = this.$route.hash ? this.$route.hash.replace('#', '') : null;
    if (hashParams){
      hashParams = map(hashParams.split('&'), element => {
        let param = element.split('=');
        if (param.length === 2){
          this.hashParams[param[0]] = param[1];
          return param;
        }
        else {
          return null;
        }
      });
    }
    let params = getCookie('rsaag_registration');
    if (!!params && params !== ''){
      params = JSON.parse(params);
      if (params && params.email) {
        this.hashParams['email'] = params.email;
        this.hashParams['mode'] = 'code';
        this.hashParams['client_id'] = params.client_id || null;
      }
    }

    this.hasSuccessRegister = this.hashParams.mode === 'code';

  }
};
</script>

<style lang="scss">
  @import '../../sass/variables';
.register {
  &__form {
    > * + * {
      margin-top: 15px;
    }
  }

  &__button {
    width: 195px;
  }

  &__success {
    background-color: #fff;
    padding: 10px;
    border-radius: 10px;
    > * + * {
      margin-top: 10px;
    }
  }

  &__success-title, &__success-text {
    text-align: center;
  }

  &__back-to-registration {
    text-align: center;
    margin: 10px;

    &:hover{
      color: $color-accent;
      text-decoration: underline;
    }
  }

  &__send-code-buttons {
    display: flex;
    justify-content: center;
  }

  &__links-container {
    display: flex;

    > * {
      flex: 0 1 auto;
    }

    > * + * {
      margin-left: 20px;
    }
  }
}
</style>
