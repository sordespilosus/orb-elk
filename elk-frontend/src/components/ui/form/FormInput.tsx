import { Control, FieldValues, Path } from 'react-hook-form';
import { FormControl, FormField, FormItem } from '@/components/ui-shadcn/form.tsx';
import { Input, InputProps } from '@/components/ui-shadcn/input.tsx';
import { cn } from '@/lib/utils.ts';

type FormInputProps<T extends FieldValues> = {
  control: Control<T>;
  name: Path<T>;
  label: string;
  placeholder: string;
  required?: boolean;
  type?: React.HTMLInputTypeAttribute;
  autoComplete?: React.HTMLInputAutoCompleteAttribute;
  formItemClassName?: string;
  inputProps?: InputProps;
};

export const FormInput = <T extends FieldValues>({
  control,
  name,
  label,
  placeholder,
  required = false,
  type = 'text',
  autoComplete,
  formItemClassName = '',
  inputProps,
}: FormInputProps<T>) => {
  return (
    <FormField
      control={control}
      name={name}
      render={({ field, fieldState }) => (
        <FormItem className={cn('space-y-0', formItemClassName)}>
          <p className='text-xs text-muted-foreground ml-3 leading-none mb-1'>
            {label} {required && <span className='text-aggressive'>*</span>}
          </p>
          <FormControl>
            <Input
              type={type}
              autoComplete={autoComplete}
              className={`outline-none focus-visible:ring-transparent m-0 ${fieldState.error ? 'border-aggressive' : 'border-ghost-hover'}`}
              placeholder={placeholder}
              {...field}
              {...inputProps}
              value={field.value || ''}
            />
          </FormControl>
          <p className='text-xs text-destructive ml-3'>{fieldState.error?.message}</p>
        </FormItem>
      )}
    />
  );
};
