import { FieldsType } from '@/types/fields-type.ts';

export const AUTHORIZATION_OPTIONS: FieldsType[] = [
  {
    formName: 'rememberUser',
    type: 'switch',
    placeholder: 'Запоминать пользователя после авторизации',
    required: false,
  },
  {
    formName: 'permissionsWindow',
    type: 'switch',
    placeholder: 'Не показывать окно с разрешениями системы',
    required: false,
  },
];
