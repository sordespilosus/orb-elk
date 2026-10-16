import { ExportLogsQueryParams, ExportUsersQueryParams } from './types';
import { $url } from '@/utils/config';
export const generateExportUsersUrl = ({
  mode = 'csv',
  order_by = 'id',
  is_asc = false,
  ...object
}: ExportUsersQueryParams) => {
  const obj = Object.fromEntries(
    Object.entries({
      mode,
      order_by,
      is_asc,
      ...object,
    })
      .filter(([_, value]) => value !== undefined)
      .map(([key, value]) => [key, value.toString()])
  );
  const paramsString = new URLSearchParams(obj).toString();
  return `${$url}/admin/api/users/export?${paramsString}`;
};

export const generateExportLogsUrl = ({ mode = 'csv', order_by = 'id', is_asc = false }: ExportLogsQueryParams) => {
  const obj = Object.fromEntries(
    Object.entries({
      mode,
      order_by,
      is_asc,
    })
      .filter(([_, value]) => value !== undefined)
      .map(([key, value]) => [key, value.toString()])
  );
  const paramsString = new URLSearchParams(obj).toString();
  return `${$url}/admin/api/export_logs?${paramsString}`;
};
