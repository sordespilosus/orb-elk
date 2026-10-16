import { FieldsType } from '@/types/fields-type.ts';

export const SYSTEM_REDIRECT: FieldsType[] = [
  {
    formName: 'systemRedirectUrl',
    label: 'Адрес редиректа системы',
    type: 'input',
    placeholder: 'Введите текст',
    required: true,
  },
];
