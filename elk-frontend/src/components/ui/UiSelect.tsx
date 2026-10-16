import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui-shadcn/select';
import { cn } from '@/lib/utils.ts';
import { SelectDataType } from '@/types/common.ts';
import { Input } from '@/components/ui-shadcn/input.tsx';

type CustomSelectProps<D> = {
  data: D[];
  className?: string;
  placeholder: string;
  value?: string;
  onChange: (value: string) => void;
  defaultValue?: string;
  onChangeSelectInput?: (value: string) => void;
};

export const UiSelect = <D extends SelectDataType>({
  data,
  className,
  placeholder,
  value,
  onChange,
  defaultValue,
  onChangeSelectInput,
}: CustomSelectProps<D>) => {
  return (
    <Select value={value} onValueChange={onChange} defaultValue={defaultValue}>
      <SelectTrigger
        className={cn(
          `w-full py-[4px] px-4 border-input rounded-xl shadow-sm text-base border text-muted-foreground-placeholder`,
          className
        )}
      >
        <SelectValue placeholder={placeholder} />
      </SelectTrigger>
      <SelectContent>
        {onChangeSelectInput && (
          <Input onChange={(e) => onChangeSelectInput(e.target.value)} className={'mb-4'} inputSize={'m'} />
        )}
        {data.map((i, index) => (
          <SelectItem key={index} value={i.value || 'Empty'}>
            {i.name}
          </SelectItem>
        ))}
      </SelectContent>
    </Select>
  );
};
