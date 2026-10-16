import { UiAccordionItem } from './UiAccordionItem.tsx';
import { Accordion } from '@/components/ui-shadcn/accordion.tsx';
import { AccordionItemType } from './type.ts';

type UiAccordionListProps = {
  items?: AccordionItemType[];
  children?: React.ReactNode;
};

export const UiAccordionList = ({ items, children }: UiAccordionListProps) => {
  return (
    <Accordion type='multiple'>
      {items?.map((i) => (
        <UiAccordionItem
          key={i.label}
          className={i.className}
          classElement={i.classElement}
          label={i.label}
          element={i.element}
        />
      ))}
      {children}
    </Accordion>
  );
};
