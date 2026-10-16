import { useMutation } from '@tanstack/react-query';
import { clientsModerationApi } from './api';

export const useClientsModerationSetStatusMutation = () => {
  return useMutation({
    mutationFn: clientsModerationApi.setStatus,
  });
};
