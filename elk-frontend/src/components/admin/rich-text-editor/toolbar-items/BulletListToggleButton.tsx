import { FC } from 'react';
import { Editor } from '@tiptap/react';
import { Toggle } from '@/components/ui-shadcn/toggle';
import UlIcon from '@/assets/rich-text-editor/ul.svg?react';

interface BulletListToggleButtonProps {
  editor: Editor | null;
}

const BulletListToggleButton: FC<BulletListToggleButtonProps> = ({ editor }) => (
  <Toggle
    pressed={editor?.isActive('bulletList')}
    onPressedChange={() => editor?.chain().focus().toggleBulletList().run()}
    disabled={!editor?.can().chain().focus().toggleBulletList().run()}
    aria-label='Маркированный список'
  >
    <UlIcon className='w-8 h-8' />
  </Toggle>
);

export default BulletListToggleButton;
