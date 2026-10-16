import { FunctionComponent } from 'react';
// import Next from '@/assets/icon/next.svg?react';
interface ItemProfileInfoProps {
  onClick?: () => void;
  icon?: JSX.Element;
  disabled?: boolean;
  name: string;
  value: string;
}

const ItemProfileInfo: FunctionComponent<ItemProfileInfoProps> = ({ onClick, name, value, icon, disabled }) => {
  const Comp = onClick ? 'button' : 'div';
  return (
    <Comp
      onClick={Comp === 'button' ? onClick : undefined}
      className='flex gap-[6px] justify-between items-center py-3 border-t border-ghost-hover first:border-0 text-left w-full'
    >
      <div className='grid gap-[2px]'>
        <label className='text-muted-foreground text-sm leading-none tracking-tight pointer-events-none'>{name}</label>
        <span
          className={`text-accent-foreground-header text-lg leading-5 tracking-tight font-medium max-w-full truncate ${disabled && 'text-muted-foreground'}`}
        >
          {value}
        </span>
      </div>
      {Comp === 'button' && icon}
    </Comp>
  );
};

export default ItemProfileInfo;
