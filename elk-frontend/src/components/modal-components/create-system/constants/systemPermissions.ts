import { FieldsType } from '@/types/fields-type.ts';

export const SYSTEM_PERMISSIONS: FieldsType[] = [
  {
    formName: 'accessUserIdGos',
    type: 'switch',
    placeholder: 'Доступ к ID пользователя в Госуслугах',
    required: false,
  },
  {
    formName: 'accessUserIdGosBinding',
    type: 'switch',
    placeholder: 'Обязательность данных',
    required: false,
  },
  {
    formName: 'accessByUserOrganizations',
    type: 'switch',
    placeholder: 'Доступ организациям пользователя',
    required: false,
  },
  {
    formName: 'accessByUserOrganizationsBinding',
    type: 'switch',
    placeholder: 'accessByUserOrganizationsBinding',
    required: false,
  },
  {
    formName: 'accessToPassportData',
    type: 'switch',
    placeholder: 'Доступ к паспортным данным',
    required: false,
  },
  {
    formName: 'accessToPassportDataBinding',
    type: 'switch',
    placeholder: 'Обязательность данных',
    required: false,
  },
  {
    formName: 'accessToPersonalInfo',
    type: 'switch',
    placeholder: 'Доступ к персональной информации (ФИО, дата рождения, городу проживания, логину)',
    required: false,
  },
  {
    formName: 'accessToPersonalInfoBinding',
    type: 'switch',
    placeholder: 'Обязательность данных',
    required: false,
  },
];
