import { RolesParams, RolesResponce } from './types';
import { getCsrfToken } from '@/utils/api';
import { $url } from '@/utils/config';
export const rolesApi = {
  getRoles: async ({ order_by = 'id', is_asc = true }: RolesParams): Promise<RolesResponce> => {
    try {
      const searchParams = new URLSearchParams({ order_by, is_asc: is_asc.toString() });
      const res = await fetch(`${$url}/admin/api/roles?${searchParams.toString()}`, {
        headers: {
          'X-CSRFToken': getCsrfToken(),
        },
      });
      if (!res.ok) throw new Error('Ошибка при получении списка ролей в системе');
      const response = await res.json();
      if (!response.status) throw new Error('Ошибка при получении списка ролей в системе');

      return response;
    } catch (error) {
      console.error('getRoles: Unexpected error:', error);
      throw error;
    }
  },
};
