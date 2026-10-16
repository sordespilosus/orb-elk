import { UiControlledFormInput } from '@/components/ui/UiControlledFormInput.tsx';
import { useFormContext } from 'react-hook-form';
import { SelectDataType } from '@/types/common.ts';
import { FieldsType } from '@/types/fields-type.ts';

interface FormFieldsRendererProps {
  fields: FieldsType[];
  selectData?: SelectDataType[];
}

export const FormFieldsRenderer = ({ fields, selectData }: FormFieldsRendererProps) => {
  const { control, formState } = useFormContext();
  return (
    <>
      {fields.map((field) => (
        <UiControlledFormInput
          key={field.formName}
          input={field}
          control={control}
          errors={formState.errors}
          selectData={selectData}
          defaultValue={formState.defaultValues?.[field.formName]}
        />
      ))}
    </>
  );
};
