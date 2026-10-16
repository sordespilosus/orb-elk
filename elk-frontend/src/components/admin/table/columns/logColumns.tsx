import { ColumnDef } from '@tanstack/react-table';
import { Checkbox } from '@/components/ui-shadcn/checkbox.tsx';
import { Log } from '@/api/admin';
import { formatDate } from 'date-fns';
import Quest from '@/assets/icon/quest.svg?react';
import { EmptyTableType } from '@/types/empty-table-message-type.ts';

export type LogTable = {
  date: string;
  time: string;
  ipAddress: string;
  request: string;
};

export const messageEmptyLog: EmptyTableType = {
  icon: Quest,
  title: 'По вашему запросу нет результатов',
  description: 'Можете попробовать поменять параметры фильтрации',
};
// interface LogColumnsProps {
//   deleteLog: (id: number[]) => void;
// }
export const logColumns = (): ColumnDef<Log>[] => [
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
    accessorKey: 'date_created',
    header: 'Дата',
    cell: (info) => formatDate(info.getValue() as string, 'dd.MM.yyyy'),
  },
  {
    header: 'Время',
    cell: (info) => formatDate(info.row.original.date_created as string, 'HH:mm'),
  },
  {
    id: 'time_created',
    accessorKey: 'ip',
    header: 'IP-адрес',
  },
  {
    accessorKey: 'method',
    header: 'Тип запроса',
  },
  {
    accessorKey: 'url',
    header: 'Запрос',
  },
  // {
  //   accessorKey: 'delete',
  //   header: () => (
  //     <Button variant={'outline'} size={'icon'}>
  //       <Trash />
  //     </Button>
  //   ),
  //   cell: (row) => (
  //     <Button
  //       onClick={() => deleteLog([row.row.original.id])}
  //       variant={'outline'}
  //       size={'icon'}
  //       className={'opacity-0 hover:opacity-100'}
  //     >
  //       <Trash />
  //     </Button>
  //   ),
  // },
];
