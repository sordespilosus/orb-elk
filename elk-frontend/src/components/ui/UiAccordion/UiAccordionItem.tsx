import { AccordionContent, AccordionItem, AccordionTrigger } from '@/components/ui-shadcn/accordion.tsx';
import { FunctionComponent } from 'react';
import { AccordionItemType } from './type.ts';
import { cn } from '@/lib/utils.ts';

export const UiAccordionItem: FunctionComponent<AccordionItemType> = ({ label, element, className, classElement }) => {
  return (
    <AccordionItem
      value={`item-${label}`}
      className={cn(className || 'data-[state=closed]:border-b data-[state=open]:border-b-0', 'last:!border-b-0')}
    >
      <AccordionTrigger className={'text-accent-foreground-header font-medium text-base h-[60px]'}>
        {label}
      </AccordionTrigger>
      <AccordionContent className={classElement}>{element}</AccordionContent>
    </AccordionItem>
  );
};
