import { FC } from 'react';
import { Editor } from '@tiptap/react';
import { Toggle } from '@/components/ui-shadcn/toggle';
import ItalicIcon from '@/assets/rich-text-editor/italic.svg?react';

interface ItalicToggleButtonProps {
  editor: Editor | null;
}

const ItalicToggleButton: FC<ItalicToggleButtonProps> = ({ editor }) => (
  <Toggle
    pressed={editor?.isActive('italic')}
    onPressedChange={() => editor?.chain().focus().toggleItalic().run()}
    disabled={!editor?.can().chain().focus().toggleItalic().run()}
    aria-label='Курсив'
  >
    <ItalicIcon className='w-8 h-8' />
  </Toggle>
);

export default ItalicToggleButton;
