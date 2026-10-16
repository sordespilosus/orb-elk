import { FunctionComponent, HTMLAttributes } from 'react';
import { cva, type VariantProps } from 'class-variance-authority';
import { cn } from '@/lib/utils.ts';

const cardVariants = cva('bg-card rounded-4xl', {
  variants: {
    size: {
      s: 'p-2',
      m: 'p-8',
    },
  },
  defaultVariants: {
    size: 'm',
  },
});

interface CardProps extends HTMLAttributes<HTMLDivElement>, VariantProps<typeof cardVariants> {}

const Card: FunctionComponent<CardProps> = ({ size, className, ...props }) => {
  return <div {...props} className={cn(cardVariants({ size, className }))} />;
};

export default Card;
