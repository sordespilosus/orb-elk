import { FunctionComponent, useContext, useEffect } from 'react';
import { Button, buttonVariants } from '../ui-shadcn/button';
import Modal from '../ui/Modal';
import { cn } from '@/lib/utils';
import MoreDetailsService from './MoreDetailsService.tsx';
import Card from '../ui/Card';
import { DialogClose } from '@/components/ui-shadcn/dialog.tsx';
import Union from '@/assets/icon/union.svg?react';
import { ClientBody, ESCPS, ScopeNames } from '@/api/clients/types.ts';
import { useRecallClientAgreementMutation } from '@/api/clients/hooks.ts';
import { ToastContext } from '../toast/ToastContext.ts';

export interface ServiceProps {
  item: ClientBody;
  generalScope: ScopeNames;
  generalEsiaScopes: ESCPS;
}

const CardService: FunctionComponent<ServiceProps> = (props) => {
  const { item, generalScope, generalEsiaScopes } = props;
  const { mutate: recallClientAgreement, error } = useRecallClientAgreementMutation();

  const { toast } = useContext(ToastContext);

  useEffect(() => {
    if (error) {
      toast({
        title: 'Ошибка',
        message: error.message,
        variant: 'error',
      });
    }
  }, [error]);

  return (
    <Card className='p-8 flex flex-col'>
      <div className='mb-8'>
        {item.logo ? (
          <img src={item.logo} width={72} height={72} alt='' />
        ) : (
          <div className={'w-[72px] h-[72px] bg-background rounded-lg flex'}>
            <Union className={'m-auto'} />
          </div>
        )}
      </div>
      <h3 className='mb-3 font-medium text-xl leading-6 tracking-tight'>{item.name}</h3>
      <p className='mb-6 text-muted-foreground text-sm leading-4 tracking-tight'>{item.description}</p>
      <div className='grid grid-cols-2 gap-2 mt-auto'>
        <Modal
          key={item.id}
          leftIconHeader={item.logo ?? undefined}
          header={item.name}
          main={<MoreDetailsService item={item} generalScope={generalScope} generalEsiaScopes={generalEsiaScopes} />}
          trigger={<Button variant='ghost'>Подробнее</Button>}
          footerContent={
            <>
              <DialogClose className={cn(buttonVariants({ variant: 'ghost' }), 'w-full', 'max-mobile:hidden')}>
                Отмена
              </DialogClose>
              <Modal
                key={item.id}
                trigger={
                  <Button
                    disabled={item.revoked_agreement}
                    className={cn(buttonVariants({ variant: 'danger' }), 'w-full')}
                  >
                    Отозвать
                  </Button>
                }
                main={
                  <div>
                    <p>Вы действительно хотите отозвать разрешение на доступ к сервису?</p>
                    <p>
                      Обратите внимание, что доступ к системе может быть ограничен до повторного предоставления
                      согласия.
                    </p>
                  </div>
                }
                footerContent={
                  <>
                    <DialogClose className={cn(buttonVariants({ variant: 'ghost' }), 'w-full')}>Отмена</DialogClose>
                    <DialogClose
                      className={cn(buttonVariants({ variant: 'danger' }), 'w-full')}
                      onClick={() => recallClientAgreement(item.id)}
                    >
                      Отозвать
                    </DialogClose>
                  </>
                }
                header={'Отозвать разрешение'}
              />
              {/* <DialogClose className={cn(buttonVariants({ variant: 'default' }))}>Принять и разрешить</DialogClose> */}
            </>
          }
        />

        <Button asChild>
          <a href={item.url_site} target='_blank' rel='noreferrer'>
            Перейти
          </a>
        </Button>
      </div>
    </Card>
  );
};

export default CardService;
