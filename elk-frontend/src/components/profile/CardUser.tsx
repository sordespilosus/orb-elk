import { FunctionComponent, useCallback, useContext, useEffect } from 'react';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui-shadcn/avatar';
import { Skeleton } from '@/components/ui-shadcn/skeleton';
import { Badge } from '@/components/ui-shadcn/badge';
import { AvatarPickModal } from '../ui/AvatarPickModal';
import { useUploadAvatarMutation } from '@/api/user';
import Plus from '@/assets/icon/add.svg?react';
import { ToastContext } from '../toast/ToastContext';
import { $url } from '@/utils/config';

interface CardUserProps {
  id: number;
  avatarUrl: string;
  fullName: string;
}

const CardUser: FunctionComponent<CardUserProps> = ({ id, avatarUrl, fullName }) => {
  const { mutate: uploadAvatar, isSuccess, error } = useUploadAvatarMutation();
  const { toast } = useContext(ToastContext);
  const handleAvatarEditorDataChange = useCallback((e: Blob) => {
    if (e) {
      uploadAvatar(e);
    } else {
      console.error('no AvatarEditorDataChange');
    }
  }, []);

  useEffect(() => {
    if (isSuccess) {
      toast({ message: 'Аватар успешно изменен', title: 'Сохранено', variant: 'success' });
    }
  }, [isSuccess]);

  useEffect(() => {
    if (error) {
      toast({ message: error.message, title: 'Ошибка', variant: 'error' });
    }
  }, [error]);

  return (
    <div className='bg-background rounded-t-3xl'>
      <div className='grid grid-flow-col items-start justify-between p-3'>
        <AvatarPickModal onAvatarEditorDataChange={handleAvatarEditorDataChange}>
          <div className='relative cursor-pointer'>
            <Avatar className='h-24 w-24 border-4 border-white ml-[10px]'>
              <AvatarImage src={`${$url}/storage/avatars/${avatarUrl}`} />
              <AvatarFallback>
                <Skeleton className='w-6 h-6 rounded-full' />
              </AvatarFallback>
            </Avatar>
            <div className='absolute bottom-0 right-0 rounded-full bg-white p-1'>
              <Plus className='w-5 h-5' />
            </div>
          </div>
        </AvatarPickModal>
        <Badge variant={'secondary'} className=''>
          ID {id}
        </Badge>
      </div>
      <h4 className='mx-6 pb-6 font-medium text-xl leading-[22px] tracking-tight'>{fullName}</h4>
    </div>
  );
};

export default CardUser;
