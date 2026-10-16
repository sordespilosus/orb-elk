import { FieldsType } from '@/types/fields-type.ts';

export const ADDITIONAL_ATTRIBUTES: FieldsType[] = [
  {
    formName: 'needOrganization',
    type: 'switch',
    placeholder: 'Нужна авторизация как юр. лица или ИП',
    required: false,
  },
  {
    formName: 'hideSystem',
    type: 'switch',
    placeholder: 'Система скрыта',
    required: false,
  },
  {
    formName: 'loginForm',
    type: 'switch',
    placeholder: 'Открывать форму ввода логина и пароля',
    required: false,
  },
  // {
  //   formName: 'acceptedSystem',
  //   type: 'switch',
  //   placeholder: 'Подтверждённая система',
  //   required: false,
  // },
];
