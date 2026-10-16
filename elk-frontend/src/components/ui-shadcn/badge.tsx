import * as React from 'react';
import { cva, type VariantProps } from 'class-variance-authority';

import { cn } from '@/lib/utils';

const badgeVariants = cva(
  'inline-flex items-center p-2 rounded-lg text-sm leading-none tracking-tight border-transparent',
  {
    variants: {
      variant: {
        default: 'bg-ghost text-primary leading-[0.6]',
        secondary: 'bg-white text-primary rounded-4xl px-2 py-[5px]',
        error: 'bg-error/20 text-error leading-[0.6]',
        outline: 'text-foreground',
      },
    },
    defaultVariants: {
      variant: 'default',
    },
  }
);

export interface BadgeProps extends React.HTMLAttributes<HTMLDivElement>, VariantProps<typeof badgeVariants> {}

function Badge({ className, variant, ...props }: BadgeProps) {
  return <div className={cn(badgeVariants({ variant }), className, '')} {...props} />;
}

export { Badge, badgeVariants };
