import { FieldsType } from '@/types/fields-type.ts';

export const SYSTEM_ADDRESS: FieldsType[] = [
  {
    formName: 'systemAddress',
    label: 'Адрес системы',
    type: 'input',
    placeholder: 'Введите текст',
    required: true,
  },
  {
    formName: 'systemEnterUrl',
    label: 'Адрес входа в систему',
    type: 'input',
    placeholder: 'Введите текст',
    required: false,
  },
  {
    formName: 'systemNotificationUrl',
    label: 'Адрес получения уведомлений',
    type: 'input',
    placeholder: 'Введите текст',
    required: false,
  },
];
