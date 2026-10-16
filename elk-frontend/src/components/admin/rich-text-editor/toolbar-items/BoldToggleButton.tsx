import { FC } from 'react';
import { Editor } from '@tiptap/react';
import { Toggle } from '@/components/ui-shadcn/toggle';
import BoldIcon from '@/assets/rich-text-editor/bold.svg?react';

interface BoldToggleButtonProps {
  editor: Editor | null;
}

const BoldToggleButton: FC<BoldToggleButtonProps> = ({ editor }) => (
  <Toggle
    pressed={editor?.isActive('bold')}
    onPressedChange={() => editor?.chain().focus().toggleBold().run()}
    disabled={!editor?.can().chain().focus().toggleBold().run()}
    aria-label='Жирный текст'
  >
    <BoldIcon className='w-8 h-8' />
  </Toggle>
);

export default BoldToggleButton;
