import { getCsrfToken } from '@/utils/api';
import { SetStatusParams } from './types';
import { $url } from '@/utils/config';
export const clientsModerationApi = {
  setStatus: async (
    params: SetStatusParams
  ): Promise<{
    status: boolean;
  }> => {
    try {
      const query = new URLSearchParams({
        client_id: params.client_id.toString(),
        status: params.status,
        reasons: params.reasons ?? '',
        ord: params.ord?.toString() ?? '',
      });
      const response = await fetch(`${$url}/admin/api/clients_moderation/set_status?${query.toString()}`, {
        method: 'POST',
        body: JSON.stringify({
          _token: getCsrfToken(),
        }),
        headers: {
          'Content-Type': 'application/json',
          'X-CSRFToken': getCsrfToken(),
        },
      });

      if (!response.ok) throw new Error('Ошибка при установке статуса');

      const responseData = await response.json();

      if (!responseData.status) {
        if (responseData.errors.reasons) {
          throw new Error('Поле "Причина" обязательно для заполнения');
        }
        throw new Error('Ошибка при установке статуса');
      }

      return responseData;
    } catch (error) {
      console.error('setStatus: Unexpected error:', error);
      throw error;
    }
  },
};
