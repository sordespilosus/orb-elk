import MainLayout from '@/components/layout/MainLayout';
import PrivateRoute from '@/components/PrivateRoute';
import LoginPage from '@/pages/LoginPage';
import ProfilePage from '@/pages/ProfilePage';
import ServicePage from '@/pages/ServicePage';
import { createBrowserRouter, Navigate } from 'react-router-dom';
import AdminLayout from '@/components/layout/AdminLayout.tsx';
import AdminUserPage from '@/pages/admin/AdminUserPage.tsx';
import UserPage from '@/pages/admin/UserPage.tsx';
import NotFoundPage from '@/pages/NotFoundPage.tsx';
import SystemDescriptionPage from '@/pages/admin/SystemDescriptionPage';
import { SecretLoginPage } from '@/pages/SecretLoginPage';
import AvailableSystemsPage from '@/pages/admin/AvailableSystemsPage.tsx';
import LogPage from '@/pages/admin/LogPage.tsx';
import SendingMessagesPage from '@/pages/admin/SendingMessagesPage.tsx';
import SystemPage from '@/pages/admin/SystemPage.tsx';
import { FromESIA } from '@/pages/FromESIA';
import { FromESIA2 } from '@/pages/FromEsia2';
import ForbiddenPage from '@/pages/ForbiddenPage';
import AboutPage from '@/pages/AboutPage';

const router = createBrowserRouter([
  {
    element: <MainLayout />,
    children: [
      {
        path: '/',
        element: <PrivateRoute isAllowed={true} />,
        children: [{ path: '', element: <ProfilePage /> }],
      },
      {
        path: '/profile',
        element: <Navigate to='/' replace />,
      },
      {
        path: 'service',
        element: <PrivateRoute isAllowed={true} />,
        children: [{ path: '', element: <ServicePage /> }],
      },
      {
        path: 'about',
        element: <AboutPage />,
      },
      {
        path: 'admin',
        element: (
          <PrivateRoute isAllowed={true} isAdmin={true}>
            <AdminLayout />
          </PrivateRoute>
        ),
        children: [
          { path: '', element: <AdminUserPage /> },
          { path: 'users/edit/:userId', element: <UserPage /> },
          {
            path: 'systems',
            children: [
              { path: '', element: <AvailableSystemsPage /> },
              { path: 'edit/:systemId', element: <SystemPage /> },
              { path: 'create', element: <SystemPage /> },
            ],
          },
          { path: 'logs', element: <LogPage /> },
          { path: 'system-description', element: <SystemDescriptionPage /> },
          { path: 'sending-message', element: <SendingMessagesPage /> },
        ],
      },
    ],
  },
  {
    path: 'login',
    element: <LoginPage />,
  },
  {
    path: '/secret/login',
    element: <SecretLoginPage />,
  },
  {
    path: '*',
    element: <NotFoundPage />,
  },
  {
    path: '403',
    element: <ForbiddenPage />,
  },
  {
    path: 'fromESIA',
    element: <FromESIA />,
  },
  {
    path: 'fromEsia2',
    element: <FromESIA2 />,
  },
]);

export default router;
