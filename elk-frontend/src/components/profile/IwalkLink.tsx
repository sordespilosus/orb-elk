import { FunctionComponent } from 'react';
// import Next from '@/assets/icon/next.svg?react';
interface IwalkLinkProps {
  userId: string;
  points: number;
}

const IwalkLink: FunctionComponent<IwalkLinkProps> = ({ points }) => {
  const formattedNumber = Number(points).toLocaleString('ru-RU');
  return (
    <div
      className='py-3 px-6 bg-background rounded-b-3xl grid grid-flow-col justify-between items-center border-t-4 border-card'
    >
      <div>
        <p className='text-xs leading-3 text-accent-foreground-header tracking-tight mb-1'>Баллы «Я шагаю»</p>
        <p className='font-bold text-xl leading-5 tracking-tight'>{formattedNumber}</p>
      </div>
      {/* <Next /> */}
    </div>
  );
};

export default IwalkLink;
