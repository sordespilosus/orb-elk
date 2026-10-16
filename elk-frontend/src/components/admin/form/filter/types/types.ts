import { SelectDataType } from '@/types/common.ts';
import { FieldsType } from '@/types/fields-type.ts';

export interface FilterFieldsType extends FieldsType {
  className?: string;
  variant?: string;
}

export interface FilterType {
  input: FilterFieldsType;
  selectData?: SelectDataType[];
}
