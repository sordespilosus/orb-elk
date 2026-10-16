export type GetUsersParams = {
  page?: number;
  per_page?: number;
  order_by?: 'id' | 'email' | 'fio' | 'blocked' | 'email_verified_at';
  is_asc?: number;
  fio?: string;
  second_name?: string;
  first_name?: string;
  patroname?: string;
  city?: string;
  id?: number;
  is_admin?: boolean;
  is_blocked?: boolean;
  is_accepted?: boolean;
  gender?: 'male' | 'female';
  esia?: boolean;
  age_period_from?: number;
  age_period_to?: number;
  only_verified?: boolean;
};

export type User = {
  id: number;
  email: string;
  surname: string;
  name: string;
  patroname: string;
  phone: string;
  birthday: string | null;
  age?: number | string | null;
  age_period: string;
  gender: 'male' | 'female' | null;
  esia_user_id: number;
  vk_user_id: number;
  fb_user_id: number;
  is_accepted: boolean;
  blocked: boolean;
  email_verified_at: string;
  fio: string;
  created_at: string;
  updated_at: string;
  is_deleted: boolean;
  is_admin?: boolean;
  cnt_users: number;
};

export type UpdateUserBody = {
  user_id: number;
  email: string;
  blocked: boolean;
  is_admin: boolean;
  is_deleted: boolean;
  email_verified: boolean;
};
