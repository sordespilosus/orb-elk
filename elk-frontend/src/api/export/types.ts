export type ExportType = 'csv' | 'xlsx';
export interface ExportUsersQueryParams {
  mode?: ExportType; // Формат выходного файла
  order_by?:
    | 'id'
    | 'email'
    | 'fio'
    | 'gender'
    | 'blocked'
    | 'is_accepted'
    | 'birthday'
    | 'email_verified_at'
    | 'area_name'
    | 'admin'; // Столбец сортировки
  is_asc?: boolean; // Направление сортировки
  fio?: string; // %ФИО пользователя%
  first_name?: string; // %Имя пользователя%
  second_name?: string; // %Фамилия пользователя%
  patroname?: string; // %Отчество пользователя%
  city?: number; // Город (код)
  gender?: 'male' | 'female'; // Пол пользователя
  esia?: boolean; // Есть ли ЕСИА
  age_period_from?: number; // Возраст пользователя от
  age_period_to?: number; // Возраст пользователя до
  only_verified?: boolean; // Только с подтвержденной почтой
  id?: number; // ID пользователя
  is_admin?: boolean; // Администратор
  is_blocked?: boolean; // Заблокирован ли пользователь
  is_accepted?: boolean; // Подтвержден ли пользователь
}

export type ExportResponse = {
  data: string;
  status: 'success' | 'error';
};

export interface ExportLogsQueryParams {
  mode?: ExportType; // Формат выходного файла
  order_by?: 'id' | 'user_id' | 'action' | 'created_at'; // Столбец сортировки
  is_asc?: boolean; // Направление сортировки
}
