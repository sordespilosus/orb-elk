import CardService from '@/components/service/CardService';
import { FunctionComponent } from 'react';
import { useClientsQuery } from '@/api/clients';

const ServicePage: FunctionComponent = () => {
  const { isLoading, isError, error, data } = useClientsQuery();

  if (isLoading) return <div>Loading...</div>;

  if (isError) return <div>An error occurred: {(error as Error).message}</div>;

  if (!data) return <div>Нет доступных систем</div>;

  return (
    <div className='grid gap-4 md:grid-cols-2 xl:grid-cols-[repeat(3,384px)]'>
      {data.data.map((item, index) => (
        <CardService key={index} item={item} generalScope={data.scope} generalEsiaScopes={data.esiaScopes} />
      ))}
    </div>
  );
};

export default ServicePage;
