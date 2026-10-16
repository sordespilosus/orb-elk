import { FC } from 'react';
import { Editor } from '@tiptap/react';
import { Toggle } from '@/components/ui-shadcn/toggle';
import OlIcon from '@/assets/rich-text-editor/ol.svg?react';

interface OrderedListToggleButtonProps {
  editor: Editor | null;
}

const OrderedListToggleButton: FC<OrderedListToggleButtonProps> = ({ editor }) => (
  <Toggle
    pressed={editor?.isActive('orderedList')}
    onPressedChange={() => editor?.chain().focus().toggleOrderedList().run()}
    disabled={!editor?.can().chain().focus().toggleOrderedList().run()}
    aria-label='Нумерованный список'
  >
    <OlIcon className='w-8 h-8' />
  </Toggle>
);

export default OrderedListToggleButton;
