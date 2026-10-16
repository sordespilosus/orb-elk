import { useSystemDescriptionQuery } from '@/api/admin';
import { useGetAgreementQuery, useSetAgreementMutation, useSetDescriptionMutation } from '@/api/admin/hooks';
import RichTextEditor from '@/components/admin/rich-text-editor/RichTextEditor';
import Card from '@/components/ui/Card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@radix-ui/react-tabs';
import { useContext, useEffect } from 'react';
import { ToastContext } from '@/components/toast/ToastContext';

const SystemDescriptionPage = () => {
  const { data: systemDescription, isLoading: isSystemDescriptionLoading } = useSystemDescriptionQuery();
  const { data: agreement, isLoading: isAgreementLoading } = useGetAgreementQuery();
  const {
    mutate: setSystemDescription,
    error: setSystemDescriptionError,
    isSuccess: isSetSystemDescriptionSuccess,
  } = useSetDescriptionMutation();
  const {
    mutate: setAgreement,
    error: setAgreementError,
    isSuccess: isSetAgreementSuccess,
  } = useSetAgreementMutation();
  const { toast } = useContext(ToastContext);

  useEffect(() => {
    if (setSystemDescriptionError)
      toast({ title: 'Ошибка', message: setSystemDescriptionError.message, variant: 'error' });
  }, [setSystemDescriptionError]);

  useEffect(() => {
    if (setAgreementError) toast({ title: 'Ошибка', message: setAgreementError.message, variant: 'error' });
  }, [setAgreementError]);

  useEffect(() => {
    if (isSetSystemDescriptionSuccess)
      toast({ title: 'Успешно', message: 'Описание системы сохранено', variant: 'success' });
  }, [isSetSystemDescriptionSuccess]);

  useEffect(() => {
    if (isSetAgreementSuccess)
      toast({ title: 'Успешно', message: 'Пользовательское соглашение сохранено', variant: 'success' });
  }, [isSetAgreementSuccess]);

  return (
    <Card className='p-8'>
      <Tabs defaultValue='account' className='flex flex-col gap-4'>
        <div className='w-full rounded-xl outline-thin p-1'>
          <TabsList>
            <div className='flex gap-2'>
              <TabsTrigger value='account' className='tab'>
                Описание системы
              </TabsTrigger>
              <TabsTrigger value='agreement' className='tab'>
                Пользовательское соглашение
              </TabsTrigger>
            </div>
          </TabsList>
        </div>

        <TabsContent value='account'>
          {isSystemDescriptionLoading ? (
            <div>Loading...</div>
          ) : (
            <RichTextEditor
              editorKey='systemDescription'
              initialContent={systemDescription?.data}
              onSave={(html) => setSystemDescription({ description: html })}
            />
          )}
        </TabsContent>

        <TabsContent value='agreement'>
          {isAgreementLoading ? (
            <div>Loading...</div>
          ) : (
            <RichTextEditor
              editorKey='userAgreement'
              initialContent={agreement?.data}
              onSave={(html) => setAgreement({ text_agreement: html })}
            />
          )}
        </TabsContent>
      </Tabs>
    </Card>
  );
};

export default SystemDescriptionPage;
