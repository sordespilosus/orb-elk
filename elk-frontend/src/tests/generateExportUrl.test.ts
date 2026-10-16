import { generateExportUsersUrl, generateExportLogsUrl } from '@/api/export';
import { $url } from '@/utils/config';

jest.mock('@/utils/config', () => ({
  $url: 'http://mocked-url.com',
}));

describe('generateExportUsersUrl', () => {
  test('должен генерировать URL с дефолтными параметрами', () => {
    const expectedUrl = `${$url}/admin/api/users/export?mode=csv&order_by=id&is_asc=false`;
    expect(generateExportUsersUrl({})).toBe(expectedUrl);
  });

  test('должен генерировать URL с переданными параметрами', () => {
    const expectedUrl = `${$url}/admin/api/users/export?mode=xlsx&order_by=email&is_asc=true`;
    expect(generateExportUsersUrl({ mode: 'xlsx', order_by: 'email', is_asc: true })).toBe(expectedUrl);
  });

  test('должен фильтровать undefined параметры', () => {
    const expectedUrl = `${$url}/admin/api/users/export?mode=csv&order_by=id&is_asc=false&gender=male`;
    expect(generateExportUsersUrl({ gender: 'male' })).toBe(expectedUrl);
  });
});

describe('generateExportLogsUrl', () => {
  test('должен генерировать URL с дефолтными параметрами', () => {
    const expectedUrl = `${$url}/admin/api/export_logs?mode=csv&order_by=id&is_asc=false`;
    expect(generateExportLogsUrl({})).toBe(expectedUrl);
  });

  test('должен генерировать URL с переданными параметрами', () => {
    const expectedUrl = `${$url}/admin/api/export_logs?mode=xlsx&order_by=created_at&is_asc=true`;
    expect(generateExportLogsUrl({ mode: 'xlsx', order_by: 'created_at', is_asc: true })).toBe(expectedUrl);
  });
});
