import { hasUnblockedUsers } from '../utils/helpers/hasBlockedUsers';

describe('hasUnblockedUsers', () => {
  test('должно возвращать true, если в массиве есть false', () => {
    expect(hasUnblockedUsers([true, false, true])).toBe(true);
  });

  test('должно возвращать true, если массив пустой', () => {
    expect(hasUnblockedUsers([])).toBe(true);
  });

  test('должно возвращать false, если все элементы true', () => {
    expect(hasUnblockedUsers([true, true, true])).toBe(false);
  });

  test('должно возвращать true, если в массиве только один false', () => {
    expect(hasUnblockedUsers([false])).toBe(true);
  });

  test('должно возвращать false, если в массиве только один true', () => {
    expect(hasUnblockedUsers([true])).toBe(false);
  });
});
