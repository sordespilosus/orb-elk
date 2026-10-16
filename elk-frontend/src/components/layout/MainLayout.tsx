import { FunctionComponent } from 'react';
import { Outlet, Navigate } from 'react-router-dom';
import Header from '../Header';
import AppBar from '../AppBar';
import { useUserProfileQuery } from '@/api/user';
import { Loader } from '../ui/Loader';

const MainLayout: FunctionComponent = () => {
  const { data: user, isLoading } = useUserProfileQuery();

  if (isLoading) {
    return <Loader />;
  }

  if (!user) {
    return <Navigate to={'/login'} />;
  }

  return (
    <div>
      <div className='mx-4'>
        <Header
          user={{
            role: user?.isAdmin ? 'admin' : 'user',
            userName: user?.surname + ' ' + user?.name || '',
            avatarUrl: user?.photo || '',
          }}
        />
      </div>
      <main className='mx-4 mobile:my-8 my-4'>
        <div className='max-w-container mx-auto max-mobile:mb-app-bar'>
          <Outlet />
        </div>
      </main>
      <AppBar
        user={{
          role: 'Admin',
          userName: user?.name || '',
          avatarUrl: user?.photo || '',
        }}
      />
    </div>
  );
};

export default MainLayout;
