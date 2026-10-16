import { Button } from '@/components/ui-shadcn/button';
import { MinusIcon, PlusIcon } from '@radix-ui/react-icons';
import { memo, useEffect, useState } from 'react';
import AvatarEdit from 'react-avatar-editor';

type AvatarEditorProps = {
  imageObjectUrl: string;
  editorRef: React.RefObject<AvatarEdit>;
};

export const AvatarEditor = memo(({ imageObjectUrl, editorRef }: AvatarEditorProps) => {
  const [crop, setCropp] = useState(1);

  useEffect(() => {
    if (crop < 1) {
      setCropp(1);
    }
  }, [crop]);

  // кроп при нажатии на кнопки
  const handleCropp = (action: 'increase' | 'decrease') => {
    setCropp((prevCropp) => {
      if (action === 'increase') {
        return Math.min(prevCropp + 0.1, 2); // Max scale limit
      } else {
        return Math.max(prevCropp - 0.1, 0.7); // Min scale limit
      }
    });
  };

  return (
    <div className='flex flex-col gap-4 justify-center items-center'>
      <AvatarEdit
        ref={editorRef}
        image={imageObjectUrl}
        width={200}
        height={200}
        backgroundColor='#000'
        borderRadius={1000}
        border={40}
        color={[0, 0, 0, 0.25]} // RGBA
        scale={crop}
        rotate={0}
        style={{
          borderRadius: '8px',
        }}
      />
      <div className='flex gap-2 items-center'>
        <Button onClick={() => handleCropp('decrease')} variant={'ghost'} className='aspect-[1/1] p-[13px]'>
          <MinusIcon fontSize={13} />
        </Button>
        <Button onClick={() => handleCropp('increase')} variant={'ghost'} className='aspect-[1/1] p-[13px]'>
          <PlusIcon fontSize={13} />
        </Button>
      </div>
    </div>
  );
});
