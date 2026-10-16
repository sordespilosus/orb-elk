import { createContext } from 'react';

export type ToastProps = {
  title: string;
  message: string;
  variant?: 'success' | 'error';
};

type ToastContextType = {
  toast: (toast: ToastProps) => void;
};

export const ToastContext = createContext<ToastContextType>({ toast: () => {} });
