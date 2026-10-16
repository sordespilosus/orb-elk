import { cva, type VariantProps } from 'class-variance-authority';
import { cn } from '@/lib/utils';
import { Switch, switchVariants } from '@/components/ui-shadcn/switch.tsx';
import { FC } from 'react';
import * as React from 'react';

const toggleAreaVariants = cva('', {
  variants: {
    variant: {
      toggleAreaRow: 'h-12',
      toggleArea: 'h-10 [&_label]:mx-3',
    },
  },
  defaultVariants: {
    variant: 'toggleAreaRow',
  },
});

interface CustomSwitchProps
  extends React.HTMLAttributes<HTMLDivElement>,
    VariantProps<typeof toggleAreaVariants>,
    VariantProps<typeof switchVariants> {
  label: string;
  onChange: () => void;
  sizeSwitch?: keyof typeof switchVariants;
  value: boolean;
}

const UiSwitch: FC<CustomSwitchProps> = ({ label, onChange, size, variant, className, value }) => {
  return (
    <div
      className={cn(toggleAreaVariants({ variant }), className, ' w-full flex items-center justify-between space-x-2')}
    >
      <label className={'text-sm text-muted-foreground'}>{label}</label>
      <Switch size={size} checked={value} onCheckedChange={onChange} />
    </div>
  );
};

export { UiSwitch, toggleAreaVariants };
