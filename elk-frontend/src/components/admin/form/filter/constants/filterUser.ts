import { FilterType } from '@/components/admin/form/filter/types/types.ts';
import { inputVariants } from '@/components/ui-shadcn/input.tsx';

const inputVariant = inputVariants({ inputSize: 'm' });
const booleanFilterOptions = [
  { name: 'Все', value: 'all' },
  { name: 'Да', value: 'true' },
  { name: 'Нет', value: 'false' },
];

export const FILTER_USER: FilterType[] = [
  {
    input: {
      formName: 'surname',
      type: 'input',
      placeholder: 'Введите фамилию',
      required: false,
      label: 'Фамилия',
      className: 'mb-3',
      variant: inputVariant,
    },
  },
  {
    input: {
      formName: 'name',
      type: 'input',
      placeholder: 'Введите имя',
      required: false,
      label: 'Имя',
      className: 'mb-6 mr-2 !col-span-1',
      variant: inputVariant,
    },
  },
  {
    input: {
      formName: 'patronymic',
      type: 'input',
      placeholder: 'Введите отчество',
      required: false,
      label: 'Отчество',
      className: 'mb-6 !col-span-1',
      variant: inputVariant,
    },
  },
  {
    input: {
      formName: 'id',
      type: 'input',
      placeholder: 'Идентификатор',
      required: false,
      label: 'Идентификатор',
      className: 'mb-6',
      variant: inputVariant,
    },
  },
  {
    input: {
      formName: 'settlement',
      type: 'select',
      placeholder: 'Выберете населённый пункт',
      required: false,
      label: 'Населённый пункт',
      className: 'mb-6',
    },
  },
  {
    input: {
      formName: 'minAge',
      type: 'input',
      placeholder: 'От',
      inputType: 'number',
      required: false,
      label: 'Min граница возраста',
      className: 'mb-4 mr-2 !col-span-1',
      variant: inputVariant,
    },
  },
  {
    input: {
      formName: 'maxAge',
      type: 'input',
      placeholder: 'До',
      inputType: 'number',
      required: false,
      label: 'Max граница возраста',
      className: 'mb-6 !col-span-1',
      variant: inputVariant,
    },
  },
  {
    input: {
      formName: 'gender',
      type: 'select',
      placeholder: 'Все',
      required: false,
      label: 'Пол',
      className: 'mb-6 mr-2 !col-span-1',
    },
    selectData: [
      { name: 'Все', value: 'all' },
      { name: 'Мужской', value: 'male' },
      { name: 'Женский', value: 'female' },
    ],
  },
  {
    input: {
      formName: 'isAdmin',
      type: 'select',
      placeholder: 'Все',
      required: false,
      label: 'Админ',
      className: 'mb-6 !col-span-1',
    },
    selectData: booleanFilterOptions,
  },
  {
    input: {
      formName: 'isBlocked',
      type: 'select',
      placeholder: 'Все',
      required: false,
      label: 'Заблокирован',
      className: 'mr-2 !col-span-1',
    },
    selectData: booleanFilterOptions,
  },
  {
    input: {
      formName: 'isConfirmed',
      type: 'select',
      placeholder: 'Все',
      required: false,
      label: 'Подтверждён',
      className: 'mr-2 !col-span-1',
    },
    selectData: booleanFilterOptions,
  },
];
