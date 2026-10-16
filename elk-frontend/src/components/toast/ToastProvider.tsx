import { cn } from '@/lib/utils';
import './ToastProvider.css';
import { ToastContext } from './ToastContext';
import { useState, useCallback } from 'react';
import { CSSTransition, TransitionGroup } from 'react-transition-group';
import { ToastProps } from './ToastContext';
import ErrorIcon from '@/assets/icon/errorIcon.svg?react';
import SuccessIcon from '@/assets/icon/sucsessIcon.svg?react';
export const ToastProvider = ({ children }: { children: React.ReactNode }) => {
  const [toasts, setToasts] = useState<(ToastProps & { id: string })[]>([]);
  const toast = useCallback(({ message, variant = 'success', title }: ToastProps) => {
    setToasts((prev) => [
      ...prev,
      { message, variant, title, id: Date.now().toString() },
    ]);
    setTimeout(() => {
      setToasts((prev) => prev.slice(1));
    }, 3000);
  }, []);
  return (
    <ToastContext.Provider value={{ toast }}>
      <TransitionGroup className='fixed right-4 bottom-4 z-[99999] flex flex-col items-end'>
        {toasts.map((toast) => (
          <CSSTransition key={toast.id} timeout={500} classNames='toast'>
            <div
              className={cn(
                'min-w-56 text-white p-4 rounded mb-2',
                toast.variant === 'error' && 'bg-[#F14357]',
                toast.variant === 'success' && 'bg-[#3FCA92]'
              )}
            >
              <div className='flex items-center gap-2'>
                {toast.variant === 'error' && <ErrorIcon />}
                {toast.variant === 'success' && <SuccessIcon />}
                {toast.title && <h3 className='font-bold'>{toast.title}</h3>}
              </div>
              {toast.message}
            </div>
          </CSSTransition>
        ))}
      </TransitionGroup>
      {children}
    </ToastContext.Provider>
  );
};
