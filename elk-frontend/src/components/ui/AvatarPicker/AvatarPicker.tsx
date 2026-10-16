import { Button } from '@/components/ui-shadcn/button';
import { UploadIcon } from '@radix-ui/react-icons';
import { useDropzone } from 'react-dropzone';
import { AvatarEditor } from '../AvatarEditor';
import { cn } from '@/lib/utils';
import AvatarEdit from 'react-avatar-editor';

type AvatarPickerProps = {
  editorRef: React.RefObject<AvatarEdit>;
};

export const AvatarPicker = ({ editorRef }: AvatarPickerProps) => {
  const { getRootProps, getInputProps, acceptedFiles, isDragReject } = useDropzone({
    accept: {
      'image/jpg': [],
      'image/jpeg': [],
      'image/png': [],
      'image/web': [],
    },
    multiple: false,
    maxSize: 2 * 1024 * 1024,
  });

  return (
    <>
      {!acceptedFiles.length && (
        <div {...getRootProps()} className='overflow-hidden'>
          <input {...getInputProps()} />
          <div
            className={cn(
              'border-2 border-dashed border-[rgba(6,59,136,0.12)] rounded-lg p-4 flex items-center justify-center flex-col gap-2 aspect-[1/1]',
              isDragReject && 'border-red-400'
            )}
          >
            <p className='font-medium text-lg text-[#063B88]'>Фотография</p>
            <Button size={'md'}>
              <UploadIcon />
              Загрузить
            </Button>
            <p className='text-xs text-[#7A7A7A]'>Или перетащите файл сюда</p>
          </div>
        </div>
      )}
      {!!acceptedFiles.length && (
        <AvatarEditor editorRef={editorRef} imageObjectUrl={URL.createObjectURL(acceptedFiles[0])} />
      )}
    </>
  );
};
