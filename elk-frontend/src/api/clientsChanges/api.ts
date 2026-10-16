import { getCsrfToken } from '@/utils/api';
import { SetStatusBody } from './types';
import { $url } from '@/utils/config';

export const clientsChangesApi = {
  setStatus: async (
    body: SetStatusBody
  ): Promise<{
    status: boolean;
  }> => {
    try {
      const response = await fetch(`${$url}/admin/api/clients_changes/set_status`, {
        method: 'POST',
        body: JSON.stringify({
          ...body,
          _token: getCsrfToken(),
        }),
        headers: {
          'Content-Type': 'application/json',
          'X-CSRFToken': getCsrfToken(),
        },
      });
      if (!response.ok) throw new Error('Ошибка при установке статуса');
      const responseData = await response.json();
      if (!responseData.status) throw new Error('Ошибка при установке статуса');
      return responseData;
    } catch (error) {
      console.error('setStatus: Unexpected error:', error);
      throw error;
    }
  },
};
