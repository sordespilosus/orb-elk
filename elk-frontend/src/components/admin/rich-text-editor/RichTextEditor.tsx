import BulletList from '@tiptap/extension-bullet-list';
import Heading from '@tiptap/extension-heading';
import OrderedList from '@tiptap/extension-ordered-list';
import TextAlign from '@tiptap/extension-text-align';
import Underline from '@tiptap/extension-underline';
import Placeholder from '@tiptap/extension-placeholder';
import { EditorContent, useEditor } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import { FC, useEffect } from 'react';

import { Button } from '@/components/ui-shadcn/button';
import EditorToolbar from './EditorToolbar';

interface RichTextEditorProps {
  initialContent?: string;
  editorKey: string;
  onSave?: (html: string) => void;
  isForm?: boolean;
  onChange?: (html: string) => void;
  placeholder?: string;
  autofocus?: boolean;
}

const RichTextEditor: FC<RichTextEditorProps> = ({
  initialContent = '',
  onSave,
  onChange,
  isForm,
  placeholder,
  autofocus = true,
}) => {
  const editor = useEditor({
    extensions: [
      StarterKit,
      Underline,
      TextAlign.configure({ types: ['heading', 'paragraph'] }),
      Heading.configure({ levels: [1, 2, 3] }),
      Placeholder.configure({
        placeholder: 'placeholder',
      }),
      BulletList,
      OrderedList,
    ],
    autofocus: autofocus,
    content: initialContent,
    onUpdate: ({ editor }) => onChange?.(editor.getHTML()),
  });

  useEffect(() => {
    if (initialContent === '' && !editor?.isEmpty) editor?.commands.clearContent();
    if (initialContent !== '' && editor?.isEmpty) editor?.commands.setContent(initialContent);
  }, [initialContent]);

  function saveHandler() {
    if (!editor) return;
    const html = editor.getHTML();
    onSave?.(html);
  }

  return (
    <div>
      <div className='flex flex-col gap-4'>
        <div className='flex items-center gap-4'>
          <EditorToolbar editor={editor} />
          {!isForm && <Button onClick={saveHandler}>Сохранить</Button>}
        </div>
        <EditorContent
          placeholder={placeholder}
          editor={editor}
          className='prose outline-thin rounded-2xl p-4 placeholder:text-basic-black-opacity text-basic-black text-base leading-5 w-full max-w-none [&_p]:m-0 min-h-[100px]'
        />
      </div>
    </div>
  );
};

export default RichTextEditor;
