import { FieldsType } from '@/types/fields-type.ts';

export const SYSTEM_NAME: FieldsType[] = [
  {
    formName: 'systemName',
    label: 'Название системы',
    type: 'input',
    placeholder: 'Напишите название системы',
    required: true,
  },
  {
    formName: 'systemDescription',
    label: 'Краткое описание системы',
    type: 'textarea',
    placeholder: 'Введите текст',
    required: true,
  },
];
