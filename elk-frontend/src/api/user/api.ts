import { getCsrfToken } from '@/utils/api';
import { ProfileData } from './types';
import { $url } from '@/utils/config';
export const userApi = {
  getProfile: async (): Promise<ProfileData> => {
    try {
      const response = await fetch(`${$url}/api/v2/profile`, {
        headers: {
          'X-CSRFToken': getCsrfToken(),
        },
      });

      if (response.status >= 300 && response.status < 400) {
        throw new Error('Не авторизирован (редирект)');
      }

      if (!response.ok) throw new Error('Ошибка при получении профиля');

      const responseData = await response.json();

      if (!responseData.status) {
        throw new Error('Ошибка при получении профиля');
      }
      return responseData.data;
    } catch (error) {
      console.error('getProfile: Unexpected error:', error);
      throw error;
    }
  },
  uploadAvatar: async (
    avatar: Blob | File
  ): Promise<{
    file_name: string;
    status: string;
  }> => {
    const formData = new FormData(); 
    formData.append('_token', getCsrfToken());
    formData.append('file', avatar, 'avatar' + '.' + avatar.type.split('/')[1]);

    try {
      const response = await fetch(`${$url}/profile/send_image`, {
        method: 'POST',
        body: formData,
        headers: {
          'X-CSRFToken': getCsrfToken(),
        },
      });
      if (!response.ok) throw new Error('Ошибка при загрузке аватара');
      const responseData = await response.json();
      if (!responseData.status) throw new Error('Ошибка при загрузке аватара');
      return responseData;
    } catch (error) {
      console.error('uploadAvatar: Unexpected error:', error);
      throw error;
    }
  },
  patchProfile: async (body: Partial<ProfileData>): Promise<{ status: boolean }> => {
    try {
      const response = await fetch(`${$url}/profile`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRFToken': getCsrfToken(),
        },
        body: JSON.stringify({
          ...body,
          _token: getCsrfToken(),
        }),
      });
      if (!response.ok) throw new Error('Недопустимый формат файла');
      const responseData = await response.json();
      if (!responseData.status) throw new Error('Недопустимый формат файла');
      return responseData;
    } catch (error) {
      console.error('patchProfile: Unexpected error:', error);
      throw error;
    }
  },
  deleteProfile: async (): Promise<{ status: boolean }> => {
    try {
      const response = await fetch(`${$url}/api/v2/delete_profile`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRFToken': getCsrfToken(),
        },
        body: JSON.stringify({
          _token: getCsrfToken(),
        }),
      });
      if (!response.ok) throw new Error('Ошибка при удалении аккаунта');
      const responseData = await response.json();
      if (!responseData.status) throw new Error('Ошибка при удалении аккаунта');
      return responseData;
    } catch (error) {
      console.error('deleteProfile: Unexpected error:', error);
      throw error;
    }
  },
};
