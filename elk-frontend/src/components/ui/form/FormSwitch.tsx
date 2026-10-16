import { cva, type VariantProps } from 'class-variance-authority';
import { cn } from '@/lib/utils';
import { Switch, switchVariants } from '@/components/ui-shadcn/switch.tsx';
import * as React from 'react';
import { FormControl, FormField, FormItem } from '@/components/ui-shadcn/form.tsx';
import { Control, FieldValues, Path } from 'react-hook-form';

const formSwitchVariants = cva('', {
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

interface CustomSwitchProps<T extends FieldValues>
  extends React.HTMLAttributes<HTMLDivElement>,
    VariantProps<typeof formSwitchVariants>,
    VariantProps<typeof switchVariants> {
  control: Control<T>;
  name: Path<T>;
  label?: string;
  sizeSwitch?: keyof typeof switchVariants;
  id?: string;
  children?: React.ReactNode;
}

const FormSwitch = <T extends FieldValues>({
  control,
  name,
  label,
  variant,
  className,
  children,
  size,
}: CustomSwitchProps<T>) => {
  return (
    <FormField
      control={control}
      name={name}
      render={({ field: { value, onChange } }) => (
        <FormItem className={cn('space-y-0')}>
          <div className={'space-x-4 flex items-center'}>
            <div
              className={cn(formSwitchVariants({ variant }), className, ' w-full flex items-center justify-between gap-4')}
            >
              <label className={'text-sm text-muted-foreground text-start'}>{label}</label>
              <FormControl>
                <Switch size={size} checked={value} onCheckedChange={onChange} />
              </FormControl>
            </div>
            {children}
          </div>
        </FormItem>
      )}
    />
  );
};

export { FormSwitch, formSwitchVariants };
