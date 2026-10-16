export interface FieldsType {
  formName: string;
  type: 'textarea' | 'input' | 'select' | 'switch' | 'dateRangePicker';
  onChangeSelectInput?: (value: string) => void;
  placeholder?: string;
  required?: boolean;
  label?: string;
  inputType?: InputType;
}

export type InputType = 'number' | 'text' | 'date' | 'time';
