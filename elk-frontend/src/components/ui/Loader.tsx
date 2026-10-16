import { type FC } from 'react';
import { Loader as LoaderIcon } from 'lucide-react';
const Loader: FC = () => {
  return (
    <div className='w-full h-dvh flex items-center justify-center'>
      <LoaderIcon className='animate-spin' />
    </div>
  );
};

export { Loader };
