import { FormFieldsRenderer } from '@/components/modal-components/create-system/ui/FormFieldsRenderer.tsx';
// import { ESIA } from '@/components/modal-components/create-system/constants/esia.ts';
import { ADDITIONAL_ATTRIBUTES } from '@/components/modal-components/create-system/constants/additionalAttributes.ts';
import { AUTHORIZATION_OPTIONS } from '@/components/modal-components/create-system/constants/authorizationOptions.ts';
import { AccordionItemType } from '@/components/ui/UiAccordion';

// const ESIALEVELS = [{ name: 'TRUSTED' }, { name: 'STANDART' }, { name: 'NONE' }];

export const SYSTEM_MODAL_ITEMS: AccordionItemType[] = [
  // {
  //   className: 'data-[state=closed]:border-b data-[state=open]:border-b-0',
  //   classElement: 'flex flex-col gap-3',
  //   label: 'Адреса системы',
  //   element: <FormFieldsRenderer fields={SYSTEM_ADDRESS} />,
  // },
  // {
  //   className: 'data-[state=closed]:border-b data-[state=open]:border-b-0',
  //   label: 'ЕСИА',
  //   element: <FormFieldsRenderer fields={ESIA} selectData={ESIALEVELS} />,
  // },
  {
    className: 'data-[state=closed]:border-b data-[state=open]:border-b-0',
    label: 'Дополнительные атрибуты',
    element: <FormFieldsRenderer fields={ADDITIONAL_ATTRIBUTES} />,
  },
  {
    className: 'data-[state=closed]:border-b data-[state=open]:border-b-0',
    label: 'Опции авторизации',
    element: <FormFieldsRenderer fields={AUTHORIZATION_OPTIONS} />,
  },
  // {
  //   className: 'data-[state=closed]:border-b data-[state=open]:border-b-0',
  //   label: 'Разрешения для системы',
  //   element: <FormFieldsRenderer fields={SYSTEM_PERMISSIONS} />,
  // },
  // {
  //   className: 'data-[state=closed]:border-b data-[state=open]:border-b-0',
  //   label: 'Методы входа',
  //   element: <FormFieldsRenderer fields={ENTRY_METHODS} />,
  // },
  // {
  //   className: 'border-b-0',
  //   label: 'Пользовательское соглашение',
  //   element: <FormFieldsRenderer fields={USER_AGREEMENT} />,
  // },
];
