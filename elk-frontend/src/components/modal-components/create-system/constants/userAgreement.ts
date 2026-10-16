import { FieldsType } from '@/types/fields-type.ts';

export const USER_AGREEMENT: FieldsType[] = [
  {
    formName: 'userAgreementSystem',
    label: 'Пользовательское соглашение системы',
    type: 'textarea',
    placeholder: 'Введите текст',
    required: false,
  },
];
