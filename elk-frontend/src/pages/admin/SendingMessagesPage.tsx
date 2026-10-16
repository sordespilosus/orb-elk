import { FC } from 'react';
import Card from '@/components/ui/Card.tsx';
import { Button } from '@/components/ui-shadcn/button.tsx';

const LISTMONK_URL = import.meta.env.VITE_LISTMONK_ADMIN_URL || '';

const SendingMessagesPage: FC = () => {
  return (
    <Card className={'h-full flex'}>
      <div className={'m-auto'}>
        <p className={'text-accent-foreground-header'}>
          Чтобы воспользоваться функционалом рассылки перейдите по ссылке указанной ниже
        </p>
        <Button variant={'link'} className={'px-0'}>
          <a href={LISTMONK_URL} target={'_blank'} rel='noreferrer'>
            Ссылка для перехода на новую страницу
          </a>
        </Button>
      </div>
    </Card>
  );
};

export default SendingMessagesPage;
