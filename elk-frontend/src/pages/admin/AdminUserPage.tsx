import { FC, useMemo, useState, memo, FormEvent } from 'react';
import Card from '@/components/ui/Card.tsx';
import { DataTable } from '@/components/admin/table/DataTable.tsx';
import { userColumns } from '@/components/admin/table/columns/userColumns.tsx';
import { Input } from '@/components/ui-shadcn/input.tsx';
import { Button } from '@/components/ui-shadcn/button.tsx';
import Filter from '@/assets/icon/filter.svg?react';
import Search from '@/assets/icon/search.svg?react';
import FilterUserModal from '@/components/admin/form/filter/FilterUserModal.tsx';
import { PaginationState } from '@tanstack/react-table';
import { GetUsersParams, useBlockUserMutation, useUsers } from '@/api/users';
import { perPage } from '@/api/users/api.ts';
import { generateExportUsersUrl } from '@/api/export';
import type { ExportUsersQueryParams } from '@/api/export/types.ts';

const AdminUserPage: FC = () => {
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [search, setSearch] = useState('');
  const [pagination, setPagination] = useState<PaginationState>({
    pageIndex: 0,
    pageSize: perPage,
  });
  const [filters, setFilters] = useState<GetUsersParams>({});
  const blockUserMutation = useBlockUserMutation();
  const { isLoading, isError, data } = useUsers({
    page: pagination.pageIndex + 1,
    per_page: pagination.pageSize,
    ...filters,
  });
  const banUser = (ids: number[]) => {
    if (ids.length === 0) return;
    // if (blockUserMutation.isPending) { TODO: Добавить обработку загрузки на кнопку чтобы нельзя было два раза отправить один и тот же запрос
    //   return;
    // }
    const banUserParams = {
      user_id: ids[0],
      value: true,
    };
    blockUserMutation.mutate(banUserParams);
  };

  const unbanUser = async (ids: number[]) => {
    if (ids.length === 0) return;
    // if (blockUserMutation.isPending) {
    //   alert('Pending');
    //   return;
    // }
    const banUserParams = {
      user_id: ids[0],
      value: false,
    };
    blockUserMutation.mutate(banUserParams);
  };

  const columns = useMemo(() => userColumns({ banUser, unbanUser }), [banUser, unbanUser]);

  const resetPagination = () => {
    setPagination((prev) => ({ ...prev, pageIndex: 0 }));
  };

  const onSubmitSearch = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    setFilters({ fio: search.trim() || undefined });
    resetPagination();
  };

  const onSubmitFilter = (filter: GetUsersParams) => {
    setSearch('');
    setFilters(filter);
    resetPagination();
  };

  const exportUsersParams: ExportUsersQueryParams = {
    order_by: filters.order_by || 'id',
    is_asc: Boolean(filters.is_asc ?? 1),
    fio: filters.fio,
    first_name: filters.first_name,
    second_name: filters.second_name,
    patroname: filters.patroname,
    city: filters.city ? Number(filters.city) : undefined,
    gender: filters.gender,
    esia: filters.esia,
    age_period_from: filters.age_period_from,
    age_period_to: filters.age_period_to,
    only_verified: filters.only_verified,
    id: filters.id,
    is_admin: filters.is_admin,
    is_blocked: filters.is_blocked,
    is_accepted: filters.is_accepted,
  };

  return (
    <Card className={'p-8 max-w-full'}>
      <form className={'mb-6 flex gap-3'} onSubmit={onSubmitSearch}>
        <Input
          leftIcon={<Search />}
          inputSize={'m'}
          value={search}
          onChange={(event) => setSearch(event.target.value)}
          placeholder={'Поиск по ФИО'}
        />
        <Button type={'button'} onClick={() => setIsModalOpen(true)} variant={'ghost'} size={'md'}>
          <Filter />
          Фильтр
        </Button>
      </form>
      <DataTable
        isLoading={isLoading}
        isError={isError}
        pagination={pagination}
        setPagination={setPagination}
        data={data?.data.users}
        totalRows={data?.data.countUsers}
        columns={columns}
        exportCsvUrl={generateExportUsersUrl({
          ...exportUsersParams,
          mode: 'csv',
        })}
        exportXlsxUrl={generateExportUsersUrl({
          ...exportUsersParams,
          mode: 'xlsx',
        })}
      />
      <FilterUserModal onSubmitData={onSubmitFilter} open={isModalOpen} onClose={() => setIsModalOpen(false)} />
    </Card>
  );
};

export default memo(AdminUserPage);
