import { FC, useCallback, useMemo, useState } from 'react';
import { CreateSystemModal } from '@/components/modal-components/create-system';
import { Button } from '@/components/ui-shadcn/button.tsx';
import Card from '@/components/ui/Card.tsx';
import { DataTable } from '@/components/admin/table/DataTable.tsx';
import { messageEmptyTable, getSystemsColumns, SystemTable } from '@/components/admin/table/columns/systemsColumns.tsx';
import Plus from '@/assets/icon/add.svg?react';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui-shadcn/tabs.tsx';
import { PaginationState } from '@tanstack/react-table';
import { Client, useClientsModerationQuery, useDeleteClientMutation } from '@/api/clients';
import { SystemSchemaType } from '@/components/modal-components/create-system/schemas/system.schema';
import { useAdminClientsQuery } from '@/api/clients/hooks';
import { NavLink } from 'react-router-dom';
import { adminSystemsRoutes } from '@/config/navigation.tsx';

const AvailableSystemsPage: FC = () => {
  const [open, setOpen] = useState(false);
  const [tab, setTab] = useState<'clients' | 'changes'>('clients');
  const { data: adminClients } = useAdminClientsQuery();
  const { data } = useClientsModerationQuery('clients');
  const [editSystem, setEditSystem] = useState<Client | null>(null);

  const systemsData: SystemTable[] = useMemo(() => {
    const result = tab === 'clients' ? (adminClients?.data.clients ?? []) : (data?.data.newClients ?? []);
    return (
      result?.map((item) => ({
        id: item.id.toString(),
        logo: item.logo || '',
        name: item.name,
        isESIA: item.need_esia,
        isHidden: item.is_hidden,
      })) || []
    );
  }, [tab, data, adminClients]);

  const handleTabChange = (value: 'clients' | 'changes') => {
    setTab(value);
  };

  const [pagination, setPagination] = useState<PaginationState>({
    pageIndex: 0,
    pageSize: 1000,
  });

  const { mutateAsync: deleteSystemMutation } = useDeleteClientMutation();

  const deleteSystem = useCallback(
    async (ids: string[]) => {
      for (const id of ids) {
        await deleteSystemMutation(Number(id));
      }
    },
    [deleteSystemMutation]
  );

  // const { mutate: editSystemMutation } = usePatchClientMutation();

  const columns = useMemo(() => getSystemsColumns(deleteSystem), [deleteSystem]);

  const handleSubmit = (formData: SystemSchemaType) => {
    console.log('new', formData);
  };

  const handleEditSubmit = (formData: SystemSchemaType) => {
    console.log('edit', formData);
  };

  return (
    <Card>
      <div className='mb-6 flex justify-between'>
        <Tabs defaultValue='clients' onValueChange={(value) => handleTabChange(value as 'clients' | 'changes')}>
          <TabsList>
            <div className='flex gap-2'>
              <TabsTrigger value='clients' className='tab'>
                Доступные системы
              </TabsTrigger>
              <TabsTrigger value='changes' className='tab'>
                Системы на модерации
              </TabsTrigger>
            </div>
          </TabsList>
        </Tabs>
        <Button size={'md'} onClick={() => setOpen(true)} asChild>
          <NavLink to={adminSystemsRoutes.createSystem}>
            <Plus /> Создать систему
          </NavLink>
        </Button>
      </div>
      <DataTable
        data={systemsData}
        columns={columns}
        emptyTable={messageEmptyTable}
        totalRows={data?.data.changesClients?.length || data?.data.newClients?.length}
        pagination={pagination}
        setPagination={setPagination}
      />
      {editSystem && (
        <CreateSystemModal
          defaultValues={editSystem}
          isOpen={!!editSystem}
          onClose={() => {
            setEditSystem(null);
          }}
          onSubmit={handleEditSubmit}
        />
      )}
      <CreateSystemModal
        isOpen={open}
        onClose={() => {
          setOpen(false);
        }}
        onSubmit={handleSubmit}
      />
    </Card>
  );
};

export default AvailableSystemsPage;
