import { FC, useEffect, useState } from 'react';
import Card from '@/components/ui/Card.tsx';
import { Input } from '@/components/ui-shadcn/input.tsx';
import { Button } from '@/components/ui-shadcn/button.tsx';
import Filter from '@/assets/icon/filter.svg?react';
import Search from '@/assets/icon/search.svg?react';
import { DataTable } from '@/components/admin/table/DataTable.tsx';
import { logColumns, messageEmptyLog } from '@/components/admin/table/columns/logColumns.tsx';
import FilterLogModal from '@/components/admin/form/filter/FilterLogModal.tsx';
import { FetchLogsParams, useLogsQuery } from '@/api/admin';
import { PaginationState } from '@tanstack/react-table';
import { perPage } from '@/api/users/api.ts';
import { generateExportLogsUrl } from '@/api/export';
import { useDebounceValue } from '@/utils/useDebounceValue';

const LogPage: FC = () => {
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [filters, setFilters] = useState<FetchLogsParams>({});
  const [search, setSearch] = useState('');
  const debouncedSearch = useDebounceValue(search, 500);
  // const deleteLog = (ids: number[]) => {
  //   if (ids.length === 0) return;
  //   console.log(ids);
  // };
  const columns = logColumns();
  const [pagination, setPagination] = useState<PaginationState>({
    pageIndex: 0,
    pageSize: perPage,
  });

  const { data, isLoading, isError } = useLogsQuery({
    page: pagination.pageIndex + 1,
    per_page: pagination.pageSize,
    ...filters,
    ip: debouncedSearch,
  });

  useEffect(() => {
    setPagination({
      pageIndex: 0,
      pageSize: perPage,
    });
  }, [debouncedSearch, filters]);

  const onSubmitFilter = (data: FetchLogsParams) => {
    setFilters(data);
  };

  return (
    <Card>
      <div className={'mb-6 flex gap-3'}>
        <Input
          value={search}
          onChange={(value) => {
            setSearch(value.target.value);
          }}
          placeholder={'Поиск по IP-адресу'}
          leftIcon={<Search />}
          inputSize={'m'}
        />
        <Button onClick={() => setIsModalOpen(true)} variant={'ghost'} size={'md'}>
          <Filter />
          Фильтр
        </Button>
      </div>
      {isError ? (
        'Error'
      ) : isLoading ? (
        'Loading'
      ) : (
        <DataTable
          columns={columns}
          data={data!.data.logs}
          pagination={pagination}
          setPagination={setPagination}
          totalRows={data?.data.countLogs}
          emptyTable={messageEmptyLog}
          exportCsvUrl={generateExportLogsUrl({
            mode: 'csv',
            order_by: 'id',
            is_asc: false,
          })}
          exportXlsxUrl={generateExportLogsUrl({
            mode: 'xlsx',
            order_by: 'id',
            is_asc: false,
          })}
        />
      )}
      <FilterLogModal onSubmitData={onSubmitFilter} open={isModalOpen} onClose={() => setIsModalOpen(false)} />
    </Card>
  );
};

export default LogPage;
