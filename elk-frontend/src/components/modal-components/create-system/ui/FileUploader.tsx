import { useDropzone } from 'react-dropzone';
import { Input } from '@/components/ui-shadcn/input.tsx';
import Upload from '@/assets/icon/upload.svg?react';
import { useFormContext, useWatch } from 'react-hook-form';
import { useCallback, useEffect, useState } from 'react';

interface FileUploaderProps {
  name: string;
  title?: string;
}

const toBase64 = (file: File): Promise<string> =>
  new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.readAsDataURL(file);
    reader.onload = () => resolve(reader.result as string);
    reader.onerror = reject;
  });

export const FileUploader = ({ name, title }: FileUploaderProps) => {
  const { register, unregister, setValue, formState, getValues } = useFormContext();
  const [preview, setPreview] = useState<string | null>(() => getValues(name) || formState.defaultValues?.[name]);
  const onDrop = useCallback(
    async (droppedFiles: File[]) => {
      if (droppedFiles.length > 0) {
        console.log(droppedFiles[0]);
        const base64 = await toBase64(droppedFiles[0]);
        console.log(base64);

        setPreview(base64);
        setValue(name, base64, { shouldValidate: true });
      }
    },
    [setValue, name]
  );

  const { getRootProps, getInputProps } = useDropzone({
    onDrop,
    noClick: true,
    accept: {
      'image/png': ['.png'],
      'image/jpeg': ['.jpeg'],
      'image/jpg': ['.jpg'],
    },
  });

  useEffect(() => {
    register(name);

    return () => {
      unregister(name);
    };
  }, [register, unregister, name]);

  const resetWatcher = useWatch({ name });
  useEffect(() => {
    if (!resetWatcher) {
      setPreview(null);
    }
  }, [resetWatcher]);

  return (
    <div
      className={`relative w-48 h-48 border-2 rounded-2xl border-dashed ${(formState.errors[name]?.message as string) ? 'border-aggressive' : preview ? 'border-primary' : 'border-ghost-hover'} flex flex-col justify-center items-center gap-2 relative overflow-hidden`}
      {...getRootProps()}
    >
      {preview && <img src={preview} alt={'Превью'} className={'w-48 h-48 absolute opacity-20 object-cover'} />}
      {title && <span className={'text-primary text-sm'}>{title}</span>}
      {formState.errors['file'] && (
        <span className={'absolute bottom-[1.2rem] left-[2.25rem] text-xs text-aggressive'}>
          {formState.errors['file']?.message as string}
        </span>
      )}
      <div
        className={`w-40 rounded-lg ${(formState.errors[name]?.message as string) ? 'bg-aggressive' : 'bg-primary'} text-primary-foreground hover:bg-primary-hover relative text-center transition-colors text-base font-normal leading-none focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring`}
        tabIndex={0}
      >
        <Input type='file' id='avatar' className={'invisible absolute opacity-0 inline-block'} {...getInputProps()} />
        <label htmlFor='avatar' className={'py-13px flex justify-center items-center gap-2 cursor-pointer'}>
          <Upload />
          <span>Загрузить</span>
        </label>
      </div>
      <span className={'text-muted-foreground text-xs'}>Или перетащите файл сюда</span>
    </div>
  );
};
