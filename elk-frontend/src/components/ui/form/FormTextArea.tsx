import { Control, FieldValues, Path } from 'react-hook-form';
import { FormControl, FormField, FormItem } from '@/components/ui-shadcn/form.tsx';
import { Textarea } from '@/components/ui-shadcn/textarea.tsx';
import { cn } from '@/lib/utils.ts';

interface FormTextAreaProps<T extends FieldValues> {
  control: Control<T>;
  name: Path<T>;
  label: string;
  placeholder: string;
  required?: boolean;
  formItemClassName?: string;
}

const FormTextArea = <T extends FieldValues>({
  control,
  name,
  label,
  placeholder,
  required = false,
  formItemClassName = '',
}: FormTextAreaProps<T>) => {
  return (
    <FormField
      control={control}
      name={name}
      render={({ field, fieldState }) => (
        <FormItem className={cn('space-y-0', formItemClassName)}>
          <p className='text-xs text-muted-foreground ml-3 mb-1'>
            {label} {required && <span className='text-aggressive'>*</span>}
          </p>
          <FormControl>
            <Textarea
              className={`${fieldState.error ? 'border-aggressive focus-visible:ring-transparent' : 'border-ghost-hover'}`}
              placeholder={placeholder}
              {...field}
              value={field.value || ''}
            />
          </FormControl>
          <p className='text-xs text-destructive ml-3'>{fieldState.error?.message}</p>
        </FormItem>
      )}
    />
  );
};

export default FormTextArea;
