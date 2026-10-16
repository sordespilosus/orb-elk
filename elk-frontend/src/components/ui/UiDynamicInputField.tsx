import { Input } from '@/components/ui-shadcn/input.tsx';
import { Textarea } from '@/components/ui-shadcn/textarea.tsx';
import { UiSelect } from '@/components/ui/UiSelect.tsx';
import { UiSwitch } from '@/components/ui/UiSwitch.tsx';
import { SelectDataType, ValueTypesType } from '@/types/common.ts';
import { DatePickerWithRange } from '@/components/ui/DateRangePicker.tsx';
import { DateRange } from 'react-day-picker';
import { InputType } from '@/types/fields-type.ts';

interface DynamicInputFieldProps<T, S> {
  type: string;
  label?: string;
  required?: boolean;
  placeholder?: string;
  value: T | DateRange;
  onChange: () => void;
  error?: string;
  selectData?: S[];
  variantFormInput?: string;
  inputType?: InputType;
  defaultValue?: string;
  onChangeSelectInput?: (value: string) => void;
}

export const UiDynamicInputField = <T extends ValueTypesType, S extends SelectDataType>({
  type,
  inputType,
  value,
  onChange,
  label,
  placeholder,
  required = true,
  error,
  selectData,
  variantFormInput,
  defaultValue,
  onChangeSelectInput,
}: DynamicInputFieldProps<T, S>) => {
  return (
    <div className={'flex flex-col gap-y-1 relative'}>
      {label && (
        <label className={'text-muted-foreground text-xs ml-3 flex gap-x-1'}>
          <span>{label}</span>
          {required && <span className={'text-aggressive'}>*</span>}
        </label>
      )}
      {error && <span className={'absolute bottom-[-17px] left-3 text-xs text-aggressive'}>{error}</span>}
      {type === 'input' && (
        <Input
          type={inputType}
          className={`${error ? 'border-aggressive focus-visible:ring-transparent' : 'border-ghost-hover'} placeholder-muted-foreground-placeholder placeholder:text-base ${variantFormInput}`}
          placeholder={placeholder}
          value={value as string}
          onChange={onChange}
          defaultValue={defaultValue}
        />
      )}
      {type === 'textarea' && (
        <Textarea
          className={`${error ? 'border-aggressive focus-visible:ring-transparent' : 'border-ghost-hover'} w-full placeholder-muted-foreground-placeholder placeholder:text-base h-134px resize-none textarea-scrollbar textarea-scrollbar-thumb textarea-scrollbar-thumb-hover textarea-scrollbar-track`}
          placeholder={placeholder}
          value={value as string}
          onChange={onChange}
          defaultValue={defaultValue}
        />
      )}
      {type === 'select' && selectData && placeholder && (
        <UiSelect
          className={`${error ? 'border-aggressive' : 'border-ghost-hover'} w-full`}
          placeholder={placeholder}
          value={value as string}
          onChange={onChange}
          data={selectData}
          defaultValue={defaultValue}
          onChangeSelectInput={onChangeSelectInput}
        />
      )}
      {type === 'switch' && placeholder && (
        <UiSwitch value={value as boolean} className={variantFormInput} label={placeholder} onChange={onChange} />
      )}
      {type === 'dateRangePicker' && (
        <DatePickerWithRange value={value as DateRange} onChangeDate={onChange} placeholder={placeholder} />
      )}
    </div>
  );
};
