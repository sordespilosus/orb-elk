import { useMutation, useQuery } from '@tanstack/react-query';

import { useQueryClient } from '@tanstack/react-query';
import { adminApi } from './api';
import { FetchLogsParams } from './types';

export const useLogsQuery = (params?: FetchLogsParams) => {
  return useQuery({
    queryKey: ['logs', params],
    queryFn: () => adminApi.getLogs(params),
  });
};

export const useGetAgreementQuery = () => {
  return useQuery({
    queryKey: ['agreement'],
    queryFn: () => adminApi.getAgreement(),
  });
};

export const useSetAgreementMutation = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: adminApi.setAgreement,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['agreement'] });
    },
  });
};

export const useSystemDescriptionQuery = () => {
  return useQuery({
    queryKey: ['system_description'],
    queryFn: adminApi.getSystemDescription,
  });
};

export const useSetDescriptionMutation = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: adminApi.setDescription,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['system_description'] });
    },
  });
};
