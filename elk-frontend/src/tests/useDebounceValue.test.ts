import { renderHook, act } from '@testing-library/react';
import { useDebounceValue } from '../utils/useDebounceValue';

jest.useFakeTimers();

describe('useDebounceValue', () => {
  test('должен возвращать изначальное значение сразу', () => {
    const { result } = renderHook(() => useDebounceValue('test', 500));
    expect(result.current).toBe('test');
  });

  test('должен обновлять значение после задержки', () => {
    const { result, rerender } = renderHook(({ value }) => useDebounceValue(value, 500), {
      initialProps: { value: 'initial' },
    });

    rerender({ value: 'updated' });
    expect(result.current).toBe('initial');

    act(() => {
      jest.advanceTimersByTime(500);
    });

    expect(result.current).toBe('updated');
  });

  test('должен сбрасывать таймер при изменении значения', () => {
    const { result, rerender } = renderHook(({ value }) => useDebounceValue(value, 500), {
      initialProps: { value: 'first' },
    });

    rerender({ value: 'second' });
    act(() => {
      jest.advanceTimersByTime(250);
    });

    rerender({ value: 'third' });
    act(() => {
      jest.advanceTimersByTime(250);
    });

    expect(result.current).toBe('first');

    act(() => {
      jest.advanceTimersByTime(500);
    });

    expect(result.current).toBe('third');
  });
});
