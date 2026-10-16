import { FunctionComponent } from 'react';
import { NavLinkProps, NavLink as RouterLink } from 'react-router-dom';
import { Avatar, AvatarFallback, AvatarImage } from '../ui-shadcn/avatar';
import { Skeleton } from '../ui-shadcn/skeleton';
interface LinkProps extends NavLinkProps {
  avatarUrl?: string;
  title: string;
}

const Link: FunctionComponent<LinkProps> = ({ avatarUrl, title, ...props }) => {
  return (
    <RouterLink
      {...props}
      className={({ isActive }) =>
        `leading-[19.2px] -tracking-[0.02em] text-accent-foreground px-4 py-[12px] rounded-lg outline outline-border outline-2 transition-colors flex items-center justify-center gap-4 ${!isActive && 'outline-transparent'
        } ${props.className}`
      }
    >
      {avatarUrl && (
        <Avatar className='h-6 w-6'>
          <AvatarImage src={avatarUrl} />
          <AvatarFallback>
            <Skeleton className='w-6 h-6 rounded-full' />
          </AvatarFallback>
        </Avatar>
      )}
      <span className=''>{title}</span>
    </RouterLink>
  );
};

export default Link;
