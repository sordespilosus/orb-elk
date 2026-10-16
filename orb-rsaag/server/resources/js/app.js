import '../icons/build';
import 'vue-svgicon/dist/polyfill';

import Vue from 'vue';
import router from './router';
import store from './store/index.js';
import { validators, messages } from './validation';

import SvgIcon from 'vue-svgicon';
import VeeValidate, { Validator } from 'vee-validate';
import ru from 'vee-validate/dist/locale/ru';

import { vMask, vTippy } from './directives';

import Alerts from 'vue-notification';
import PortalVue from 'portal-vue';

import App from './components/App';
import PageAuth from '@/js/components/PageAuth';
import PageHome from './components/PageHome';
import PageProfile from './components/PageProfile';
import PageAdmin from './components/PageAdmin';
import PagePhoneVerification from './components/PagePhoneVerification';
import PagePasswordReset from './components/PagePasswordReset';
import PageNotificationPrint from './components/PageNotificationPrint';
import PageOAuth from './components/PageOAuth';
import PageAgreement from './components/PageAgreement';
import Notification from './components/Notification';
import BasePage from './components/BasePage';
import PageAddEmail from './components/PageAddEmail';
import PageQuestion from './components/PageQuestion';
import PageRedirect from './components/PageRedirect';
import PageMyClients from './components/PageMyClients';
import PageUnsubscribeMailing from './components/PageUnsubscribeMailing'

Vue.use(SvgIcon, { tagName: 'icon' });

Vue.use(VeeValidate, {
    errorBagName: 'veeErrors',
    fieldsBagName: 'veeFields'
});

Validator.localize('ru', {...ru, messages });

Object.entries(validators).forEach(([name, options]) =>
    Validator.extend(name, options)
);

Vue.use(PortalVue);
Vue.use(Alerts);

Vue.directive('mask', vMask);
Vue.directive('tippy', vTippy());

const app = new Vue({
    el: '#app',
    components: {
        App,
        BasePage,
        PageAuth,
        PageHome,
        PageProfile,
        PageAdmin,
        PagePhoneVerification,
        PagePasswordReset,
        PageUnsubscribeMailing,
        PageNotificationPrint,
        PageOAuth,
        PageAgreement,
        Notification,
        PageAddEmail,
        PageQuestion,
        PageRedirect,
        PageMyClients
    },
    store,
    router
});
