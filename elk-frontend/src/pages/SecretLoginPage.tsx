import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { Form } from '@/components/ui-shadcn/form';
import { Button } from '@/components/ui-shadcn/button';
import { LoginBody, useLoginMutation } from '@/api/auth';
import { LoginBodySchema } from '@/api/auth/types';
import { useContext, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { ToastContext } from '@/components/toast/ToastContext';
import { FormInput } from '@/components/ui/form/FormInput.tsx';

export const SecretLoginPage = () => {
  const navigate = useNavigate();
  const { toast } = useContext(ToastContext);

  const form = useForm<LoginBody>({
    resolver: zodResolver(LoginBodySchema),
    defaultValues: { login: '', password: '' },
  });

  const { mutate: login, isSuccess, isPending, error } = useLoginMutation();

  useEffect(() => {
    if (isSuccess) {
      navigate('/');
    }
  }, [isSuccess]);

  useEffect(() => {
    if (error) {
      toast({ title: 'Ошибка', message: error.message, variant: 'error' });
    }
  }, [error]);

  const onSubmit = async (values: LoginBody) => {
    login(values);
  };

  return (
    <div className='flex flex-col justify-center h-screen w-[90%] mx-auto mobile:w-[350px]'>
      <h1 className='text-2xl font-bold ml-3 mb-2'>Секретный вход</h1>
      <Form {...form}>
        <form onSubmit={form.handleSubmit(onSubmit)} className='space-y-4'>
          <FormInput
            control={form.control}
            name='login'
            type='email'
            autoComplete='email'
            label='Логин'
            placeholder='Введите логин'
            required
          />
          <FormInput
            control={form.control}
            name='password'
            type='password'
            autoComplete='current-password'
            label='Пароль'
            placeholder='Введите пароль'
            required
          />
          <Button type='submit' className='w-full' disabled={isPending}>
            {isPending ? 'Вход...' : 'Войти'}
          </Button>
        </form>
      </Form>
    </div>
  );
};
