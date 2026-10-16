import { z } from 'zod';

export type BooleanFilterValue = 'all' | 'true' | 'false';

const optionalAgeSchema = z.preprocess(
  (value) => (value === '' || value === null || value === undefined ? undefined : Number(value)),
  z.number().min(0, { message: ' ' }).max(150, { message: ' ' }).optional()
);

export const filterUserSchema = z
  .object({
    surname: z.string().optional(),
    name: z.string().optional(),
    patronymic: z.string().optional(),
    id: z.string().optional(),
    settlement: z.string().optional(),
    minAge: optionalAgeSchema,
    maxAge: optionalAgeSchema,
    gender: z.string().optional(),
    isAdmin: z.enum(['all', 'true', 'false']).optional(),
    isBlocked: z.enum(['all', 'true', 'false']).optional(),
    isConfirmed: z.enum(['all', 'true', 'false']).optional(),
  })
  .superRefine((data, ctx) => {
    if (data.minAge !== undefined && data.maxAge !== undefined) {
      if (data.minAge > data.maxAge) {
        ctx.addIssue({
          code: 'custom',
          message: ' ', //Минимальный возраст не должен превышать максимальный.
          path: ['minAge'],
        });
        ctx.addIssue({
          code: 'custom',
          message: '', //Максимальный возраст не должен быть меньше минимального.
          path: ['maxAge'],
        });
      }
    }
  });

export type FilterUserSchemaType = z.infer<typeof filterUserSchema>;
