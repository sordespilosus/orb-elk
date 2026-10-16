import { getCsrfToken } from '@/utils/api';
import { $url } from '@/utils/config';

export const authApi = {
  getCsrf: async (): Promise<{ csrf_token: string }> => {
    try {
      const res = await fetch(`${$url}/csrf_token`);
      const response = await res.json();
      return response;
    } catch (error) {
      console.error('getCsrf: Unexpected error:', error);
      throw error;
    }
  },
  logout: async (): Promise<{ status: boolean }> => {
    try {
      const res = await fetch(`${$url}/after_logout`, {
        method: 'GET',
        headers: {
          'X-CSRFToken': getCsrfToken(),
        },
        redirect: 'manual',
      });
      if (res.status == 0) return { status: true };
      if (res.status >= 300 && res.status < 400) return { status: true };
      if (!res.ok) throw new Error('Ошибка при выходе');
      return { status: true };
    } catch (error) {
      console.error('logout: Unexpected error:', error);
      throw error;
    }
  },
  login: async (body: { login: string; password: string }): Promise<{ status: boolean }> => {
    try {
      const formData = new FormData();
      formData.append('login', body.login);
      formData.append('password', body.password);
      formData.append('_token', getCsrfToken());
      const res = await fetch(`${$url}/api/v2/service_login`, {
        method: 'POST',
        headers: {
          'X-CSRFToken': getCsrfToken(),
        },
        body: formData,
        redirect: 'manual',
      });
      if (res.status == 0) return { status: true };
      if (res.status == 400) {
        const response = await res.json();
        if (response.errors.login) throw new Error('Неверный логин');
        if (response.errors.password) throw new Error('Неверный пароль');
        if (response.errors.invalidCredentials) throw new Error('Неверный пароль');
      }
      if (res.status == 401) throw new Error('Пользователь заблокирован');
      if (res.status == 404) {
        const response = await res.json();
        if (response.errors.userNotExists) throw new Error('Пользователь не существует');
        if (response.errors.userNotActive) throw new Error('Пользователь не активирован');
        if (response.errors.userDeleted) throw new Error('Пользователь удален');
      }
      if (res.status == 500) throw new Error('Ошибка сервера');
      if (res.status >= 300 && res.status < 400) throw new Error('Ошибка при авторизации');
      if (!res.ok) throw new Error('Ошибка при авторизации');
      return { status: true };
    } catch (error) {
      console.error('login: Unexpected error:', error);
      throw error;
    }
  },
};
