import { FunctionComponent } from 'react';

interface LoginButtonProps extends React.HTMLAttributes<HTMLButtonElement> {
  icon: JSX.Element;
  title: string;
  description: string;
}

const LoginButton: FunctionComponent<LoginButtonProps> = ({ icon, title, description, ...props }) => {
  return (
    <button {...props} className='border border-border rounded-2xl p-6 text-start'>
      <div className='mb-8'>{icon}</div>
      <h3 className='mb-3 font-medium text-xl leading-5 tracking-tight'>{title}</h3>
      <p className='text-muted-foreground text-sm leading-[18px] tracking-tight max-w-[320px]'>{description}</p>
    </button>
  );
};

export default LoginButton;
