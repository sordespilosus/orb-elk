import { getNavLinks } from '@/config/navigation';
import { FunctionComponent } from 'react';
import AppBarButton from './ui/AppBarButton';

interface AppBarProps {
  user: {
    role: string;
    userName: string;
    avatarUrl: string;
  };
}
const AppBar: FunctionComponent<AppBarProps> = ({ user }) => {
  return (
    <div className='w-full h-app-bar mobile:hidden bg-white bottom-0 inset-x-0 fixed pt-4 px-4 rounded-tr-[32px] rounded-tl-[32px] grid grid-flow-col auto-cols-fr text-xs text-center text-muted-foreground'>
      {getNavLinks(user.role, user.userName, user.avatarUrl).map((link, index) =>
        link.isDesktopOnly ? null : (
          <AppBarButton
            key={index}
            title={link.path === '/' ? 'Профиль' : link.label}
            patch={link.path}
            icon={link.icon}
          />
        )
      )}
    </div>
  );
};

export default AppBar;
