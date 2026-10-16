import { Control, FieldValues, Path } from 'react-hook-form';
import { FormControl, FormField, FormItem } from '@/components/ui-shadcn/form.tsx';
import { cn } from '@/lib/utils.ts';
import RichTextEditor from '@/components/admin/rich-text-editor/RichTextEditor.tsx';

type FormRichTextEditorProps<T extends FieldValues> = {
  control: Control<T>;
  name: Path<T>;
  label?: string;
  placeholder?: string;
  required?: boolean;
};

export const FormRichTextEditor = <T extends FieldValues>({
  control,
  name,
  label,
  placeholder,
  required = false,
}: FormRichTextEditorProps<T>) => {
  return (
    <FormField
      control={control}
      name={name}
      render={({ field, fieldState }) => (
        <FormItem className={cn('space-y-0')}>
          <p className='text-xs text-muted-foreground ml-3 leading-none mb-1'>
            {label} {required && <span className='text-aggressive'>*</span>}
          </p>
          <FormControl>
            <RichTextEditor
              placeholder={placeholder}
              editorKey={name}
              initialContent={field.value || ''}
              isForm
              onChange={(html) => field.onChange(html)}
              autofocus={false}
            />
          </FormControl>
          <p className='text-xs text-destructive ml-3'>{fieldState.error?.message}</p>
        </FormItem>
      )}
    />
  );
};
