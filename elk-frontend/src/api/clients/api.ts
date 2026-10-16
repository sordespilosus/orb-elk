import { getCsrfToken } from '@/utils/api';
import { Client, ClientsModerationResponse, ClientsResponse, EsiaScopes, ScopeNames } from './types';
import { $url } from '@/utils/config';

export const clientsApi = {
  getClients: async (): Promise<ClientsResponse> => {
    try {
      const res = await fetch(`${$url}/api/v2/clients`, {
        headers: {
          'X-CSRFToken': getCsrfToken(),
        },
      });
      if (!res.ok) throw new Error('Ошибка при получении клиентов');
      const response = await res.json();
      if (!response.status) throw new Error('Ошибка при получении клиентов');
      return response;
    } catch (error) {
      console.error('getClients: Unknown error', error);
      throw error;
    }
  },
  getSystemDescription: async (): Promise<{ status: boolean; data: string | null }> => {
    try {
      const res = await fetch(`${$url}/api/v2/system-description`, {
        headers: {
          'X-CSRFToken': getCsrfToken(),
        },
      });
      if (!res.ok) throw new Error('Ошибка при получении описания системы');
      const response = await res.json();
      if (!response.status) throw new Error('Ошибка при получении описания системы');
      return response;
    } catch (error) {
      console.error('getSystemDescription: Unknown error', error);
      throw error;
    }
  },
  getClient: async (
    id?: number
  ): Promise<{
    status: boolean;
    data: {
      client: Client;
      scopes: EsiaScopes;
      scope: ScopeNames;
    };
  }> => {
    if (!id) throw new Error('id is required');
    try {
      const res = await fetch(`${$url}/api/v2/client/${id}`, {
        headers: {
          'X-CSRFToken': getCsrfToken(),
        },
      });
      if (!res.ok) throw new Error('Ошибка при получении клиента');
      const response = await res.json();
      if (!response.status) throw new Error('Ошибка при получении клиента');
      return response;
    } catch (error) {
      console.error('getClient: Unknown error', error);
      throw error;
    }
  },
  getAdminClients: async (): Promise<{
    status: boolean;
    data: {
      clients: Client[];
    };
  }> => {
    try {
      const res = await fetch(`${$url}/admin/api/clients`, {
        headers: {
          'X-CSRFToken': getCsrfToken(),
        },
      });
      if (!res.ok) throw new Error('Ошибка при получении клиентов');
      const response = await res.json();
      if (!response.status) throw new Error('Ошибка при получении клиентов');
      return response;
    } catch (error) {
      console.error('getAdminClients: Unknown error', error);
      throw error;
    }
  },
  getClientsModeration: async (tab: 'clients' | 'changes'): Promise<ClientsModerationResponse> => {
    try {
      const searchParams = new URLSearchParams({
        tab,
      });
      const res = await fetch(`${$url}/admin/api/clients_moderation?${searchParams.toString()}`, {
        headers: {
          'X-CSRFToken': getCsrfToken(),
        },
      });
      if (!res.ok) throw new Error('Ошибка при получении клиентов');
      const response = await res.json();
      if (!response.status) throw new Error('Ошибка при получении клиентов');
      return response;
    } catch (error) {
      console.error('getClientsModeration: Unknown error', error);
      throw error;
    }
  },
  patchClient: async (
    data: Partial<Client> & {
      id: number;
      name: string;
      redirect: string[];
      url_site: string;
      oldAgreement?: string;
      oldAgreementUpdatedAt?: string;
    }
  ): Promise<{
    status: boolean;
    data: Client;
  }> => {
    console.log('data', data);
    try {
      const response = await fetch(`${$url}/admin/api/clients/edit`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRFToken': getCsrfToken(),
        },
        body: JSON.stringify({
          ...data,
          update_agreement:
            data.oldAgreement?.trim() !== data.agreement?.trim() ||
            data.oldAgreementUpdatedAt !== data.agreement_updated_at,
          scope: JSON.stringify(
            Object.entries(data.scope ?? {})
              .filter(([_, v]) => v)
              .map(([key]) => key)
          ),
          scope_required: JSON.stringify(
            Object.entries(data.scope_required ?? {})
              .filter(([_, v]) => v)
              .map(([key]) => key)
          ),
          client_hits_esia_scope: JSON.stringify(
            Object.entries(data.client_hits_esia_scope ?? {})
              .filter(([_, v]) => v)
              .map(([key]) => key)
          ),
          _token: getCsrfToken(),
        }),
      });
      if (!response.ok) throw new Error('Ошибка при обновлении клиента');
      const responseData = await response.json();
      if (!responseData.status) throw new Error('Ошибка при обновлении клиента');
      return responseData;
    } catch (error) {
      console.error('patchClient: Unknown error', error);
      throw error;
    }
  },
  createClient: async (
    data: Omit<
      Client,
      | 'created_at'
      | 'id'
      | 'password_client'
      | 'personal_access_client'
      | 'revoked'
      | 'status_changed_at'
      | 'update_agreement'
      | 'updated_at'
      | 'user_id'
      | 'client_hits_esia_scope'
      | 'reasons_for_rejections'
      | 'secret'
      | 'ord'
    >
  ): Promise<{
    status: boolean;
    data: Client;
  }> => {
    try {
      const response = await fetch(`${$url}/admin/api/clients/new`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRFToken': getCsrfToken(),
        },
        body: JSON.stringify({
          _token: getCsrfToken(),
          name: data.name,
          redirect: data.redirect,
          url_login: data.url_login,
          url_notifications: data.url_notifications,
          need_esia: data.need_esia,
          only_esia: data.only_esia,
          dont_remember: data.dont_remember,
          logo: data.logo,
          url_site: data.url_site,
          is_hidden: data.is_hidden,
          description: data.description,
          need_organization: data.need_organization,
          scope: JSON.stringify(
            Object.entries(data.scope)
              .filter(([_, v]) => v)
              .map(([key]) => key)
          ),
          scope_required: JSON.stringify(
            Object.entries(data.scope_required)
              .filter(([_, v]) => v)
              .map(([key]) => key)
          ),
          esia_level: data.esia_level,
          // ord: data.ord,
          without_oauth_notify: data.without_oauth_notify,
          auth_methods: JSON.stringify(data.auth_methods),
          only_login: data.only_login,
          agreement: data.agreement,
          agreement_updated_at: data.agreement_updated_at,
        }),
      });
      if (!response.ok) throw new Error('Ошибка при создании клиента');
      const responseData = await response.json();
      if (!responseData.status) throw new Error('Ошибка при создании клиента');
      return responseData;
    } catch (error) {
      console.error('createClient: Unknown error', error);
      throw error;
    }
  },
  deleteClient: async (
    id: number
  ): Promise<{
    status: boolean;
    data: Client;
  }> => {
    try {
      const response = await fetch(`${$url}/admin/api/clients/delete`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRFToken': getCsrfToken(),
        },
        body: JSON.stringify({
          client_id: id,
          _token: getCsrfToken(),
        }),
      });
      if (!response.ok) throw new Error('Ошибка при удалении клиента');
      const responseData = await response.json();
      if (!responseData.status) throw new Error('Ошибка при удалении клиента');
      return responseData;
    } catch (error) {
      console.error('deleteClient: Unknown error', error);
      throw error;
    }
  },
  recallClientAgreement: async (
    id: number
  ): Promise<{
    status: boolean;
  }> => {
    try {
      const response = await fetch(`${$url}/api/v2/client/${id}/revoke`, {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRFToken': getCsrfToken(),
        },
      });
      if (!response.ok) throw new Error('Ошибка при отзыве пользовательского соглашения');
      const responseData = await response.json();
      if (!responseData.status) throw new Error('Ошибка при отзыве пользовательского соглашения');
      return responseData;
    } catch (error) {
      console.error('recallClientAgreement: Unknown error', error);
      throw error;
    }
  },
};
