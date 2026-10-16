import { ColumnDef } from '@tanstack/react-table';
import { Checkbox } from '@/components/ui-shadcn/checkbox.tsx';
import { Badge } from '@/components/ui-shadcn/badge.tsx';
import { Button } from '@/components/ui-shadcn/button.tsx';
import Trash from '@/assets/icon/trash.svg?react';
import Edit from '@/assets/icon/edit.svg?react';
import emptyImg from '@/assets/empty.png';
import { EmptyTableType } from '@/types/empty-table-message-type.ts';
import { NavLink } from 'react-router-dom';
import { adminSystemsRoutes } from '@/config/navigation.tsx';

// Тип данных для пользователя
export type SystemTable = {
  id: string;
  logo: string;
  name: string;
  isESIA: boolean;
  isHidden: boolean;
};

export const messageEmptyTable: EmptyTableType = {
  title: 'Системы отсутствуют',
  description: 'Необходимо добавить системы для доступа к ним через портал Цифровой Системы Оренбуржья',
};

// Определение колонок для таблицы
export const getSystemsColumns = (deleteSystem: (ids: string[]) => void): ColumnDef<SystemTable>[] => [
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
    accessorKey: 'logo',
    header: 'Логотип',
    cell: (info) => (
      <img
        src={(info.getValue() as string) || emptyImg}
        height={64}
        width={64}
        className={'object-cover aspect-square rounded-lg'}
        loading='lazy'
        alt='logo'
      />
    ),
  },
  {
    accessorKey: 'name',
    header: 'Название системы',
  },
  {
    accessorKey: 'isESIA',
    header: 'Необходима ЕСИА',
    cell: (info) => (info.getValue() ? <Badge>Да</Badge> : <Badge variant={'error'}>Нет</Badge>),
  },
  {
    accessorKey: 'isHidden',
    header: 'Система скрыта',
    cell: (info) => (info.getValue() ? <Badge>Да</Badge> : <Badge variant={'error'}>Нет</Badge>),
  },
  {
    accessorKey: 'delete',
    header: ({ table }) => (
      <Button
        onClick={() => {
          deleteSystem([...table.getSelectedRowModel().rows.map((row) => row.original.id)]);
          table.toggleAllPageRowsSelected(false);
        }}
        variant={'outline'}
        size={'icon'}
      >
        <Trash />
      </Button>
    ),
    cell: ({ row }) => (
      <Button variant={'outline'} size={'iconBox'} className={'opacity-80 hover:opacity-100'} asChild>
        <NavLink to={adminSystemsRoutes.editSystem(row.original.id)}>
          <Edit />
        </NavLink>
      </Button>
    ),
  },
];
