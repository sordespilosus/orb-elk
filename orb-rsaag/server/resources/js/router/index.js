import Vue from 'vue';
import Router from 'vue-router';
import PageAdminUsers from '../components/PageAdminUsers';
import PageAdminRoles from '../components/PageAdminRoles';
import PageAdminClients from '../components/PageAdminClients';
import PageAdminLogs from '../components/PageAdminLogs';
import PageAdminAgreement from '../components/PageAdminAgreement';
import PageAdminDescription from '../components/PageAdminDescription';
import PageMyNotifications from '../components/PageMyNotifications';
import PageNotificationPrint from '../components/PageNotificationPrint';
import PageAuth from '@/js/components/PageAuth';
import PageAdminClientsModeration from '../components/PageAdminClientsModeration';
import PageHome from '../components/PageHome';
import MailingModule from "../components/PageMailingModule";

Vue.use(Router);

const routes = [
    {
        name: 'adminUsers',
        path: '/admin/page/users',
        components: { adminSection: PageAdminUsers }
    },

    {
        name: 'adminRoles',
        path: '/admin/page/roles',
        components: { adminSection: PageAdminRoles }
    },

    {
        name: 'adminClients',
        path: '/admin/page/clients',
        components: { adminSection: PageAdminClients }
    },

    {
        name: 'adminClientsModeration',
        path: '/admin/page/clients_moderation',
        components: { adminSection: PageAdminClientsModeration }
    },

    {
        name: 'adminLogs',
        path: '/admin/page/logs',
        components: { adminSection: PageAdminLogs }
    },

    {
        name: 'adminAgreement',
        path: '/admin/page/user_agreement',
        components: { adminSection: PageAdminAgreement }
    },

    {
        name: 'adminDescription',
        path: '/admin/page/system_description',
        components: { adminSection: PageAdminDescription }
    },

	{
		name: "mailingModule",
		path: "/admin/page/mailing_module",
		components: { adminSection: MailingModule },
	},

	{
		name: "myNotifications",
		path: "/my_notifications",
		component: PageMyNotifications,
	},

	{
		name: "notificationPrint",
		path: "/my_notifications/print/:id",
		component: PageNotificationPrint,
		props: route => ({ query: route.query }),
	},

    {
        name: 'admin',
        path: '/admin',
        redirect: { name: 'adminUsers' },
        components: { adminSection: PageAdminUsers }
    },

    {
        name: 'login',
        path: '/login',
        components: { PageAuth }
    },

    {
        name: 'register',
        path: '/register',
        component: { PageAuth }
    },

    {
        name: 'pageHome',
        path: '/',
        component: PageHome,
    },

    {
        name: 'pageNotices',
        path: '/notices',
        component: PageHome,
    }
];

export default new Router({
    mode: 'history',
    routes,
    scrollBehavior(to, from, savedPosition) {
        return { x: 0, y: 0 }; //сброс позиции прокрутки при переходе по ссылкам
    }
});
