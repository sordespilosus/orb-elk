import { FC, FunctionComponent, SVGProps } from 'react';
import Info from '@/assets/icon/info.svg?react';

interface EmptyTableProps {
  icon?: FunctionComponent<SVGProps<SVGSVGElement>>; // Сделал icon необязательным
  title: string;
  description: string;
}

const EmptyTable: FC<EmptyTableProps> = ({ icon: Icon = Info, title, description }) => {
  return (
    <div className={'max-w-[380px] flex m-auto gap-3 items-center text-left tracking-tight'}>
      <div className={'bg-ghost p-3 rounded-lg'}>
        <Icon width={24} height={24} />
      </div>
      <div className={'flex-col gap-1'}>
        <h4 className={'font-medium text-xl leading-6 text-accent-foreground-header mb-1'}>{title}</h4>
        <p className={'text-xs leading-[14px] text-muted-foreground'}>{description}</p>
      </div>
    </div>
  );
};

export default EmptyTable;
