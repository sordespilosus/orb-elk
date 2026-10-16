import { FC, useContext, useEffect, useMemo } from 'react';
import Card from '@/components/ui/Card.tsx';
import { Button } from '@/components/ui-shadcn/button.tsx';
import Cancel from '@/assets/icon/cancel.svg?react';
import { Check } from 'lucide-react';
import { FormInput } from '@/components/ui/form/FormInput.tsx';
import { Form } from '@/components/ui-shadcn/form.tsx';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { SystemParameters, SystemParametersSchema } from '@/validation/system-parameters.ts';
import FormTextArea from '@/components/ui/form/FormTextArea.tsx';
import { FileUploader } from '@/components/modal-components/create-system/ui/FileUploader.tsx';
import { SYSTEM_MODAL_ITEMS } from '@/components/modal-components/create-system/constants';
import { UiAccordion } from '@/components/ui/UiAccordion';
import { UiAccordionItem } from '@/components/ui/UiAccordion/UiAccordionItem.tsx';
import { FormSwitch } from '@/components/ui/form/FormSwitch.tsx';
import { FormRichTextEditor } from '@/components/ui/form/FormRichTextEditor.tsx';
import { useCreateClientMutation, usePatchClientMutation } from '@/api/clients';
import { transformSystemParametersToClient } from '@/utils/helpers/systemTransformers.ts';
import { FormSelect } from '@/components/ui/form/FormSelect.tsx';
import { useSwitchBinding } from '@/hooks/useSwitchBinding.ts';
import { optionsAcceptedSystem, optionsAccountLevelESIA, switchBindings } from '@/lib/constants/system-const.ts';
import { useClientQuery } from '@/api/clients/hooks.ts';
import { useNavigate, useParams } from 'react-router-dom';
import { adminSystemsRoutes } from '@/config/navigation.tsx';
import { ToastContext } from '@/components/toast/ToastContext.ts';
import { TextTooltip } from '@/components/ui-shadcn/tooltip';
import { TooltipProvider } from '@radix-ui/react-tooltip';

const formatAgreementDateForInput = (date?: string | null) => {
  if (!date) return '';
  const [day, month, year] = date.split('.');
  return day && month && year ? `${year}-${month}-${day}` : date;
};

const SystemPage: FC = () => {
  const { systemId } = useParams();
  const navigate = useNavigate();
  const { toast } = useContext(ToastContext);

  const {
    mutate,
    isSuccess: isCreateSuccess,
    isPending: isCreatePending,
    error: createError,
  } = useCreateClientMutation();
  const {
    mutate: edit,
    isSuccess: isEditSuccess,
    isPending: isEditPending,
    error: editError,
  } = usePatchClientMutation();
  const { data, isSuccess } = useClientQuery(Number(systemId));

  const oldAgreement = useMemo(() => data?.data.client?.agreement, [data]);
  const oldAgreementUpdatedAt = useMemo(
    () => formatAgreementDateForInput(data?.data.client?.agreement_updated_at),
    [data]
  );

  const form = useForm<SystemParameters>({
    resolver: zodResolver(SystemParametersSchema),
  });
  const onSubmit = async (data: SystemParameters) => {
    console.log('submit');
    console.log(data);
    const newClientDataTransformed = transformSystemParametersToClient(data);
    const clientDataWithRedirect = {
      ...newClientDataTransformed,
      redirect: newClientDataTransformed.redirect ?? [],
    };
    if (systemId) {
      edit({ id: Number(systemId), ...clientDataWithRedirect, oldAgreement, oldAgreementUpdatedAt });
    } else {
      mutate(newClientDataTransformed);
    }
    console.log(newClientDataTransformed);
  };

  const onReset = () => {
    form.reset();
  };

  switchBindings.forEach((switchBinding) =>
    useSwitchBinding<SystemParameters>({
      switchBindings: switchBinding,
      watch: form.watch,
      setValue: form.setValue,
    })
  );

  useEffect(() => {
    if (isSuccess) {
      const client = data?.data.client;
      form.reset({
        file: client?.logo ?? undefined,
        needOrganization: client?.need_organization ?? false,
        systemName: client?.name ?? '',
        systemAddress: client?.url_site ?? '',
        acceptedSystem: client?.status ?? undefined,
        // loginForm: client?.login_form ?? false,
        systemRedirectUrl: client?.redirect?.[0] ?? '',
        systemEnterUrl: client?.url_login ?? '',
        systemNotificationUrl: client?.url_notifications ?? '',
        rememberUser: client?.dont_remember,
        permissionsWindow: client?.without_oauth_notify,
        hideSystem: client?.is_hidden ?? false,
        accountLevelESIA: client?.esia_level ?? 'NONE',
        secret: client?.secret ?? '',
        systemDescription: client?.description ?? '',
        requiredESIA: client?.need_esia ?? false,
        userAgreementSystem: client?.agreement ?? '',
        agreementUpdatedAt: formatAgreementDateForInput(client?.agreement_updated_at),

        // Разрешения для системы
        accessToAuthMethod: client?.scope.auth_method ?? false,
        accessToAuthMethodBinding: client?.scope_required.auth_method ?? false,
        accessToEmailBinding: client?.scope_required.email ?? false,
        accessToPhone: client?.scope.phone ?? false,
        accessToPhoneBinding: client?.scope_required.phone ?? false,
        accessToEmail: client?.scope.email ?? false,
        accessToRsaagId: client?.scope.rsaag_id ?? false,
        accessToRsaagIdBinding: client?.scope_required.rsaag_id ?? false,
        accessToEsiaAddress: client?.scope.esia_address ?? false,
        accessToEsiaAddressBinding: client?.scope_required.esia_address ?? false,
        accessToEsiaData: client?.scope.esia_data ?? false,
        accessToEsiaDataBinding: client?.scope_required.esia_data ?? false,
        accessToPersonalInfo: client?.scope.personal_data ?? false,
        accessToPersonalInfoBinding: client?.scope_required.personal_data ?? false,
        accessUserIdGos: client?.scope?.esia_user_id ?? false,
        accessUserIdGosBinding: client?.scope_required?.esia_user_id ?? false,
        accessByUserOrganizations: client?.scope?.organizations_user ?? false,
        accessByUserOrganizationsBinding: client?.scope_required?.organizations_user ?? false,
        accessToPassportData: client?.scope?.passport ?? false,
        accessToPassportDataBinding: client?.scope_required?.passport ?? false,
        accessToNumberSNILS: client?.scope?.snils ?? false,
        accessToNumberSNILSBinding: client?.scope_required?.snils ?? false,

        // ЕСИАDATA
        ext_org_info: client?.client_hits_esia_scope?.ext_org_info ?? false,
        default_data: client?.client_hits_esia_scope?.default_data ?? false,
        temporary_residence_doc: client?.client_hits_esia_scope?.temporary_residence_doc ?? false,
        drivers_licence_doc: client?.client_hits_esia_scope?.drivers_licence_doc ?? false,
        residence_doc: client?.client_hits_esia_scope?.residence_doc ?? false,
        birth_cert_doc: client?.client_hits_esia_scope?.birth_cert_doc ?? false,
        vehicles: client?.client_hits_esia_scope?.vehicles ?? false,
        kids: client?.client_hits_esia_scope?.kids ?? false,

        // Методы входа
        loginWithUsernameAndPassword: client?.auth_methods?.includes('login') ?? false,
        loginViaGosUslugi: client?.auth_methods?.includes('esia') ?? false,
        reasons: client?.reasons ?? '',
      });
    }
  }, [isSuccess, data]);

  useEffect(() => {
    if (isCreateSuccess || isEditSuccess) {
      toast({ message: 'Система успешно сохранена', title: 'Сохранено', variant: 'success' });
      navigate(adminSystemsRoutes.systems);
      return;
    }
    if (createError || editError) {
      toast({ message: 'Произошла ошибка при сохранении системы', title: 'Ошибка', variant: 'error' });
    }
  }, [isCreateSuccess, isEditSuccess, createError, editError]);

  return (
    <Card>
      <Form {...form}>
        <form id='systemForm' onSubmit={form.handleSubmit(onSubmit)} className=''>
          <div className={'mb-6 flex justify-between'}>
            <h3 className={'font-medium text-xl leading-6 tracking-tight text-accent-foreground-header'}>
              Управление системой
            </h3>
            <div className={'flex gap-3.5'}>
              <Button
                disabled={isEditPending || isCreatePending}
                type={'button'}
                onClick={onReset}
                variant='secondary'
                size='md'
              >
                <Cancel /> Сброс
              </Button>
              <Button disabled={isEditPending || isCreatePending} size='md'>
                <Check /> Сохранить изменения
              </Button>
            </div>
          </div>
          <div>
            <h4 className={'font-medium leading-5 tracking-tight text-accent-foreground-header mb-7'}>
              Основные параметры
            </h4>
            <div className={'grid gap-6'}>
              <div>
                <div className={'flex gap-4 items-end mb-3'}>
                  <div className='col-span-2 grid grid-cols-2 gap-y-3 gap-x-2.5 flex-grow'>
                    <FormInput
                      control={form.control}
                      name='systemName'
                      autoComplete='off'
                      label='Название системы'
                      placeholder='Введите название системы'
                      required
                      inputProps={{ inputSize: 'm' }}
                    />
                    <FormInput
                      control={form.control}
                      name='systemAddress'
                      autoComplete='off'
                      label='Адрес системы'
                      placeholder='Введите адрес'
                      inputProps={{ inputSize: 'm' }}
                      required
                    />
                    <FormInput
                      control={form.control}
                      name='systemRedirectUrl'
                      autoComplete='off'
                      label='Адрес редиректа системы'
                      placeholder='Введите адрес редиректа'
                      inputProps={{ inputSize: 'm' }}
                      required
                    />
                    <FormInput
                      control={form.control}
                      name='systemEnterUrl'
                      autoComplete='off'
                      label='Адрес входа в систему'
                      placeholder='Введите адрес входа'
                      inputProps={{ inputSize: 'm' }}
                    />
                    <FormInput
                      formItemClassName='col-span-2'
                      control={form.control}
                      name='systemNotificationUrl'
                      autoComplete='off'
                      label='Адрес получения уведомлений'
                      placeholder='Введите адрес уведомлений'
                      inputProps={{ inputSize: 'm' }}
                    />
                  </div>
                  <FileUploader title={'Логотип системы'} name={'file'} />
                </div>
                <FormInput
                  formItemClassName='mb-3'
                  control={form.control}
                  name='secret'
                  autoComplete='off'
                  label='Секретный ключ'
                  placeholder='Секретный ключ'
                  inputProps={{ inputSize: 'm', readOnly: true }}
                />
                <FormTextArea
                  control={form.control}
                  name='systemDescription'
                  label='Краткое описание системы'
                  placeholder='Введите описание'
                  required
                />
              </div>
              <div>
                <UiAccordion items={SYSTEM_MODAL_ITEMS}>
                  <UiAccordionItem
                    key={'ЕСИА'}
                    className={''}
                    classElement={''}
                    label={'ЕСИА'}
                    element={
                      <div className={'relative'}>
                        <FormSwitch control={form.control} name={'requiredESIA'} label={'Необходима ЕСИА'} />
                        <FormSelect
                          options={optionsAccountLevelESIA}
                          control={form.control}
                          name={'accountLevelESIA'}
                          placeholder={'Выберите уровень'}
                          label={'Уровень учётных записей ЕСИА'}
                        />
                      </div>
                    }
                  />
                  <TooltipProvider>
                    <UiAccordionItem
                      key={'Разрешения для системы'}
                      className={''}
                      classElement={''}
                      label={'Разрешения для системы'}
                      element={
                        <div className={'relative'}>
                          <TextTooltip text='rsaag_id'>
                            <FormSwitch
                              control={form.control}
                              name={'accessToRsaagId'}
                              label={'Доступ к открытой сессии пользователя (ID пользователя)'}
                            >
                              <FormSwitch
                                control={form.control}
                                name={'accessToRsaagIdBinding'}
                                label={'Обязательно?'}
                              />
                            </FormSwitch>
                          </TextTooltip>
                          <TextTooltip text='personal_data'>
                            <FormSwitch
                              control={form.control}
                              name={'accessToPersonalInfo'}
                              label={'Доступ к персональной информации (ФИО, дате рождения, городу проживания, логину)'}
                            >
                              <FormSwitch
                                control={form.control}
                                name={'accessToPersonalInfoBinding'}
                                label={'Обязательно?'}
                              />
                            </FormSwitch>
                          </TextTooltip>
                          <TextTooltip text='esia_data'>
                            <FormSwitch
                              control={form.control}
                              name={'accessToEsiaData'}
                              label={'Доступ к персональной информации из Госуслуг'}
                            >
                              <FormSwitch
                                control={form.control}
                                name={'accessToEsiaDataBinding'}
                                label={'Обязательно?'}
                              />
                            </FormSwitch>
                          </TextTooltip>
                          <TextTooltip text='email'>
                            <FormSwitch control={form.control} name={'accessToEmail'} label={'Доступ к E-mail'}>
                              <FormSwitch control={form.control} name={'accessToEmailBinding'} label={'Обязательно?'} />
                            </FormSwitch>
                          </TextTooltip>
                          <TextTooltip text='phone'>
                            <FormSwitch
                              control={form.control}
                              name={'accessToPhone'}
                              label={'Доступ к номеру телефона'}
                            >
                              <FormSwitch control={form.control} name={'accessToPhoneBinding'} label={'Обязательно?'} />
                            </FormSwitch>
                          </TextTooltip>
                          <TextTooltip text='esia_user_id'>
                            <FormSwitch
                              control={form.control}
                              name={'accessUserIdGos'}
                              label={'Доступ к ID пользователя в Госуслугах'}
                            >
                              <FormSwitch
                                control={form.control}
                                name={'accessUserIdGosBinding'}
                                label={'Обязательно?'}
                              />
                            </FormSwitch>
                          </TextTooltip>
                          <TextTooltip text='passport'>
                            <FormSwitch
                              control={form.control}
                              name={'accessToPassportData'}
                              label={'Доступ к паспортным данным'}
                            >
                              <FormSwitch
                                control={form.control}
                                name={'accessToPassportDataBinding'}
                                label={'Обязательно?'}
                              />
                            </FormSwitch>
                          </TextTooltip>
                          <TextTooltip text='snils'>
                            <FormSwitch
                              control={form.control}
                              name={'accessToNumberSNILS'}
                              label={'Доступ к номеру СНИЛС'}
                            >
                              <FormSwitch
                                control={form.control}
                                name={'accessToNumberSNILSBinding'}
                                label={'Обязательно?'}
                              />
                            </FormSwitch>
                          </TextTooltip>
                          <TextTooltip text='organizations_user'>
                            <FormSwitch
                              control={form.control}
                              name={'accessByUserOrganizations'}
                              label={'Доступ организациям пользователя'}
                            >
                              <FormSwitch
                                control={form.control}
                                name={'accessByUserOrganizationsBinding'}
                                label={'Обязательно?'}
                              />
                            </FormSwitch>
                          </TextTooltip>
                          <TextTooltip text='auth_method'>
                            <FormSwitch
                              control={form.control}
                              name={'accessToAuthMethod'}
                              label={'Доступ к методу авторизации'}
                            >
                              <FormSwitch
                                control={form.control}
                                name={'accessToAuthMethodBinding'}
                                label={'Обязательно?'}
                              />
                            </FormSwitch>
                          </TextTooltip>
                          <TextTooltip text='esia_address'>
                            <FormSwitch
                              control={form.control}
                              name={'accessToEsiaAddress'}
                              label={'Доступ к адресу пользователя'}
                            >
                              <FormSwitch
                                control={form.control}
                                name={'accessToEsiaAddressBinding'}
                                label={'Обязательно?'}
                              />
                            </FormSwitch>
                          </TextTooltip>
                        </div>
                      }
                    />
                  </TooltipProvider>
                  <UiAccordionItem
                    key={'ЕСИАDATA'}
                    className={''}
                    classElement={''}
                    label={'Данные ЕСИА из системы'}
                    element={
                      <div className={'relative'}>
                        <FormSwitch
                          control={form.control}
                          name={'ext_org_info'}
                          label={'Подробные данные об организации'}
                        />
                        <FormSwitch control={form.control} name={'default_data'} label={'Базовая информация'} />
                        <FormSwitch
                          control={form.control}
                          name={'temporary_residence_doc'}
                          label={'Данные разрешения на временное проживание'}
                        />
                        <FormSwitch
                          control={form.control}
                          name={'drivers_licence_doc'}
                          label={'Данные водительского удостоверения'}
                        />
                        <FormSwitch control={form.control} name={'residence_doc'} label={'Данные вида на жительство'} />
                        <FormSwitch
                          control={form.control}
                          name={'birth_cert_doc'}
                          label={'Данные свидетельства о рождении'}
                        />
                        <FormSwitch control={form.control} name={'vehicles'} label={'Данные транспортных средств'} />
                        <FormSwitch control={form.control} name={'kids'} label={'Данные о детях пользователя'} />
                      </div>
                    }
                  />
                  <UiAccordionItem
                    key={'Методы входа'}
                    className={''}
                    classElement={''}
                    label={'Методы входа'}
                    element={
                      <div className={'relative'}>
                        <FormSwitch
                          control={form.control}
                          name={'loginWithUsernameAndPassword'}
                          label={'Вход через логин и пароль'}
                        />
                        <FormSwitch control={form.control} name={'loginViaGosUslugi'} label={'Вход через госуслуги'} />
                      </div>
                    }
                  />
                </UiAccordion>
              </div>
              <FormSelect
                options={optionsAcceptedSystem}
                control={form.control}
                name={'acceptedSystem'}
                placeholder={'Статус системы'}
                label={'Выберите статус системы'}
              />
              {form.watch('acceptedSystem') === 'rejected' && (
                <FormTextArea
                  control={form.control}
                  name='reasons'
                  label='Прчина отклонения'
                  placeholder='Введите причину отклонения'
                  required
                />
              )}
              <FormRichTextEditor
                placeholder={'Введите текст'}
                control={form.control}
                name={'userAgreementSystem'}
                label={'Пользовательское соглашение системы'}
              />
              <FormInput
                formItemClassName='max-w-64'
                control={form.control}
                name='agreementUpdatedAt'
                autoComplete='off'
                label='Дата обновления пользовательского соглашения'
                placeholder='дд.мм.гггг'
                type='date'
                inputProps={{ inputSize: 'm' }}
              />
            </div>
          </div>
        </form>
      </Form>
    </Card>
  );
};

export default SystemPage;
