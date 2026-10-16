import * as React from 'react';
import { format } from 'date-fns';
import CalendarRageIcon from '@/assets/icon/calendar-range.svg?react';
import { DateRange } from 'react-day-picker';

import { cn } from '@/lib/utils';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui-shadcn/popover.tsx';
import { Button } from '@/components/ui-shadcn/button.tsx';
import { Calendar } from '@/components/ui-shadcn/calendar.tsx';
import { FC } from 'react';

interface DatePickerWithRangeProps extends React.HTMLAttributes<HTMLDivElement> {
  value?: DateRange | undefined;
  onChangeDate?: (value: DateRange | undefined) => void;
  placeholder?: string;
}

export const DatePickerWithRange: FC<DatePickerWithRangeProps> = ({ className, placeholder, value, onChangeDate }) => {
  return (
    <div className={cn('grid gap-2 w-full', className)}>
      <Popover>
        <PopoverTrigger asChild>
          <Button
            id='date'
            variant={'secondary'}
            className={cn(
              'justify-start text-left font-normal px-4 enabled:outline-1 data-[state=open]:ring-1 data-[state=open]:outline-none data-[state=open]:ring-ring',
              !value && 'text-muted-foreground'
            )}
          >
            <CalendarRageIcon />
            {value?.from ? (
              value.to ? (
                <>
                  {format(value.from, 'LLL dd, y')} - {format(value.to, 'LLL dd, y')}
                </>
              ) : (
                format(value.from, 'LLL dd, y')
              )
            ) : (
              <span>{placeholder}</span>
            )}
          </Button>
        </PopoverTrigger>
        <PopoverContent className='w-auto p-0' align='start'>
          <Calendar
            initialFocus
            mode='range'
            defaultMonth={value?.from}
            selected={value}
            onSelect={onChangeDate}
            numberOfMonths={2}
          />
        </PopoverContent>
      </Popover>
    </div>
  );
};
