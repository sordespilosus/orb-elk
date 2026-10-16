import {
  ColumnDef,
  flexRender,
  getCoreRowModel,
  getPaginationRowModel,
  PaginationState,
  useReactTable,
} from '@tanstack/react-table';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui-shadcn/table';
import PaginationTable from '@/components/admin/PaginationTable.tsx';
import { Button } from '@/components/ui-shadcn/button.tsx';
import EmptyTable from '@/components/admin/table/EmptyTable.tsx';
import React, { useState } from 'react';
import { EmptyTableType } from '@/types/empty-table-message-type.ts';
import { cn } from '@/lib/utils';
import ExportIcon from '@/assets/export.svg?react';

interface DataTableProps<TData, TValue> {
  columns: ColumnDef<TData, TValue>[];
  data: TData[] | undefined;
  emptyTable?: EmptyTableType;
  pagination?: PaginationState;
  setPagination?: React.Dispatch<React.SetStateAction<PaginationState>>;
  totalRows?: number;
  exportCsvUrl?: string;
  exportXlsxUrl?: string;
  isLoading?: boolean;
  isError?: boolean;
}

export function DataTable<TData, TValue>({
  columns,
  data = [],
  emptyTable,
  pagination = { pageIndex: 0, pageSize: 10 },
  setPagination,
  totalRows,
  exportCsvUrl,
  exportXlsxUrl,
  isLoading,
  isError,
}: DataTableProps<TData, TValue>): JSX.Element {
  const pageSize = pagination?.pageSize;
  const maxPage = totalRows ? Math.ceil(totalRows / pageSize) : 1;
  const table = useReactTable({
    data,
    columns,
    getCoreRowModel: getCoreRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    pageCount: maxPage,
    manualPagination: !!pagination,
    onPaginationChange: setPagination,
    state: {
      pagination,
    },
  });

  return (
    <div className={'relative'}>
      <div
        className={
          'before:absolute flex before:top-0 min-h-[640px] before:right-0 before:h-[calc(100%-24px)] before:w-12 before:bg-gradient-to-r before:from-transparent before:to-white before:z-40 before:pointer-events-none before:hidden'
        }
      >
        {isLoading ? (
          <EmptyTable title={'Загрузка...'} description={''} />
        ) : !isError && table.getRowModel().rows?.length ? (
          <Table className={'text-accent-foreground-header font-medium text-xs leading-[14px] tracking-tight mb-4'}>
            <TableHeader>
              {table.getHeaderGroups().map((headerGroup) => (
                <TableRow key={headerGroup.id}>
                  {headerGroup.headers.map((header) => {
                    return (
                      <TableHead className={'text-current px-[5px]'} key={header.id}>
                        {header.isPlaceholder ? null : flexRender(header.column.columnDef.header, header.getContext())}
                      </TableHead>
                    );
                  })}
                </TableRow>
              ))}
            </TableHeader>
            <TableBody className={'font-normal'}>
              {table.getRowModel().rows.map((row) => (
                <TableRow key={row.id} data-state={row.getIsSelected() && 'selected'}>
                  {row.getVisibleCells().map((cell) => (
                    <TableCell key={cell.id}>{flexRender(cell.column.columnDef.cell, cell.getContext())}</TableCell>
                  ))}
                </TableRow>
              ))}
            </TableBody>
          </Table>
        ) : (
          <EmptyTable
            icon={emptyTable?.icon}
            title={emptyTable?.title || 'Пусто'}
            description={emptyTable?.description || ''}
          />
        )}
      </div>
      <div className={'relative my-4'}>
        <PaginationTable table={table} pages={maxPage} />
        {(exportCsvUrl || exportXlsxUrl) && <ExportButton exportCsvUrl={exportCsvUrl} exportXlsxUrl={exportXlsxUrl} />}
      </div>
    </div>
  );
}

type ExportButtonProps = {
  exportCsvUrl?: string;
  exportXlsxUrl?: string;
};

export const ExportButton = ({ exportCsvUrl, exportXlsxUrl }: ExportButtonProps) => {
  const [isOpen, setIsOpen] = useState(false);

  return (
    <div className='absolute right-0 bottom-1/2 translate-y-1/2'>
      <div
        className={cn(
          'gap-2 flex-col absolute right-0 top-0 min-w-full -translate-y-[calc(100%+8px)] rounded-md border bg-white p-2 shadow-md',
          isOpen ? 'flex' : 'hidden'
        )}
      >
        {exportCsvUrl && (
          <Button className='mb-2' asChild variant={'secondary'} size={'md'}>
            <a target='_blank' href={exportCsvUrl}>
              CSV
            </a>
          </Button>
        )}
        {exportXlsxUrl && (
          <Button className='mb-2' asChild variant={'secondary'} size={'md'}>
            <a target='_blank' href={exportXlsxUrl}>
              XLSX
            </a>
          </Button>
        )}
      </div>
      <Button variant={'secondary'} size={'md'} onClick={() => setIsOpen(!isOpen)}>
        <ExportIcon />
        Экспорт
      </Button>
    </div>
  );
};
