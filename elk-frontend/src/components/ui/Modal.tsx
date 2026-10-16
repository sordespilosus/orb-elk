import {
  Dialog,
  DialogClose,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui-shadcn/dialog';
import { ReactElement } from 'react';
import CancelIcon from '@/assets/icon/cancel.svg?react';
import { Button, buttonVariants } from '../ui-shadcn/button';
import { cn } from '@/lib/utils';

interface ModalProps {
  leftIconHeader?: string;
  header?: string;
  trigger?: JSX.Element;
  main: ReactElement;
  footerContent?: JSX.Element;
  className?: string;
  open?: boolean;
  onClose?: () => void;
  onSubmit?: () => void;
  actionBtnText?: string;
  disabled?: boolean;
}

const Modal = ({
  leftIconHeader,
  header,
  main,
  footerContent,
  trigger,
  className,
  open,
  onClose,
  onSubmit,
  actionBtnText,
  disabled,
}: ModalProps) => (
  <Dialog
    open={open}
    onOpenChange={(isOpen) => {
      if (!isOpen && onClose) {
        onClose();
      }
    }}
  >
    <DialogTrigger asChild>{trigger}</DialogTrigger>
    <DialogContent
      className={`bg-accent overflow-y-auto !rounded-t-3xl mobile:!rounded-b-3xl mobile:!max-w-modal gap-0 p-0 border-none max-mobile:bottom-0 max-mobile:top-auto max-mobile:translate-y-0 max-h-[91vh] rich-text-display ${className}`}
    >
      <DialogHeader>
        <div className='flex border-b bg-modal-foreground sticky top-0 justify-between items-center gap-8 p-8'>
          {leftIconHeader && (
            <div className=''>
              <img src={leftIconHeader} width={32} height={32} alt='' />
            </div>
          )}
          <DialogTitle className='text-accent-foreground-header font-medium text-xl leading-6 tracking-tight text-left'>
            {header}
          </DialogTitle>
          <DialogClose className={cn(buttonVariants({ variant: 'ghost', size: 'icon' }))}>
            <CancelIcon />
          </DialogClose>
        </div>
      </DialogHeader>
      <div className='px-8 !m-0 py-4 text-standard text-xl leading-6 tracking-tight text-left'>{main}</div>
      <DialogFooter className='grid grid-flow-col auto-cols-fr gap-2 p-8 border-t'>
        {footerContent ? (
          footerContent
        ) : (
          <>
            <DialogClose className={cn(buttonVariants({ variant: 'ghost' }))}>Отмена</DialogClose>
            <Button onClick={onSubmit} className={cn(buttonVariants({ variant: 'default' }))} disabled={disabled}>
              {actionBtnText ? actionBtnText : 'Сохранить'}
            </Button>
          </>
        )}
      </DialogFooter>
    </DialogContent>
  </Dialog>
);

export default Modal;
