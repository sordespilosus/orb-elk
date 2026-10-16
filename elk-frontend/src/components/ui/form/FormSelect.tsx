import { Control, FieldValues, Path } from 'react-hook-form';
import { FormControl, FormField, FormItem } from '@/components/ui-shadcn/form.tsx';
import { Input } from '@/components/ui-shadcn/input.tsx';
import { cn } from '@/lib/utils.ts';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui-shadcn/select.tsx";

type FormSelectProps<T extends FieldValues> = {
  control: Control<T>;
  name: Path<T>;
  label?: string;
  placeholder?: string;
  required?: boolean;
  className?: string;
  onChangeSelectInput?: (value: string) => void;
  options: { value: string; name: string }[];
};

export const FormSelect = <T extends FieldValues>({
  control,
  name,
  label,
  placeholder,
  required = false,
  className,
  onChangeSelectInput,
  options,
}: FormSelectProps<T>) => {
  return (
    <FormField
      control={control}
      name={name}
      render={({ field, fieldState }) => (
        <FormItem className={cn('space-y-0')}>
          <p className='text-xs text-muted-foreground ml-3 leading-none mb-1'>
            {label} {required && <span className='text-aggressive'>*</span>}
          </p>
          <FormControl>
            <Select value={field.value} onValueChange={field.onChange} defaultValue={field.value}>
              <SelectTrigger
                className={cn(
                  `w-full py-[4px] px-4 border-input rounded-xl shadow-sm text-base border text-muted-foreground-placeholder ${fieldState.error && 'border-aggressive'}`,
                  className
                )}
              >
                <SelectValue placeholder={placeholder} />
              </SelectTrigger>
              <SelectContent>
                {onChangeSelectInput && (
                  <Input onChange={(e) => onChangeSelectInput(e.target.value)} className={'mb-4'} inputSize={'m'} />
                )}
                {options.map((i, index) => (
                  <SelectItem key={index} value={i.value}>
                    {i.name}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </FormControl>
          <p className='text-xs text-destructive ml-3'>{fieldState.error?.message}</p>
        </FormItem>
      )}
    />
  );
};
