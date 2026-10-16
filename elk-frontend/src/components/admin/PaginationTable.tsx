import { FC } from 'react';
import { Pagination, PaginationContent, PaginationItem } from '@/components/ui-shadcn/pagination.tsx';
// import { Input } from '@/components/ui-shadcn/input.tsx';
import LeftIcon from '@/assets/icon/left.svg?react';
import RightIcon from '@/assets/icon/right.svg?react';
import { Table } from '@tanstack/react-table';
import { Button } from '@/components/ui-shadcn/button.tsx';
import { usePageLinks } from '@/hooks/usePageLinks.ts';

interface PaginationTableProps<TData> {
  pages: number;
  table: Table<TData>;
}

const PaginationTable: FC<PaginationTableProps<any>> = ({ table, pages }) => {
  const { pageIndex } = table.getState().pagination;
  const currentPage = pageIndex + 1;

  const pageLinks = usePageLinks(currentPage, pages);
  return (
    <Pagination>
      <PaginationContent className='gap-2.5'>
        <PaginationItem>
          <Button
            size={'smIcon'}
            variant={'outline'}
            onClick={table.previousPage}
            disabled={!table.getCanPreviousPage()}
          >
            <LeftIcon />
          </Button>
        </PaginationItem>
        {pageLinks.map((page) => (
          <PaginationItem key={page}>
            <Button
              size={'smIcon'}
              variant={'outline'}
              onClick={() => table.setPageIndex(page - 1)}
              isActive={page === currentPage}
            >
              {page}
            </Button>
          </PaginationItem>
        ))}
        {currentPage < pages - 1 && pages !== pageLinks.length && (
          <PaginationItem>
            <Button size={'smIcon'} variant={'outline'} onClick={() => table.setPageIndex(pages - 1)}>
              {pages}
            </Button>
          </PaginationItem>
        )}
        <PaginationItem>
          <Button size={'smIcon'} variant={'outline'} onClick={table.nextPage} disabled={!table.getCanNextPage()}>
            <RightIcon />
          </Button>
        </PaginationItem>
      </PaginationContent>
    </Pagination>
  );
};

export default PaginationTable;
