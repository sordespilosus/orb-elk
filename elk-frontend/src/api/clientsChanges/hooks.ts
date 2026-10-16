import { clientsChangesApi } from './api';
import { useMutation } from '@tanstack/react-query';

export const useClientsChangesSetStatusMutation = () => {
  return useMutation({
    mutationFn: clientsChangesApi.setStatus,
  });
};
