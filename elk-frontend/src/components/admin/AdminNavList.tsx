import { FC } from 'react';
import { getAdminNavLinks } from '@/config/navigation.tsx';
import AdminNavItem from '@/components/admin/AdminNavItem.tsx';

const AdminNavList: FC = () => {
  return (
    <>
      {getAdminNavLinks().map((link, index) => (
        <AdminNavItem patch={link.path} label={link.label} icon={link.icon} key={index} target={link.target} />
      ))}
    </>
  );
};

export default AdminNavList;
