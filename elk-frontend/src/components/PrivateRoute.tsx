import { FC, ReactNode } from 'react';
import { Navigate, Outlet } from 'react-router-dom';
import { useUserProfileQuery } from '@/api/user';

interface PrivateRouteProps {
  isAllowed: boolean;
  isAdmin?: boolean;
  children?: ReactNode;
}

const PrivateRoute: FC<PrivateRouteProps> = ({ isAllowed, isAdmin, children }) => {
  const { data: user, isLoading, isError } = useUserProfileQuery();

  if (isLoading) {
    return null;
  }

  const userIsAdmin = user?.isAdmin;

  if (!isAllowed && isError) {
    return <Navigate to={'/login'} />;
  }

  if (isAdmin && !userIsAdmin) {
    return <Navigate to={'/'} />;
  }

  return children ? <>{children}</> : <Outlet />;
};

export default PrivateRoute;
