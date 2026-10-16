import { FileUploader } from '@/components/modal-components/create-system/ui/FileUploader.tsx';
import { FormFieldsRenderer } from '@/components/modal-components/create-system/ui/FormFieldsRenderer.tsx';
import { UiAccordion } from '@/components/ui/UiAccordion';
import {
  SYSTEM_NAME,
  SYSTEM_REDIRECT,
  SYSTEM_MODAL_ITEMS,
} from '@/components/modal-components/create-system/constants';

export const SystemCreate = () => {
  return (
    <div className={'flex flex-col gap-6'}>
      <FileUploader title={'Логотип системы'} name={'file'} />
      <div className={'flex flex-col gap-3'}>
        <FormFieldsRenderer fields={SYSTEM_NAME} />
      </div>
      <FormFieldsRenderer fields={SYSTEM_REDIRECT} />
      <UiAccordion items={SYSTEM_MODAL_ITEMS} />
    </div>
  );
};
