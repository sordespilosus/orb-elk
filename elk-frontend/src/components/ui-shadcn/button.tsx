import * as React from 'react';
import { Slot } from '@radix-ui/react-slot';
import { cva, type VariantProps } from 'class-variance-authority';

import { cn } from '@/lib/utils';

const buttonVariants = cva(
  'inline-flex -outline-offset-2 items-center justify-center gap-2 text-foreground whitespace-nowrap rounded-xl text-base font-normal leading-5 tracking-tight transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg]:size-4 [&_svg]:shrink-0',
  {
    variants: {
      variant: {
        default: 'bg-primary text-primary-foreground hover:bg-primary-hover',
        ghost: 'bg-ghost hover:bg-ghost-hover hover:text-accent-foreground',
        secondary: 'outline outline-1 outline-border hover:outline-2',
        outline: 'outline outline-0 outline-border hover:outline-2',
        destructive: 'bg-destructive text-destructive-foreground hover:bg-destructive/90',
        link: 'text-primary underline-offset-4 hover:underline',
        editor: 'p-1 flex items-center justify-center',
        danger: 'bg-destructive text-destructive-foreground hover:bg-destructive/90',
      },
      size: {
        default: 'py-[14px] px-6 rounded-xl',
        md: 'text-sm leading-4 py-[14px] px-6',
        sm: 'text-sm py-2 px-4 rounded-lg-plus',
        smLink: 'text-base py-2 rounded-lg-plus',
        smIcon: 'text-sm min-w-8 p-2 h-8 rounded-md text-xs leading-[14px]',
        xsIcon: 'text-sm p-1 rounded-md text-xs leading-[14px]',
        icon: 'p-2 rounded-full w-fit h-fit',
        iconBox: 'p-2 rounded-md w-fit h-fit',
        editor: 'size-8 [&_svg]:size-8',
      },
    },
    defaultVariants: {
      variant: 'default',
      size: 'default',
    },
  }
);

export interface ButtonProps
  extends React.ButtonHTMLAttributes<HTMLButtonElement>,
    VariantProps<typeof buttonVariants> {
  asChild?: boolean;
  isActive?: boolean;
}

const Button = React.forwardRef<HTMLButtonElement, ButtonProps>(
  ({ className, variant, size, asChild = false, isActive, ...props }, ref) => {
    const Comp = asChild ? Slot : 'button';
    return (
      <Comp
        className={cn(buttonVariants({ variant, size, className }), isActive && 'outline-2')}
        ref={ref}
        {...props}
      />
    );
  }
);
Button.displayName = 'Button';

export { Button, buttonVariants };
