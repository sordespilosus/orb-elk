import { FunctionComponent } from 'react';
import DigitalEcosystemLogo from '@/assets/logo/DigitalEcosystemLogo.svg?react';
const DigitalEcosystemIcon: FunctionComponent = () => {
  return (
    <div className='flex gap-4 items-center justify-center w-full'>
      <span>
        <DigitalEcosystemLogo />
      </span>
      <div className='font-medium text-2xl leading-6 tracking-tight text-accent-foreground flex flex-col'>
        <span>Цифровая</span>
        <span>Экосистема</span>
        <span>Оренбуржья</span>
      </div>
    </div>
  );
};

export default DigitalEcosystemIcon;
