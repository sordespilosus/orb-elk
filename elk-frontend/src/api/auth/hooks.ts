import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { authApi } from './api';
import { KEY as USER_KEY } from '../user/hooks';

export const useLoginMutation = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: authApi.login,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: [USER_KEY] });
      queryClient.invalidateQueries({ queryKey: ['csrf-token'] });
    },
  });
};

export const useLogoutMutation = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: authApi.logout,
    onSuccess: async () => {
      await queryClient.invalidateQueries({ queryKey: ['csrf-token'] });
      await queryClient.invalidateQueries({ queryKey: [USER_KEY] });
    },
  });
};

export const useGetCsrfTokenQuery = () => {
  return useQuery({
    queryKey: ['csrf-token'],
    queryFn: authApi.getCsrf
  });
};
