import { getCsrfToken } from '@/utils/api';
import { CreateAgreementBody, FetchLogsParams, Logs } from './types';
import { $url } from '@/utils/config';

export const adminApi = {
  getLogs: async ({
    page = 1,
    per_page = 50,
    order_by = 'date_created',
    is_asc = false,
    max_id,
    min_id,
    max_date,
    min_date,
    method_get,
    method_post,
    method_others,
    ip,
  }: FetchLogsParams = {}): Promise<{ status: boolean; data: Logs }> => {
    try {
      const searchParams = new URLSearchParams({
        page: page.toString(),
        per_page: per_page.toString(),
        order_by,
        is_asc: is_asc.toString(),
        max_id: max_id?.toString() ?? '',
        min_id: min_id?.toString() ?? '',
        max_date: max_date ?? '',
        min_date: min_date ?? '',
        method_get: method_get?.toString() ?? '',
        method_post: method_post?.toString() ?? '',
        method_others: method_others?.toString() ?? '',
        ip: ip ?? '',
      });
      const res = await fetch(`${$url}/admin/api/logs?${searchParams.toString()}`, {
        method: 'GET',
        headers: {
          'X-CSRFToken': getCsrfToken(),
        },
      });

      if (!res.ok) throw new Error('Ошибка при получении логов');
      const response = await res.json();
      if (!response.status) throw new Error('Ошибка при получении логов');

      return response;
    } catch (error) {
      console.error('getLogs: Unexpected error:', error);
      throw error;
    }
  },
  getAgreement: async (): Promise<{ status: boolean; data: string }> => {
    try {
      const res = await fetch(`${$url}/admin/api/get_agreement`);
      if (!res.ok) throw new Error('Ошибка при получении пользовательского соглашения');
      const response = await res.json();
      if (!response.status) throw new Error('Ошибка при получении пользовательского соглашения');
      return response;
    } catch (error) {
      console.error('getAgreement: Unexpected error:', error);
      throw error;
    }
  },
  setAgreement: async (body: CreateAgreementBody): Promise<{ status: boolean }> => {
    try {
      const res = await fetch(`${$url}/admin/api/set_agreement`, {
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

      if (!res.ok) throw new Error('Ошибка при изменении пользовательского соглашения');

      const response = await res.json();

      if (!response.status) throw new Error('Ошибка при изменении пользовательского соглашения');

      return response;
    } catch (error) {
      console.error('setAgreement: Unexpected error:', error);
      throw error;
    }
  },
  getSystemDescription: async (): Promise<{ status: boolean; data: string }> => {
    try {
      const res = await fetch(`${$url}/admin/api/get_description`);

      if (!res.ok) throw new Error('Ошибка при получении описания системы');

      const response = await res.json();

      if (!response.status) throw new Error('Ошибка при получении описания системы');

      return response;
    } catch (error) {
      console.error('getSystemDescription: Unexpected error:', error);
      throw error;
    }
  },
  setDescription: async (body: { description: string }): Promise<{ status: boolean }> => {
    try {
      const res = await fetch(`${$url}/admin/api/set_description`, {
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

      if (!res.ok) throw new Error('Ошибка при изменении описания системы');

      const response = await res.json();

      if (!response.status) throw new Error('Ошибка при изменении описания системы');

      return response;
    } catch (error) {
      console.error('setDescription: Unexpected error:', error);
      throw error;
    }
  },
};
