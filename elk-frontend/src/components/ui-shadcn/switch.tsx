import * as React from 'react';
import * as SwitchPrimitives from '@radix-ui/react-switch';

import { cn } from '@/lib/utils';
import { cva, type VariantProps } from 'class-variance-authority';

const switchVariants = cva(
  'peer px-[2px] inline-flex shrink-0 cursor-pointer items-center rounded-full border border-border transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:cursor-not-allowed disabled:opacity-50 data-[state=checked]:bg-primary data-[state=unchecked]:bg-transparent',
  {
    variants: {
      size: {
        m: 'h-6 w-[42px] [&_span]:size-[18px] [&_span]:data-[state=checked]:translate-x-full',
        l: 'w-14 h-8 [&_span]:size-[26px] [&_span]:data-[state=checked]:translate-x-[calc(100%-2px)]',
      },
    },
    defaultVariants: {
      size: 'm',
    },
  }
);

const Switch = React.forwardRef<
  React.ElementRef<typeof SwitchPrimitives.Root>,
  React.ComponentPropsWithoutRef<typeof SwitchPrimitives.Root> & VariantProps<typeof switchVariants>
>(({ className, size, ...props }, ref) => (
  <SwitchPrimitives.Root className={cn(switchVariants({ size }), '', className)} {...props} ref={ref}>
    <SwitchPrimitives.Thumb
      className={cn(
        'pointer-events-none block rounded-full data-[state=checked]:bg-background shadow-lg ring-0 transition-transform data-[state=unchecked]:translate-x-0 data-[state=unchecked]:bg-primary'
      )}
    />
  </SwitchPrimitives.Root>
));
Switch.displayName = SwitchPrimitives.Root.displayName;

export { Switch, switchVariants };
