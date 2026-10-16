import { FC } from 'react';
import { Editor } from '@tiptap/react';
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui-shadcn/select';

interface HeadingLevelSelectProps {
  editor: Editor | null;
}

const HeadingLevelSelect: FC<HeadingLevelSelectProps> = ({ editor }) => (
  <Select
    value={(() => {
      if (editor?.isActive('heading', { level: 1 })) return '1';
      if (editor?.isActive('heading', { level: 2 })) return '2';
      if (editor?.isActive('heading', { level: 3 })) return '3';
      return '0';
    })()}
    onValueChange={(value) => {
      if (value === '0') {
        editor?.chain().focus().setParagraph().run();
      } else {
        editor
          ?.chain()
          .focus()
          .toggleHeading({ level: parseInt(value) as 1 | 2 | 3 })
          .run();
      }
    }}
  >
    <SelectTrigger>
      <SelectValue placeholder='Выберите уровень заголовка' />
    </SelectTrigger>
    <SelectContent>
      <SelectGroup>
        <SelectItem value='0'>Нормальный</SelectItem>
        <SelectItem value='1'>Заголовок 1</SelectItem>
        <SelectItem value='2'>Заголовок 2</SelectItem>
        <SelectItem value='3'>Заголовок 3</SelectItem>
      </SelectGroup>
    </SelectContent>
  </Select>
);

export default HeadingLevelSelect;
