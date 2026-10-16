import { DialogClose } from '@/components/ui-shadcn/dialog';
import Modal from '../Modal';
import { buttonVariants } from '@/components/ui-shadcn/button';
import { cn } from '@/lib/utils';
import { AvatarPicker } from '../AvatarPicker';
import { useCallback, useRef } from 'react';
import AvatarEditor from 'react-avatar-editor';

type AvatarPickModelProps = {
  children: JSX.Element;
  onAvatarEditorDataChange: (e: Blob) => void;
};

export const AvatarPickModal = ({ children, onAvatarEditorDataChange }: AvatarPickModelProps) => {
  const editorRef = useRef<AvatarEditor>(null);

  const handleOnChange = useCallback(() => {
    if (editorRef.current) {
      const canvas = editorRef.current.getImage();

      const dataUrl = canvas.toDataURL();
      fetch(dataUrl)
        .then((res) => res.blob())
        .then((blob) => {
          onAvatarEditorDataChange(blob);
        });
    }
  }, []);

  return (
    <Modal
      header='Загрузка фото'
      main={<AvatarPicker editorRef={editorRef} />}
      trigger={children}
      footerContent={
        <>
          <DialogClose className={cn(buttonVariants({ variant: 'ghost' }))}>Отмена</DialogClose>
          <DialogClose
            onClick={handleOnChange}
            className={cn(buttonVariants({ variant: 'default' }), 'bg-aggressive !m-0')}
          >
            Сохранить
          </DialogClose>
        </>
      }
    />
  );
};
