import { FC } from 'react';
import { NavLink, useMatch, useResolvedPath } from 'react-router-dom';
import { Button } from '@/components/ui-shadcn/button.tsx';

interface AdminNavItemProps {
  patch: string;
  label: string;
  icon?: JSX.Element;
  target?: '_blank' | '_self';
}

const AdminNavItem: FC<AdminNavItemProps> = ({ patch, icon, label, target }) => {
  const resolvedPath = useResolvedPath(patch);
  const isActive = useMatch({ path: resolvedPath.pathname, end: true });
  return (
    <Button isActive={!!isActive} className={'justify-start px-3.5 py-4 gap-4'} asChild variant={'outline'}>
      <NavLink to={patch} target={target}>
        {icon} {label}
      </NavLink>
    </Button>
  );
};

export default AdminNavItem;
