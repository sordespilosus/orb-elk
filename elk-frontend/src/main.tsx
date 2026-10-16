import { createRoot } from 'react-dom/client';
import './index.css';
import App from './app/App';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { ToastProvider } from './components/toast/ToastProvider';

export const queryClient = new QueryClient();

createRoot(document.getElementById('root')!).render(
  <ToastProvider>
    <QueryClientProvider client={queryClient}>
      <App />
    </QueryClientProvider>
  </ToastProvider>
);
