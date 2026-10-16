import { Editor } from '@tiptap/react';
import { FC } from 'react';
import {
  HeadingLevelSelect,
  BoldToggleButton,
  ItalicToggleButton,
  UnderlineToggleButton,
  BulletListToggleButton,
  OrderedListToggleButton,
  TextAlignLeftToggleButton,
  TextAlignRightToggleButton,
} from './toolbar-items';
import { Separator } from '@/components/ui-shadcn/separator';

interface EditorToolbarProps {
  editor: Editor | null;
}

const EditorToolbar: FC<EditorToolbarProps> = ({ editor }) => {
  return (
    <div className='editor-toolbar flex items-center flex-1 outline-thin rounded-xl px-4 h-[46px] gap-4'>
      <div className='relative flex-1'>
        <HeadingLevelSelect editor={editor} />
      </div>

      <Separator orientation='vertical' />

      <div className='flex gap-4'>
        <BoldToggleButton editor={editor} />
        <ItalicToggleButton editor={editor} />
        <UnderlineToggleButton editor={editor} />
      </div>

      <Separator orientation='vertical' />

      <div className='flex gap-4'>
        <BulletListToggleButton editor={editor} />
        <OrderedListToggleButton editor={editor} />
      </div>

      <Separator orientation='vertical' />

      <div className='flex gap-4'>
        <TextAlignLeftToggleButton editor={editor} />
        <TextAlignRightToggleButton editor={editor} />
      </div>
    </div>
  );
};
export default EditorToolbar;
