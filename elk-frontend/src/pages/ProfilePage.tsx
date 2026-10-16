import Card from '@/components/ui/Card';
import IwalkLink from '@/components/profile/IwalkLink';
import TrashCan from '@/assets/icon/trash-can.svg?react';
import Logout from '@/assets/icon/logout.svg?react';
import Copy from '@/assets/icon/copy.svg?react';
import { FunctionComponent, memo, useContext, useEffect } from 'react';
import { Button, buttonVariants } from '@/components/ui-shadcn/button';
import ItemProfileInfo from '@/components/profile/ItemProfileInfo';
import Modal from '@/components/ui/Modal';
import { cn } from '@/lib/utils';
import UserData from '@/components/profile/UserData';
import CardUser from '@/components/profile/CardUser';
import QrCode from 'react-qr-code';
import { DialogClose } from '@/components/ui-shadcn/dialog.tsx';
import { useDeleteProfileMutation, useUserProfileQuery } from '@/api/user';
import { useLogoutMutation } from '@/api/auth/hooks';
import { useNavigate } from 'react-router-dom';
import { ToastContext } from '@/components/toast/ToastContext';
const ProfilePage: FunctionComponent = () => {
  const navigate = useNavigate();
  const { data: profileData, isLoading, isError, error } = useUserProfileQuery();
  const { mutate: logout, isSuccess: isLogoutSuccess } = useLogoutMutation();
  const { mutate: deleteProfile, isSuccess: isDeleteProfileSuccess } = useDeleteProfileMutation();
  const { toast } = useContext(ToastContext);

  useEffect(() => {
    if (!isLogoutSuccess) return;
    navigate('/login');
  }, [isLogoutSuccess]);

  useEffect(() => {
    if (!isDeleteProfileSuccess) return;
    navigate('/login');
  }, [isDeleteProfileSuccess]);

  if (isLoading) return <div>Loading...</div>;

  if (isError) return <div>{error.message}</div>;
  if (!profileData) return <div>Profile data not found</div>;

  const handleLogout = () => {
    logout();
  };

  return (
    <div className='grid gap-4 xl:grid-cols-[minmax(0,284px)_auto_auto_auto] mobile:grid-cols-2 mobile:grid-rows-2 '>
      <Card className='p-2 mobile:col-span-2 md:col-span-1'>
        <CardUser id={profileData.id} avatarUrl={profileData.photo ?? ''} fullName={profileData.name} />
        <IwalkLink points={profileData.points ?? 0} userId={profileData.id.toString()} />
        <div className='grid grid-cols-2 gap-2 mt-[22px] mb-3 mx-3'>
          <Modal
            header='Удаление профиля'
            main={<span>Вы уверены, что хотите безвозвратно удалить свой профиль?</span>}
            trigger={
              <Button variant='secondary' size='md' className='text-aggressive'>
                <TrashCan />
                Удалить
              </Button>
            }
            footerContent={
              <>
                <DialogClose className={cn(buttonVariants({ variant: 'ghost' }))}>Отмена</DialogClose>
                <DialogClose
                  onClick={() => deleteProfile()}
                  className={cn(buttonVariants({ variant: 'default' }), 'bg-aggressive !m-0')}
                >
                  Удалить
                </DialogClose>
              </>
            }
          />
          <Button variant={'secondary'} size={'md'} onClick={handleLogout}>
            <Logout />
            Выйти
          </Button>
        </div>
      </Card>
      <UserData />
      <Card className='p-8 flex flex-col justify-between items-center'>
        <QrCode value={`Id = ${profileData.id}`} level='Q' className='mx-auto' size={150} fgColor='#021633' />
        <ItemProfileInfo
          name='Уникальный ID'
          value={profileData.id.toString()}
          onClick={() => {
            if (!navigator.clipboard) {
              toast({
                title: 'Ошибка',
                variant: 'error',
                message: 'Браузер не поддерживает копирование в буфер обмена',
              });
              return;
            }
            navigator.clipboard.writeText(profileData.id.toString());
            toast({
              title: 'Успешно',
              variant: 'success',
              message: 'ID успешно скопирован в буфер обмена',
            });
          }}
          icon={<Copy />}
        />
      </Card>
    </div>
  );
};

export default memo(ProfilePage);
