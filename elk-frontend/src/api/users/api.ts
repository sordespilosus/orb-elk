import { getCsrfToken } from '@/utils/api';
import { GetUsersParams, UpdateUserBody, User } from './types';
import { $url } from '@/utils/config';

export const perPage = 10;

type ApiErrors = Record<string, string[] | string>;

const getApiErrorMessage = (errors?: ApiErrors): string | null => {
  if (!errors) return null;

  const firstError = Object.values(errors)[0];

  if (Array.isArray(firstError)) {
    return firstError[0] ?? null;
  }

  return firstError ?? null;
};

export const usersApi = {
  getUsers: async ({
    page = 1,
    per_page = perPage,
    order_by = 'id',
    is_asc = 1,
    age_period_from,
    age_period_to,
    city,
    esia,
    fio,
    first_name,
    gender,
    id,
    is_admin,
    is_accepted,
    is_blocked,
    only_verified,
    patroname,
    second_name,
  }: GetUsersParams): Promise<{
    status: boolean;
    data: {
      countUsers: number;
      users: User[];
    };
  }> => {
    try {
      const searchParams = new URLSearchParams({
        page: page.toString(),
        per_page: per_page.toString(),
        order_by,
        is_asc: is_asc.toString(),
        age_period_from: age_period_from?.toString() || '',
        age_period_to: age_period_to?.toString() || '',
        city: city || '',
        esia: esia?.toString() || '',
        fio: fio || '',
        first_name: first_name || '',
        gender: gender || '',
        id: id?.toString() || '',
        is_admin: is_admin?.toString() || '',
        is_accepted: is_accepted?.toString() || '',
        is_blocked: is_blocked?.toString() || '',
        only_verified: only_verified?.toString() || '',
        patroname: patroname || '',
        second_name: second_name || '',
      });
      const response = await fetch(`${$url}/admin/api/users?${searchParams.toString()}`, {
        method: 'GET',
        headers: {
          'X-CSRFToken': getCsrfToken(),
        },
      });
      if (!response.ok) throw new Error('Ошибка при получении пользователей');
      const responseData = await response.json();
      if (!responseData.status) throw new Error('Ошибка при получении пользователей');
      return responseData;
    } catch (error) {
      console.error('getUsers: Unknown error', error);
      throw error;
    }
  },
  getUser: async (
    user_id: number
  ): Promise<{
    status: boolean;
    data: User;
  }> => {
    try {
      const response = await fetch(`${$url}/admin/api/users/${user_id}`, {
        method: 'GET',
        headers: {
          'X-CSRFToken': getCsrfToken(),
        },
      });
      if (!response.ok) throw new Error('Ошибка при получении пользователя');
      const responseData = await response.json();
      if (!responseData.status) throw new Error('Ошибка при получении пользователя');
      return responseData;
    } catch (error) {
      console.error('getUser: Unknown error', error);
      throw error;
    }
  },
  blockUser: async (body: {
    user_id: number;
    value: boolean;
  }): Promise<{
    status: boolean;
    data: {
      blocked: boolean;
    };
  }> => {
    try {
      const response = await fetch(`${$url}/admin/api/users/blocking`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRFToken': getCsrfToken(),
        },
        body: JSON.stringify({
          user_id: body.user_id,
          value: body.value,
          _token: getCsrfToken(),
        }),
      });
      if (!response.ok) throw new Error('Ошибка при блокировке пользователя');
      const responseData = await response.json();
      if (!responseData.status) throw new Error('Ошибка при блокировке пользователя');
      return responseData;
    } catch (error) {
      console.error('blockUser: Unknown error', error);
      throw error;
    }
  },
  updateUser: async (
    body: UpdateUserBody
  ): Promise<{
    status: boolean;
    data: User;
  }> => {
    try {
      const response = await fetch(`${$url}/admin/api/users/edit`, {
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
      if (!response.ok) throw new Error('Ошибка при сохранении пользователя');
      const responseData = await response.json();
      if (!responseData.status) throw new Error(getApiErrorMessage(responseData.errors) || 'Ошибка при сохранении пользователя');
      return responseData;
    } catch (error) {
      console.error('updateUser: Unknown error', error);
      throw error;
    }
  },
  getUserRoles: async (
    user_id: number
  ): Promise<{
    status: boolean;
    data: {
      id: number;
      public_name: string;
      name: string;
    }[];
  }> => {
    try {
      const response = await fetch(`${$url}/admin/api/users/${user_id}/roles`);
      if (!response.ok) throw new Error('Ошибка при получении ролей пользователя');
      const responseData = await response.json();
      if (!responseData.status) throw new Error('Ошибка при получении ролей пользователя');
      return responseData;
    } catch (error) {
      console.error('getUserRoles: Unknown error', error);
      throw error;
    }
  },
  // сервак возвращает статус true перманентно, тут вместо status прилетает data
  setUserRoles: async (body: {
    user_id: number;
    roles: number[];
  }): Promise<{
    status: true;
    data: boolean;
  }> => {
    try {
      const response = await fetch(`${$url}/admin/api/users/${body.user_id}/roles`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRFToken': getCsrfToken(),
        },
        body: JSON.stringify({ roles: body.roles, _token: getCsrfToken() }),
      });
      if (!response.ok) throw new Error('Ошибка при установке ролей пользователя');
      const responseData = await response.json();
      if (!responseData.status) throw new Error('Ошибка при установке ролей пользователя');
      return responseData;
    } catch (error) {
      console.error('setUserRoles: Unknown error', error);
      throw error;
    }
  },
  deleteUserRole: async (body: {
    user_id: number;
    role_id: number;
  }): Promise<{
    status: boolean;
  }> => {
    try {
      const response = await fetch(`${$url}/admin/api/users/${body.user_id}/roles/unset`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRFToken': getCsrfToken(),
        },
        body: JSON.stringify({ role_id: body.role_id, _token: getCsrfToken() }),
      });
      if (!response.ok) throw new Error('Ошибка при удалении роли пользователя');
      const responseData = await response.json();
      if (!responseData.status) throw new Error('Ошибка при удалении роли пользователя');
      return responseData;
    } catch (error) {
      console.error('deleteUserRole: Unknown error', error);
      throw error;
    }
  },
  restoreUser: async (
    user_id: number
  ): Promise<{
    status: boolean;
  }> => {
    try {
      const response = await fetch(`${$url}/admin/api/users/${user_id}/restore`, {
        method: 'POST',
        body: JSON.stringify({ _token: getCsrfToken() }),
        headers: {
          'Content-Type': 'application/json',
          'X-CSRFToken': getCsrfToken(),
        },
      });
      if (!response.ok) throw new Error('Ошибка при восстановлении пользователя');
      const responseData = await response.json();
      if (!responseData.status) throw new Error('Ошибка при восстановлении пользователя');
      return responseData;
    } catch (error) {
      console.error('restoreUser: Unknown error', error);
      throw error;
    }
  },
};
