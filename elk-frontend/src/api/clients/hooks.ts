import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { clientsApi } from './api';

export const QUERY_KEYS = {
  CLIENTS: 'clients',
  CLIENTS_MODERATION: 'clientsModeration',
  ADMIN_CLIENTS: 'adminClients',
  CLIENT: 'client',
  SYSTEM_DESCRIPTION: 'systemDescription',
};

export const useClientsQuery = () => {
  return useQuery({
    queryKey: [QUERY_KEYS.CLIENTS],
    queryFn: clientsApi.getClients,
  });
};

export const useSystemDescriptionQuery = () => {
  return useQuery({
    queryKey: [QUERY_KEYS.SYSTEM_DESCRIPTION],
    queryFn: clientsApi.getSystemDescription,
  });
};

export const useClientQuery = (id?: number) => {
  return useQuery({
    queryKey: [QUERY_KEYS.CLIENT, id],
    queryFn: () => clientsApi.getClient(id),
  });
};

export const useAdminClientsQuery = () => {
  return useQuery({
    queryKey: [QUERY_KEYS.ADMIN_CLIENTS],
    queryFn: clientsApi.getAdminClients,
  });
};

export const useClientsModerationQuery = (tab: 'clients' | 'changes') => {
  return useQuery({
    queryKey: [QUERY_KEYS.CLIENTS_MODERATION, tab],
    queryFn: () => clientsApi.getClientsModeration(tab),
  });
};

export const usePatchClientMutation = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: clientsApi.patchClient,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: [QUERY_KEYS.CLIENTS_MODERATION] });
      queryClient.invalidateQueries({ queryKey: [QUERY_KEYS.CLIENTS] });
      queryClient.invalidateQueries({ queryKey: [QUERY_KEYS.ADMIN_CLIENTS] });
    },
  });
};

export const useCreateClientMutation = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: clientsApi.createClient,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: [QUERY_KEYS.CLIENTS_MODERATION] });
      queryClient.invalidateQueries({ queryKey: [QUERY_KEYS.CLIENTS] });
      queryClient.invalidateQueries({ queryKey: [QUERY_KEYS.ADMIN_CLIENTS] });
    },
  });
};

export const useDeleteClientMutation = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: clientsApi.deleteClient,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: [QUERY_KEYS.CLIENTS_MODERATION] });
      queryClient.invalidateQueries({ queryKey: [QUERY_KEYS.CLIENTS] });
      queryClient.invalidateQueries({ queryKey: [QUERY_KEYS.ADMIN_CLIENTS] });
    },
  });
};


export const useRecallClientAgreementMutation = () => {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: clientsApi.recallClientAgreement,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: [QUERY_KEYS.CLIENTS_MODERATION] });
      queryClient.invalidateQueries({ queryKey: [QUERY_KEYS.CLIENTS] });
      queryClient.invalidateQueries({ queryKey: [QUERY_KEYS.ADMIN_CLIENTS] });
    },
  });
};
