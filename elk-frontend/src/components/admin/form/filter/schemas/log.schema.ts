import { z, ZodSchema } from 'zod';

type FormSchemaType = {
  dateRange: {
    from: Date;
    to?: Date;
  };
  timeLeftRange?: string;
  timeRightRange?: string;
};

export const filterLogSchema: ZodSchema<FormSchemaType> = z
  .object({
    dateRange: z.object(
      {
        from: z.date({ required_error: ' ' }),
        to: z.date().optional(),
      },
      { required_error: ' ' }
    ),
    timeLeftRange: z
      .string()
      .regex(/^\d{2}:\d{2}$/, ' ')
      .refine((value) => {
        const [hours, minutes] = value.split(':').map(Number);
        return hours >= 0 && hours < 24 && minutes >= 0 && minutes < 60;
      }, 'Введено некорректное время')
      .optional(),
    timeRightRange: z
      .string()
      .regex(/^\d{2}:\d{2}$/, ' ')
      .refine((value) => {
        const [hours, minutes] = value.split(':').map(Number);
        return hours >= 0 && hours < 24 && minutes >= 0 && minutes < 60;
      }, 'Введено некорректное время')
      .optional(),
  })
  .superRefine((data, ctx) => {
    if (data.timeLeftRange && data.timeRightRange) {
      const [leftHours, leftMinutes] = data.timeLeftRange.split(':').map(Number);
      const [rightHours, rightMinutes] = data.timeRightRange.split(':').map(Number);
      const leftTime = leftHours * 60 + leftMinutes;
      const rightTime = rightHours * 60 + rightMinutes;

      if (leftTime > rightTime) {
        ctx.addIssue({
          code: 'custom',
          message: ' ',
          path: ['timeLeftRange'],
        });
        ctx.addIssue({
          code: 'custom',
          message: ' ',
          path: ['timeRightRange'],
        });
      }
    }
  });

export type LogSchemaType = z.infer<typeof filterLogSchema>;
