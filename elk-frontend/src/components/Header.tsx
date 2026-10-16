import { FunctionComponent } from 'react';
import LogoSVG from '@/assets/logo/logo.svg?react';
import Link from './ui/Link';
import { getNavLinks } from '@/config/navigation';
import { NavLink } from 'react-router-dom';
import { $url } from '@/utils/config';

interface FunctionComponentProps {
  user: {
    role: string;
    userName: string;
    avatarUrl: string;
  };
}
const Header: FunctionComponent<FunctionComponentProps> = ({ user }) => {
  return (
    <header className='max-w-container mx-auto flex items-center justify-between mobile:py-[13px] py-2'>
      <NavLink to='/' className='mr-8'>
        <LogoSVG height={'auto'} className='max-mobile:h-12' />
      </NavLink>
      <div className='grid grid-flow-col auto-cols-fr gap-2 md:gap-8 max-mobile:hidden'>
        {getNavLinks(user.role, user.userName, `${$url}/storage/avatars/${user.avatarUrl}`).map((link, index) => (
          <Link key={index} to={link.path} title={link.label} avatarUrl={link.image} />
        ))}
      </div>
    </header>
  );
};

export default Header;
