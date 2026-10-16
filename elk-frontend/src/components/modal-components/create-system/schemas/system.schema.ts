import { z, ZodSchema } from 'zod';

// Todo поменять имена переменных
type FormSchemaType = {
  file?: string;
  systemName: string;
  systemDescription: string;
  systemRedirectUrl: string;
  systemAddress: string;
  systemEnterUrl?: string;
  systemNotificationUrl?: string;
  requiredESIA?: boolean;
  accountLevelESIA?: string;
  ip?: boolean;
  hideSystem?: boolean;
  loginForm?: boolean;
  acceptedSystem?: boolean;
  rememberUser?: boolean;
  permissionsWindow?: boolean;
  accessUserIdGos?: boolean;
  accessUserIdGosBinding?: boolean;
  accessByUserOrganizations?: boolean;
  accessByUserOrganizationsBinding?: boolean;
  accessToPassportData?: boolean;
  accessToPassportDataBinding?: boolean;
  accessToPersonalInfo?: boolean;
  accessToPersonalInfoBinding?: boolean;
  loginWithUsernameAndPassword?: boolean;
  loginViaGosUslugi?: boolean;
  loginVK?: boolean;
  userAgreementSystem?: string;
};

export const systemSchema: ZodSchema<FormSchemaType> = z.object({
  file: z.string({ message: 'Выберите изображение' }).optional(),
  systemName: z
    .string({ invalid_type_error: 'Значение должно быть строкой', required_error: 'Поле обязательно для заполнения' })
    .min(1, 'Не менее 1 символа')
    .refine((value) => /^[a-zA-Zа-яА-Я0-9]/.test(value), {
      message: 'Первый символ должен быть буквой или цифрой',
    })
    .refine((value) => /^[a-zA-Zа-яА-Я0-9\s\-]+$/.test(value), {
      message: 'Поле должно содержать только буквы, цифры, пробелы и дефисы',
    }),
  systemDescription: z
    .string({ invalid_type_error: 'Значение должно быть строкой', required_error: 'Поле обязательно для заполнения' })
    .min(1, 'Не менее 1 символа')
    .refine((value) => /^[a-zA-Zа-яА-Я0-9]/.test(value), {
      message: 'Первый символ должен быть буквой или цифрой',
    }),
  systemRedirectUrl: z
    .string({ invalid_type_error: 'Значение должно быть строкой', required_error: 'Поле обязательно для заполнения' })
    .url({ message: 'Некорректный Url' }),
  systemAddress: z.string().url({ message: 'Некорректный Url' }),
  systemEnterUrl: z.string().optional(),
  systemNotificationUrl: z.string().optional(),
  requiredESIA: z.boolean().optional(),
  accountLevelESIA: z.string().optional(),
  ip: z.boolean().optional(),
  hideSystem: z.boolean().optional(),
  loginForm: z.boolean().optional(),
  acceptedSystem: z.boolean().optional(),
  rememberUser: z.boolean().optional(),
  permissionsWindow: z.boolean().optional(),
  accessUserIdGos: z.boolean().optional(),
  accessUserIdGosBinding: z.boolean().optional(),
  accessByUserOrganizations: z.boolean().optional(),
  accessByUserOrganizationsBinding: z.boolean().optional(),
  accessToPassportData: z.boolean().optional(),
  accessToPassportDataBinding: z.boolean().optional(),
  accessToPersonalInfo: z.boolean().optional(),
  accessToPersonalInfoBinding: z.boolean().optional(),
  loginWithUsernameAndPassword: z.boolean().optional(),
  loginViaGosUslugi: z.boolean().optional(),
  loginVK: z.boolean().optional(),
  userAgreementSystem: z.string().optional(),
});

export type SystemSchemaType = z.infer<typeof systemSchema>;
