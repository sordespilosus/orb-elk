import { FC, useContext, useEffect, useMemo, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { useForm } from 'react-hook-form';
import { Check, Pencil } from 'lucide-react';
import Card from '@/components/ui/Card.tsx';
import { Button } from '@/components/ui-shadcn/button.tsx';
import { Form } from '@/components/ui-shadcn/form.tsx';
import Cancel from '@/assets/icon/cancel.svg?react';
import { FormInput } from '@/components/ui/form/FormInput.tsx';
import { FormSwitch } from '@/components/ui/form/FormSwitch.tsx';
import { useUpdateUserMutation, useUser, type User } from '@/api/users';
import { adminUsersRoutes } from '@/config/navigation.tsx';
import { ToastContext } from '@/components/toast/ToastContext.ts';

type UserFormValues = {
  email: string;
  blocked: boolean;
  isAdmin: boolean;
  isDeleted: boolean;
  isConfirmed: boolean;
};

type ReadOnlyInfoProps = {
  label: string;
  value: string;
};

const ReadOnlyInfo: FC<ReadOnlyInfoProps> = ({ label, value }) => (
  <div className={'space-y-0'}>
    <p className='text-xs text-muted-foreground ml-3 leading-none mb-1'>{label}</p>
    <p className={'px-3 py-[13px] text-sm leading-4 tracking-tight text-accent-foreground'}>{value || '-'}</p>
  </div>
);

const formatGender = (gender?: string | null): string => {
  const normalizedGender = gender?.trim().toLowerCase();

  if (!normalizedGender) return '';
  return normalizedGender === 'male' ? 'М' : 'Ж';
};

const parseBirthday = (birthday: string): Date => {
  const dateMatch = birthday.match(/^(\d{2})\.(\d{2})\.(\d{4})$/);

  if (dateMatch) {
    const [, day, month, year] = dateMatch;

    return new Date(Number(year), Number(month) - 1, Number(day));
  }

  return new Date(birthday);
};

const getAge = (birthday?: string | null): string => {
  if (!birthday) return '';

  const birthDate = parseBirthday(birthday);
  if (Number.isNaN(birthDate.getTime())) return '';

  const today = new Date();
  const age = today.getFullYear() - birthDate.getFullYear();
  const hasHadBirthdayThisYear =
    today.getMonth() > birthDate.getMonth() ||
    (today.getMonth() === birthDate.getMonth() && today.getDate() >= birthDate.getDate());

  return String(hasHadBirthdayThisYear ? age : age - 1);
};

const getFormValues = (user?: User): UserFormValues => ({
  email: user?.email ?? '',
  blocked: user?.blocked ?? false,
  isAdmin: user?.is_admin ?? false,
  isDeleted: user?.is_deleted ?? false,
  isConfirmed: Boolean(user?.email_verified_at),
});

const UserPage: FC = () => {
  const { userId } = useParams();
  const navigate = useNavigate();
  const { toast } = useContext(ToastContext);
  const [isEmailEditable, setIsEmailEditable] = useState(false);

  const { data, isSuccess, isLoading, isError } = useUser(Number(userId));
  const user = data?.data;
  const updateUserMutation = useUpdateUserMutation();

  const defaultValues = useMemo(() => getFormValues(user), [user]);
  const userInfo = useMemo(
    () => ({
      fio: user?.fio ?? '',
      email: user?.email ?? '',
      phone: user?.phone ?? '',
      gender: formatGender(user?.gender),
      age: user?.age ? String(user.age) : getAge(user?.birthday),
    }),
    [user]
  );

  const form = useForm<UserFormValues>({
    defaultValues,
  });

  useEffect(() => {
    if (isSuccess) {
      form.reset(defaultValues);
      setIsEmailEditable(false);
    }
  }, [isSuccess, defaultValues, form]);

  useEffect(() => {
    if (updateUserMutation.isSuccess) {
      toast({ message: 'Пользователь успешно сохранён', title: 'Сохранено', variant: 'success' });
      navigate(adminUsersRoutes.users);
    }

    if (updateUserMutation.error) {
      toast({
        message: updateUserMutation.error.message || 'Произошла ошибка при сохранении пользователя',
        title: 'Ошибка',
        variant: 'error',
      });
    }
  }, [updateUserMutation.isSuccess, updateUserMutation.error, navigate, toast]);

  const onReset = () => {
    form.reset(defaultValues);
    setIsEmailEditable(false);
  };

  const onSubmit = (values: UserFormValues) => {
    if (!user) return;

    updateUserMutation.mutate({
      user_id: user.id,
      email: values.email,
      blocked: values.blocked,
      is_admin: values.isAdmin,
      is_deleted: values.isDeleted,
      email_verified: values.isConfirmed,
    });
  };

  return (
    <Card>
      <Form {...form}>
        <form id='userForm' onSubmit={form.handleSubmit(onSubmit)}>
          <div className={'mb-6 flex justify-between'}>
            <h3 className={'font-medium text-xl leading-6 tracking-tight text-accent-foreground-header'}>
              Редактирование пользователя
            </h3>
            <div className={'flex gap-3.5'}>
              <Button
                disabled={updateUserMutation.isPending || isLoading || isError}
                type={'button'}
                onClick={onReset}
                variant='secondary'
                size='md'
              >
                <Cancel /> Сброс
              </Button>
              <Button disabled={updateUserMutation.isPending || isLoading || isError || !user} size='md'>
                <Check /> Сохранить изменения
              </Button>
            </div>
          </div>
          <div>
            <h4 className={'font-medium leading-5 tracking-tight text-accent-foreground-header mb-7'}>
              Основные параметры
            </h4>
            <div className={'grid gap-6'}>
              <div className={'grid grid-cols-2 gap-y-3 gap-x-2.5'}>
                <div className={'flex items-start gap-2'}>
                  <FormInput
                    formItemClassName={'flex-grow'}
                    control={form.control}
                    name='email'
                    autoComplete='off'
                    label='Логин'
                    placeholder='Введите логин'
                    inputProps={{
                      inputSize: 'm',
                      readOnly: !isEmailEditable,
                    }}
                  />
                  <Button
                    className={'mt-[17px]'}
                    disabled={isEmailEditable}
                    type={'button'}
                    variant={'outline'}
                    size={'icon'}
                    onClick={() => setIsEmailEditable(true)}
                  >
                    <Pencil size={16} />
                  </Button>
                </div>
                <ReadOnlyInfo label={'ФИО'} value={userInfo.fio} />
                <ReadOnlyInfo label={'Email'} value={userInfo.email} />
                <ReadOnlyInfo label={'Телефон'} value={userInfo.phone} />
                <ReadOnlyInfo label={'Пол'} value={userInfo.gender} />
                <ReadOnlyInfo label={'Возраст'} value={userInfo.age} />
              </div>
              <div className={'grid gap-2'}>
                <FormSwitch control={form.control} name={'blocked'} label={'Заблокирован'} />
                <FormSwitch control={form.control} name={'isAdmin'} label={'Админ'} />
                <FormSwitch control={form.control} name={'isDeleted'} label={'Удалён'} />
                <FormSwitch control={form.control} name={'isConfirmed'} label={'Подтверждён'} />
              </div>
            </div>
          </div>
        </form>
      </Form>
    </Card>
  );
};

export default UserPage;
