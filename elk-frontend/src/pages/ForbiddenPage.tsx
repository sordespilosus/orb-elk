import { FC } from 'react';
import { useNavigate } from 'react-router-dom';
import { Button } from '@/components/ui-shadcn/button';

const ForbiddenPage: FC = () => {
  const navigate = useNavigate();

  return (
    <main className='grid items-center justify-center h-screen px-4 bg-background'>
      <div className='max-w-[600px] w-full mx-auto bg-card rounded-4xl p-8 sm:p-12 text-center'>
        <div className='mb-6'>
          <h1 className='text-8xl sm:text-9xl font-bold text-foreground mb-4'>403</h1>
          <h2 className='text-2xl sm:text-3xl font-medium text-foreground mb-3'>Доступ запрещен</h2>
          <p className='text-muted-foreground text-base sm:text-lg leading-6'>
            У вас нет доступа к этой странице. Обратитесь к системному администратору вашей организации.
          </p>
        </div>
        <div className='mt-10'>
          <Button variant='default' size='default' onClick={() => navigate('/')} className='min-w-[200px]'>
            Вернуться на главную
          </Button>
        </div>
      </div>
    </main>
  );
};

export default ForbiddenPage;
