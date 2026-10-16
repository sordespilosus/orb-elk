import { $url } from '@/utils/config';
import { CityResponce } from './types';

export const citiesApi = {
  getCities: async ({ q = '', count = 7 }: { q?: string; count?: number }): Promise<CityResponce> => {
    try {
      const res = await fetch(`${$url}/api/v2/cities?city=${q}&count=${count}`);
      if (!res.ok) {
        throw new Error('Ошибка при получении списка городов');
      }
      const data = await res.json();
      if (!data.status) {
        throw new Error('Ошибка при получении списка городов');
      }
      return data;
    } catch (error) {
      console.error(error);
      throw error;
    }
  },
};
