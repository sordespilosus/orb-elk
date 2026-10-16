import { FC } from 'react';
import { Editor } from '@tiptap/react';
import { Toggle } from '@/components/ui-shadcn/toggle';
import UnderlineIcon from '@/assets/rich-text-editor/underline.svg?react';

interface UnderlineToggleButtonProps {
  editor: Editor | null;
}

const UnderlineToggleButton: FC<UnderlineToggleButtonProps> = ({ editor }) => (
  <Toggle
    pressed={editor?.isActive('underline')}
    onPressedChange={() => editor?.chain().focus().toggleUnderline().run()}
    disabled={!editor?.can().chain().focus().toggleUnderline().run()}
    aria-label='Подчеркнутый текст'
  >
    <UnderlineIcon className='w-8 h-8' />
  </Toggle>
);

export default UnderlineToggleButton;
