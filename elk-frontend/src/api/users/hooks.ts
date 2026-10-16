import { usersApi } from './api';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { GetUsersParams } from './types';

export const useUsers = (params: GetUsersParams) => {
  return useQuery({
    queryKey: ['users', params],
    queryFn: () => usersApi.getUsers(params),
  });
};

export const useUser = (user_id: number) => {
  return useQuery({
    queryKey: ['user', user_id],
    queryFn: () => usersApi.getUser(user_id),
    enabled: Number.isFinite(user_id),
  });
};

export const useUserRoles = (user_id: number) => {
  return useQuery({
    queryKey: ['user-roles', user_id],
    queryFn: () => usersApi.getUserRoles(user_id),
  });
};

export const useBlockUserMutation = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: usersApi.blockUser,
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: ['users'] });
      queryClient.invalidateQueries({ queryKey: ['user-roles', variables.user_id] });
    },
  });
};

export const useRestoreUserMutation = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: usersApi.restoreUser,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['users'] });
    },
  });
};

export const useUpdateUserMutation = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: usersApi.updateUser,
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: ['users'] });
      queryClient.invalidateQueries({ queryKey: ['user', variables.user_id] });
      queryClient.invalidateQueries({ queryKey: ['user-roles', variables.user_id] });
    },
  });
};

export const useSetUserRolesMutation = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: usersApi.setUserRoles,
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: ['users'] });
      queryClient.invalidateQueries({ queryKey: ['user', variables.user_id] });
      queryClient.invalidateQueries({ queryKey: ['user-roles', variables.user_id] });
    },
  });
};

export const useDeleteUserRoleMutation = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: usersApi.deleteUserRole,
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: ['users'] });
      queryClient.invalidateQueries({ queryKey: ['user-roles', variables.user_id] });
    },
  });
};
