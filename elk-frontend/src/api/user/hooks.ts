import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { userApi } from './api';

export const KEY = 'profile';

export const useUserProfileQuery = () => {
  return useQuery({
    queryKey: [KEY],
    queryFn: userApi.getProfile,
    retry: false,
  });
};

export const useUploadAvatarMutation = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: userApi.uploadAvatar,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: [KEY] });
    },
  });
};

export const usePatchProfileMutation = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: userApi.patchProfile,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: [KEY] });
    },
  });
};

export const useDeleteProfileMutation = () => {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: userApi.deleteProfile,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: [KEY] });
    },
  });
};
