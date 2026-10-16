import { FieldsType } from '@/types/fields-type.ts';

export const ENTRY_METHODS: FieldsType[] = [
  {
    formName: 'loginWithUsernameAndPassword',
    type: 'switch',
    placeholder: 'Вход через логин и пароль',
    required: false,
  },
  {
    formName: 'loginViaGosUslugi',
    type: 'switch',
    placeholder: 'Вход через Госуслуги',
    required: false,
  },
  {
    formName: 'loginVK',
    type: 'switch',
    placeholder: 'Вход через ВКонтакте',
    required: false,
  },
];
