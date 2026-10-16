import Modal from '@/components/ui/Modal.tsx';
import { FunctionComponent, useCallback, useEffect } from 'react';
import { SystemCreate } from './ui/SystemCreate.tsx';
import { systemSchema, SystemSchemaType } from '@/components/modal-components/create-system/schemas/system.schema.ts';
import { useForm, FormProvider } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { Client } from '@/api/clients';

interface CreateSystemModalProps {
  isOpen: boolean;
  onClose: () => void;
  defaultValues?: Client | null;
  onSubmit: (formData: SystemSchemaType) => void;
}

export const CreateSystemModal: FunctionComponent<CreateSystemModalProps> = ({
  isOpen,
  onClose,
  defaultValues,
  onSubmit,
}) => {
  console.log('def', defaultValues);

  const methods = useForm<SystemSchemaType>({
    resolver: zodResolver(systemSchema),
    mode: 'onChange',
    defaultValues: {
      file: defaultValues?.logo ?? undefined,
      systemName: defaultValues?.name,
      rememberUser: !defaultValues?.dont_remember,
      hideSystem: defaultValues?.is_hidden ?? false,
      accountLevelESIA: defaultValues?.esia_level ?? 'NONE',
      systemDescription: defaultValues?.description ?? "",
    },
  });
  const { isValid } = methods.formState;

  useEffect(() => {
    methods.trigger()
  }, []);

  const closeModal = useCallback(() => {
    methods.reset();
    onClose();
  }, [methods, onClose]);

  return (
    <FormProvider {...methods}>
      <Modal
        open={isOpen}
        onClose={closeModal}
        onSubmit={methods.handleSubmit(onSubmit)}
        header='Создание системы'
        actionBtnText='Создать'
        disabled={!isValid}
        main={<SystemCreate />}
      />
    </FormProvider>
  );
};
