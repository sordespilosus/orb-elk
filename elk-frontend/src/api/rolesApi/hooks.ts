import { useQuery } from '@tanstack/react-query';
import { rolesApi } from './api';
import { RolesParams } from './types';

export const useRolesQuery = (roleParams: RolesParams) => {
  return useQuery({
    queryKey: ['roles', roleParams],
    queryFn: () => rolesApi.getRoles(roleParams),
  });
};
