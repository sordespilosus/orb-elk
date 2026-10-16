import { FC } from 'react';
import { Outlet } from 'react-router-dom';
import Card from '@/components/ui/Card.tsx';
import AdminNavList from '@/components/admin/AdminNavList.tsx';

const AdminLayout: FC = () => {
  return (
    <div className={'grid grid-flow-col grid-cols-[auto_minmax(0,1fr)] items-start gap-4'}>
      <Card className={'p-8'}>
        <h3 className={'m-1 text-accent-foreground-header font-medium text-xl leading-6 tracking-tight'}>
          Панель управления
        </h3>
        <div className={'mt-6 grid grid-flow-row gap-3.5'}>
          <AdminNavList />
        </div>
      </Card>
      <Outlet />
    </div>
  );
};

export default AdminLayout;
