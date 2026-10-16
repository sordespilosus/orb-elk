import { FC } from 'react';
import { Editor } from '@tiptap/react';
import { Toggle } from '@/components/ui-shadcn/toggle';
import LeftIcon from '@/assets/rich-text-editor/left.svg?react';

interface TextAlignLeftToggleButtonProps {
  editor: Editor | null;
}

const TextAlignLeftToggleButton: FC<TextAlignLeftToggleButtonProps> = ({ editor }) => (
  <Toggle
    pressed={editor?.isActive({ textAlign: 'left' })}
    onPressedChange={() => editor?.chain().focus().setTextAlign('left').run()}
    aria-label='Выровнять текст влево'
  >
    <LeftIcon className='w-8 h-8' />
  </Toggle>
);

export default TextAlignLeftToggleButton;
