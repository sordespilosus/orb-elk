import { FunctionComponent } from 'react';
import { ServiceProps } from '@/components/service/CardService.tsx';
import RenderScopes from '@/components/service/RenderScopes.tsx';
import { Button } from '@/components/ui-shadcn/button.tsx';
import { $url } from '@/utils/config';

const MoreDetailsService: FunctionComponent<ServiceProps> = ({ item, generalScope, generalEsiaScopes }) => {
  const hasVisible = Object.values(item.scope).includes(true) && generalScope;
  const hasEsiaVisible = Object.values(item.client_hits_esia_scope ?? {}).includes(true) && generalEsiaScopes;
  return (
    <div className={'text-base leading-5 tracking-tight text-muted-foreground flex flex-col gap-6'}>
      {item.description && <p className={'text-accent-foreground-header'}>{item.description}</p>}
      {hasVisible && (
        <RenderScopes
          values={Object.keys(item.scope).map((key) => generalScope[key as keyof typeof generalScope])}
          header={'Для входа в систему необходимо будет предоставить следующие разрешения:'}
        />
      )}
      {hasEsiaVisible && (
        <RenderScopes
          values={Object.keys(item.client_hits_esia_scope ?? {}).map(
            (key) => generalEsiaScopes[key as keyof typeof generalEsiaScopes].description
          )}
          header={'Также при входе будут предоставлены следующие данные из вашего профиля Госуслуг:'}
        />
      )}
      <div>
        <p className={'text-sm leading-4'}>
          Продолжая, Вы принимаете Пользовательское соглашение системы {item.name} (Редакция обновлена
          {' ' + item.agreement_updated_at})
        </p>
        <Button variant={'link'} size={'smLink'} asChild>
          <a href={`${$url}/api/v2/client/${item.id}/agreement`}>Пользовательское соглашение </a>
        </Button>
      </div>
    </div>
  );
};

export default MoreDetailsService;
