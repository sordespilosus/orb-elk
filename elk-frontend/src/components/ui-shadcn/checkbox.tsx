import * as React from 'react';
import * as CheckboxPrimitive from '@radix-ui/react-checkbox';
import { cn } from '@/lib/utils';
import { CheckIcon } from '@radix-ui/react-icons';
import { cva, type VariantProps } from 'class-variance-authority';

const checkboxVariants = cva(
  'peer shrink-0 rounded-sm border border-border focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50',
  {
    variants: {
      variant: {
        default: 'rounded-sm',
        round: 'rounded-full',
      },
      size: {
        default: 'p-1 w-8 h-8',
        xs: 'p-[3px] w-6 h-6',
      },
    },
    defaultVariants: {
      variant: 'default',
      size: 'default',
    },
  }
);

const Checkbox = React.forwardRef<
  React.ElementRef<typeof CheckboxPrimitive.Root>,
  React.ComponentPropsWithoutRef<typeof CheckboxPrimitive.Root> & VariantProps<typeof checkboxVariants>
>(({ className, variant, size, ...props }, ref) => (
  <CheckboxPrimitive.Root ref={ref} className={cn(checkboxVariants({ variant, size }), '', className)} {...props}>
    <CheckboxPrimitive.Indicator
      className={cn(
        variant === 'round' ? 'rounded-full' : 'rounded-[2px]',
        'flex items-center h-auto w-auto justify-center text-current data-[state=checked]:bg-primary data-[state=checked]:text-primary-foreground'
      )}
    >
      <CheckIcon className='h-full w-full' />
    </CheckboxPrimitive.Indicator>
  </CheckboxPrimitive.Root>
));
Checkbox.displayName = CheckboxPrimitive.Root.displayName;

export { Checkbox };
