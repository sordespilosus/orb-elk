import * as React from 'react';
import { cn } from '@/lib/utils';
import { cva, VariantProps } from 'class-variance-authority';
import ClockIcon from '@/assets/icon/clock.svg?react';

const inputVariants = cva(
  "flex w-full rounded-xl border px-4 text-accent-foreground tracking-tight border-input bg-transparent shadow-sm transition-colors file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-foreground placeholder:text-muted-foreground focus-visible:outline-none focus-visible:border-primary disabled:cursor-not-allowed disabled:opacity-50 [&[type='time']]:input-time [&[type='time']]:focus:before:hidden",
  {
    variants: {
      inputSize: {
        default: 'text-base leading-5 py-[14px] ',
        m: 'text-sm leading-4 py-[13px]',
        s: 'text-xs leading-[14px] py-[9px] px-2',
      },
    },
    defaultVariants: {
      inputSize: 'default',
    },
  }
);

export interface InputProps extends React.InputHTMLAttributes<HTMLInputElement>, VariantProps<typeof inputVariants> {
  leftIcon?: React.ReactNode;
  rightIcon?: React.ReactNode;
}

const Input = React.forwardRef<HTMLInputElement, InputProps>(
  ({ className, inputSize, type, leftIcon, rightIcon, ...props }, ref) => {
    const inputRef = React.useRef<HTMLInputElement>(null);
    React.useImperativeHandle(ref, () => inputRef.current as HTMLInputElement);
    const showPickerForInputTypeTime = () => {
      if (type === 'time' && inputRef.current?.showPicker) {
        inputRef.current.showPicker();
      }
    };
    return (
      <div className='relative flex items-center w-full'>
        {leftIcon && <div className='absolute left-4 flex items-center pointer-events-none'>{leftIcon}</div>}
        <input
          type={type}
          className={cn(
            inputVariants({ inputSize, className }),
            `${props.value !== '' && "[&[type='time']]:before:hidden"}`,
            leftIcon && 'pl-10',
            rightIcon && 'pr-10'
          )}
          ref={inputRef}
          {...props}
        />
        {rightIcon && <div className='absolute right-4 flex items-center pointer-events-none'>{rightIcon}</div>}
        {type === 'time' && (
          <button onClick={showPickerForInputTypeTime} className='absolute right-4 flex items-center'>
            <ClockIcon color={'#A3A3A3'} />
          </button>
        )}
      </div>
    );
  }
);
Input.displayName = 'Input';

export { Input, inputVariants };
