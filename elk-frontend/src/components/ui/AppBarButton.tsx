import React from 'react';
import { FunctionComponent } from 'react';
import { NavLink, useMatch, useResolvedPath } from 'react-router-dom';

interface AppBarButtonProps {
  title: string;
  patch: string;
  icon?: JSX.Element;
}
const AppBarButton: FunctionComponent<AppBarButtonProps> = ({ title, patch, icon }) => {
  const resolvedPath = useResolvedPath(patch);
  const isActive = useMatch({ path: resolvedPath.pathname, end: true });
  const coloredIcon = !icon
    ? null
    : React.cloneElement(icon, {
        className: `${icon.props.className || ''} ${isActive && '!fill-white'}`,
      });
  return (
    <NavLink to={patch} className={`flex items-center flex-col ${isActive && 'text-accent-foreground-header'}`}>
      <div className={`bg-background p-[13px] rounded-full mb-2 ${isActive && 'bg-foreground text-accent-foreground'}`}>
        {coloredIcon}
      </div>
      <span className='text-xs leading-none tracking-tight'>{title}</span>
    </NavLink>
  );
};

export default AppBarButton;
