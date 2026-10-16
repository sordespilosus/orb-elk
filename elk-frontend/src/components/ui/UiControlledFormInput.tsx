import { Path, FieldValues, Controller, Control } from 'react-hook-form';
import { UiDynamicInputField } from '@/components/ui/UiDynamicInputField.tsx';
import { FieldErrors } from 'react-hook-form';
import { SelectDataType } from '@/types/common.ts';
import { FieldsType } from '@/types/fields-type.ts';

export type FormFieldConfig = FieldsType;

export interface ControlledFormInputProps<T extends FieldValues, S> {
  input: FormFieldConfig;
  control: Control<T>;
  errors: FieldErrors<T>;
  selectData?: Array<S>;
  variantFormInput?: string;
  defaultValue?: string;
}

export const UiControlledFormInput = <T extends FieldValues, S extends SelectDataType>({
  input,
  control,
  errors,
  variantFormInput,
  selectData,
  defaultValue,
}: ControlledFormInputProps<T, S>) => {
  return (
    <Controller
      key={input.formName.toString()}
      name={input.formName as Path<T>}
      control={control}
      render={({ field: { onChange, name, value } }) => (
        <UiDynamicInputField
          variantFormInput={variantFormInput}
          type={input.type}
          onChangeSelectInput={input.onChangeSelectInput}
          inputType={input.inputType}
          value={value || ''}
          label={input.label}
          required={input.required}
          placeholder={input.placeholder}
          defaultValue={defaultValue}
          onChange={onChange}
          error={errors[name as keyof T]?.message as string}
          selectData={selectData}
        />
      )}
    />
  );
};
