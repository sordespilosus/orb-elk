import { Accordion } from '../../ui-shadcn/accordion.tsx';
import { UiAccordionList } from './UiAccordionList.tsx';
import { AccordionItemType } from './type.ts';

type UiAccordionProps = {
  items?: AccordionItemType[];
  children?: React.ReactNode;
};

export const UiAccordion = ({ items, children }: UiAccordionProps) => {
  return (
    <Accordion type='single' collapsible>
      <UiAccordionList items={items}>{children}</UiAccordionList>
    </Accordion>
  );
};
