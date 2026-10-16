import { userRole } from '@/types/role';
import Info from '@/assets/icon/info.svg?react';
import Menu from '@/assets/icon/menu.svg?react';
import Profile from '@/assets/icon/profile.svg?react';
import People from '@/assets/icon/people.svg?react';
// import PersonGear from '@/assets/icon/person-gear.svg?react';
import Grid from '@/assets/icon/grid.svg?react';
import Clock from '@/assets/icon/clock.svg?react';
import FileText from '@/assets/icon/file-text.svg?react';
import ChatLeftText from '@/assets/icon/chat-left-text.svg?react';

type NavLink = { path: string; label: string; image?: string; isDesktopOnly?: boolean; icon?: JSX.Element };

const commonNavLinks: NavLink[] = [{ path: '/service', label: 'Доступные системы', icon: <Menu /> }];

const roleSpecificNavLinks: { [key in string]?: NavLink[] } = {
  [userRole.admin]: [{ path: '/admin', label: 'Админ-панель', isDesktopOnly: true }],
  [userRole.user]: [{ path: '/about', label: 'О сервисе', icon: <Info /> }],
};

export function getNavLinks(role: string, userName: string, avatarUrl: string): NavLink[] {
  const specificLinks = roleSpecificNavLinks[role] || [];

  return [...commonNavLinks, { path: '/', label: userName, image: avatarUrl, icon: <Profile /> }, ...specificLinks];
}

//admin list:
interface AdminNavListType {
  path: string;
  label: string;
  icon?: JSX.Element;
  target?: '_blank' | '_self';
}

export function getAdminNavLinks(): AdminNavListType[] {
  return [
    { path: '/admin', label: 'Пользователи', icon: <People /> },
    // { path: '/admin/roles', label: 'Роли', icon: <PersonGear /> },
    { path: '/admin/systems', label: 'Доступные системы', icon: <Grid /> },
    { path: '/admin/logs', label: 'Лог запросов к сервису', icon: <Clock /> },
    { path: '/admin/system-description', label: 'Описание системы', icon: <FileText /> },
    {
      path: 'https://listmonk.ag.dev-vt2b.ru/admin',
      label: 'Рассылка сообщений',
      icon: <ChatLeftText />,
      target: '_blank',
    },
  ];
}

export const adminSystemsRoutes = {
  systems: '/admin/systems',
  createSystem: 'create',
  editSystem: (id: string | number) => `edit/${id}`,
};

export const adminUsersRoutes = {
  users: '/admin',
  editUser: (id: string | number) => `/admin/users/edit/${id}`,
};
