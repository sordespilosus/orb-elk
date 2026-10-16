import { queryClient } from '@/main';

export const getCsrfToken = () => (queryClient.getQueryData(['csrf-token']) as any)?.csrf_token;
