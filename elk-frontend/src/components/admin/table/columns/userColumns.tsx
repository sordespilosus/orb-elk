import { ColumnDef } from '@tanstack/react-table';
import { Checkbox } from '@/components/ui-shadcn/checkbox.tsx';
import { Badge } from '@/components/ui-shadcn/badge.tsx';
import Ban from '@/assets/icon/ban.svg?react';
import Human from '@/assets/icon/human.svg?react';
import Edit from '@/assets/icon/edit.svg?react';
import { Button } from '@/components/ui-shadcn/button.tsx';
import { hasUnblockedUsers } from '@/utils/helpers/hasBlockedUsers.ts';
import { User } from '@/api/users';
import { NavLink } from 'react-router-dom';
import { adminUsersRoutes } from '@/config/navigation.tsx';

// Тип данных для пользователя
export type UserTable = {
  id: string;
  login: string;
  fullName: string;
  gender: string;
  age: number;
  location: string;
  role: string;
  isBlocked: boolean;
  isConfirmed: boolean;
};

interface UserColumnsProps {
  banUser: (listId: number[]) => void;
  unbanUser: (listId: number[]) => void;
}
// Определение колонок для таблицы
export const userColumns = ({ banUser, unbanUser }: UserColumnsProps): ColumnDef<User>[] => [
  {
    id: 'select',
    header: ({ table }) => (
      <Checkbox
        checked={table.getIsAllPageRowsSelected() || (table.getIsSomePageRowsSelected() && 'indeterminate')}
        onCheckedChange={(value) => table.toggleAllPageRowsSelected(!!value)}
        aria-label='Select all'
        size={'xs'}
      />
    ),
    cell: ({ row }) => (
      <Checkbox
        checked={row.getIsSelected()}
        onCheckedChange={(value) => row.toggleSelected(!!value)}
        aria-label='Select row'
        size={'xs'}
      />
    ),
    enableSorting: false,
    enableHiding: false,
  },
  {
    accessorKey: 'id',
    header: 'ID',
  },
  {
    accessorKey: 'email',
    header: 'Логин',
  },
  {
    accessorKey: 'fio',
    header: 'ФИО',
  },
  {
    accessorKey: 'gender',
    header: 'Пол',
    cell: (info) => {
      const gender = info.getValue();

      if (gender === 'male') return 'М';
      if (gender === 'female') return 'Ж';

      return '';
    },
  },
  {
    accessorKey: 'birthday',
    header: 'Возраст',
    cell: (info) => {
      const birthday = info.getValue();
      if (!birthday) return '';

      const birthDate = new Date(birthday as string); // Получаем дату рождения из данных
      if (Number.isNaN(birthDate.getTime())) return '';

      const today = new Date(); // Текущая дата
      const age = today.getFullYear() - birthDate.getFullYear(); // Вычисляем разницу в годах
      const hasHadBirthdayThisYear =
        today.getMonth() > birthDate.getMonth() ||
        (today.getMonth() === birthDate.getMonth() && today.getDate() >= birthDate.getDate()); // Проверяем, был ли день рождения в этом году
      return hasHadBirthdayThisYear ? age : age - 1; // Если день рождения ещё не прошёл в этом году, уменьшаем возраст на 1
    },
  },
  // {
  //   accessorKey: 'location',
  //   header: 'Населенный пункт',
  // },
  {
    accessorKey: 'blocked',
    header: 'Заблокирован',
    cell: (info) => (info.getValue() ? <Badge>Да</Badge> : <Badge variant={'error'}>Нет</Badge>),
  },
  {
    accessorKey: 'is_admin',
    header: 'Админ',
    cell: (info) => (info.getValue() ? <Badge>Да</Badge> : <Badge variant={'error'}>Нет</Badge>),
  },
  {
    accessorKey: 'is_deleted',
    header: 'Удалён',
    cell: (info) => (info.getValue() ? <Badge>Да</Badge> : <Badge variant={'error'}>Нет</Badge>),
  },
  {
    accessorKey: 'email_verified_at',
    header: 'Подтверждён',
    cell: (info) => (info.getValue() ? <Badge>Да</Badge> : <Badge variant={'error'}>Нет</Badge>),
  },
  {
    accessorKey: 'edit',
    header: '',
    cell: ({ row }) => (
      <Button variant={'outline'} size={'icon'} className={'opacity-80 hover:opacity-100'} asChild>
        <NavLink to={adminUsersRoutes.editUser(row.original.id)}>
          <Edit />
        </NavLink>
      </Button>
    ),
  },
  {
    accessorKey: 'block',
    header: ({ table }) =>
      hasUnblockedUsers(table.getFilteredSelectedRowModel().rows.map((row) => row.original.blocked)) ? (
        <Button
          onClick={() => banUser([...table.getSelectedRowModel().rows.map((row) => row.original.id)])}
          variant={'outline'}
          size={'icon'}
        >
          <Ban />
        </Button>
      ) : (
        <Button
          onClick={() => unbanUser([...table.getSelectedRowModel().rows.map((row) => row.original.id)])}
          variant={'outline'}
          size={'icon'}
        >
          <Human />
        </Button>
      ),
    cell: ({ row }) => (
      <Button
        onClick={() => (row.original.blocked ? unbanUser([row.original.id]) : banUser([row.original.id]))}
        variant={'outline'}
        size={'icon'}
        className={'opacity-0 hover:opacity-100'}
      >
        {row.original.blocked ? <Human /> : <Ban />}
      </Button>
    ),
  },
];
