import { usePageLinks } from '../hooks/usePageLinks';

describe('usePageLinks', () => {
  test('Возвращает [1, 2, 3] при currentPage = 1 и totalPages > 2', () => {
    expect(usePageLinks(1, 5)).toEqual([1, 2, 3]);
  });

  test('Возвращает [totalPages-2, totalPages-1, totalPages] при текущей странице = totalPages и totalPages > 2', () => {
    expect(usePageLinks(5, 5)).toEqual([3, 4, 5]);
  });

  test('Возвращает текущую и соседние страницы, если currentPage в середине', () => {
    expect(usePageLinks(3, 5)).toEqual([2, 3, 4]);
  });

  test('Возвращает только существующие страницы, если текущая страница граничная', () => {
    expect(usePageLinks(2, 3)).toEqual([1, 2, 3]);
  });

  test('Возвращает корректные страницы, если текущая страница в конце диапазона', () => {
    expect(usePageLinks(4, 5)).toEqual([3, 4, 5]);
  });

  test('Возвращает только одну страницу, если всего одна страница', () => {
    expect(usePageLinks(1, 1)).toEqual([1]);
  });

  test('Корректно работает с totalPages = 2', () => {
    expect(usePageLinks(1, 2)).toEqual([1, 2]);
    expect(usePageLinks(2, 2)).toEqual([1, 2]);
  });

  test('Игнорирует несуществующие страницы, если currentPage = 1, но всего 1 страница', () => {
    expect(usePageLinks(1, 1)).toEqual([1]);
  });

  test('Работает корректно при больших значениях totalPages', () => {
    expect(usePageLinks(10, 20)).toEqual([9, 10, 11]);
  });

  test('Фильтрует дубликаты и обрезает диапазон, если totalPages меньше 3', () => {
    expect(usePageLinks(1, 2)).toEqual([1, 2]);
  });
});
