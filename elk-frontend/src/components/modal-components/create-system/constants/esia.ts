import { FieldsType } from '@/types/fields-type.ts';

export const ESIA: FieldsType[] = [
  {
    formName: 'requiredESIA',
    type: 'switch',
    placeholder: 'Необходима ЕСИА',
    required: false,
  },
  // {
  //   formName: 'accountLevelESIA',
  //   label: 'Уровень учётных записей ЕСИА',
  //   type: 'select',
  //   placeholder: 'Выберете уровень учётных записей ЕСИА',
  //   required: false,
  // },
];
