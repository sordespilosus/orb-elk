import { z } from 'zod';

export const ProfileSchema = z.object({
  email: z.string().email(),
  name: z.string({ message: 'Имя обязательное поле' }),
  surname: z.string({ message: 'Фамилия обязательное поле' }),
  birthday: z.string({ message: 'День рождения обязательное поле' }).date('Неверный формат даты').nullable(),
  gender: z.string({ message: 'Гендер обязательное поле' }).nullable(),
  city: z.string({ message: 'Город обязательное поле' }).nullable(),
  patroname: z.string({ message: 'Отчество обязательное поле' }).nullable(),
  phone: z.string({ message: 'Телефон обязательное поле' }).nullable(),
  photo: z.string().nullable(),
  points: z.number().nullable(),
  area_name: z.string().nullable(),
  international_passport: z.string().nullable(),
  inn: z.string().nullable(),
  pension_certificate: z.string().nullable(),
  driver_license: z.string().nullable(),
  isAdmin: z.boolean(),
});

export type ProfileData = z.infer<typeof ProfileSchema> & { id: number };
