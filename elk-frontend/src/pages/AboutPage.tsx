import { useSystemDescriptionQuery } from '@/api/clients/hooks';
import Card from '@/components/ui/Card';
import { Button } from '@/components/ui-shadcn/button';
import { $url } from '@/utils/config';
import { FunctionComponent } from 'react';

const AboutPage: FunctionComponent = () => {
  const { data, isLoading, isError, error } = useSystemDescriptionQuery();

  if (isLoading) return <div>Загрузка...</div>;

  if (isError) {
    void error;
    // return <div>An error occurred: {(error as Error).message}</div>;
    return null;
  }

  return (
    <Card className='flex flex-col gap-8'>
      {data?.data && (
        <div
          className='prose max-w-none text-base leading-6 text-accent-foreground-header'
          dangerouslySetInnerHTML={{ __html: data.data }}
        />
      )}
      <Button variant='link' size='smLink' asChild className='w-fit'>
        <a href={`${$url}/api/v2/agreement`} className='underline'>
          Пользовательское соглашение
        </a>
      </Button>
    </Card>
  );
};

export default AboutPage;
