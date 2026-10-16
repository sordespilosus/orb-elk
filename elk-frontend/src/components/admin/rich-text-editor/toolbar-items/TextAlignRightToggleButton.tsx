import { FC } from 'react';
import { Editor } from '@tiptap/react';
import { Toggle } from '@/components/ui-shadcn/toggle';
import RightIcon from '@/assets/rich-text-editor/right.svg?react';

interface TextAlignRightToggleButtonProps {
  editor: Editor | null;
}

const TextAlignRightToggleButton: FC<TextAlignRightToggleButtonProps> = ({ editor }) => (
  <Toggle
    pressed={editor?.isActive({ textAlign: 'right' })}
    onPressedChange={() => editor?.chain().focus().setTextAlign('right').run()}
    aria-label='Выровнять текст вправо'
  >
    <RightIcon className='w-8 h-8' />
  </Toggle>
);

export default TextAlignRightToggleButton;
