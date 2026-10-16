import { z, ZodSchema } from 'zod';

type FormSchemaType = {
  file?: string;
  systemName: string;
  systemDescription: string;
  secret?: string;
  systemRedirectUrl: string;
  systemAddress: string;
  systemEnterUrl?: string;
  systemNotificationUrl?: string;
  requiredESIA?: boolean;
  accountLevelESIA?: string;
  needOrganization?: boolean;
  hideSystem?: boolean;
  loginForm?: boolean;
  acceptedSystem?: 'approved' | 'rejected' | 'sent';
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
  accessToNumberSNILS?: boolean;
  accessToNumberSNILSBinding?: boolean;
  loginWithUsernameAndPassword?: boolean;
  loginViaGosUslugi?: boolean;
  loginVK?: boolean;
  userAgreementSystem?: string;
  agreementUpdatedAt?: string;
  reasons?: string;
  accessToRsaagId?: boolean;
  accessToEsiaData?: boolean;
  accessToEmail?: boolean;
  accessToPhone?: boolean;
  accessToAuthMethod?: boolean;
  accessToEsiaAddress?: boolean;
  accessToRsaagIdBinding?: boolean;
  accessToEsiaDataBinding?: boolean;
  accessToEmailBinding?: boolean;
  accessToPhoneBinding?: boolean;
  accessToAuthMethodBinding?: boolean;
  accessToEsiaAddressBinding?: boolean;

  // ЕСИАDATA
  birth_cert_doc?: boolean;
  default_data?: boolean;
  drivers_licence_doc?: boolean;
  ext_org_info?: boolean;
  kids?: boolean;
  residence_doc?: boolean;
  temporary_residence_doc?: boolean;
  vehicles?: boolean;
};

export const SystemParametersSchema: ZodSchema<FormSchemaType> = z
  .object({
    file: z.string({ message: 'Выберите изображение' }).optional(),
    systemName: z
      .string({ invalid_type_error: 'Значение должно быть строкой', required_error: 'Поле обязательно для заполнения' })
      .min(1, 'Не менее 1 символа')
      .refine((value) => /^[a-zA-Zа-яА-Я0-9]/.test(value), {
        message: 'Первый символ должен быть буквой или цифрой',
      })
      .refine((value) => /^[a-zA-Zа-яА-Я0-9\s\\-]+$/.test(value), {
        message: 'Поле должно содержать только буквы, цифры, пробелы и дефисы',
      }),
    systemDescription: z
      .string({ invalid_type_error: 'Значение должно быть строкой', required_error: 'Поле обязательно для заполнения' })
      .min(1, 'Не менее 1 символа')
      .refine((value) => /^[a-zA-Zа-яА-Я0-9]/.test(value), {
        message: 'Первый символ должен быть буквой или цифрой',
      }),
    secret: z.string().optional(),
    systemRedirectUrl: z
      .string({ invalid_type_error: 'Значение должно быть строкой', required_error: 'Поле обязательно для заполнения' })
      .url({ message: 'Некорректный Url' }),
    systemAddress: z.string({ required_error: 'Поле обязательно для заполнения' }).url({ message: 'Некорректный Url' }),
    systemEnterUrl: z.string().optional(),
    systemNotificationUrl: z.string().optional(),
    requiredESIA: z.boolean().optional(),
    accountLevelESIA: z.string().optional(),
    needOrganization: z.boolean().optional(),
    hideSystem: z.boolean().optional(),
    loginForm: z.boolean().optional(),
    acceptedSystem: z.enum(['approved', 'rejected', 'sent'], { required_error: 'Поле обязательно для заполнения' }),
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
    accessToNumberSNILS: z.boolean().optional(),
    accessToNumberSNILSBinding: z.boolean().optional(),
    loginWithUsernameAndPassword: z.boolean().optional(),
    loginViaGosUslugi: z.boolean().optional(),
    loginVK: z.boolean().optional(),
    userAgreementSystem: z.string().optional(),
    agreementUpdatedAt: z.string().optional(),
    reasons: z.string().optional(),
    accessToRsaagId: z.boolean().optional(),
    accessToEsiaData: z.boolean().optional(),
    accessToEmail: z.boolean().optional(),
    accessToPhone: z.boolean().optional(),
    accessToAuthMethod: z.boolean().optional(),
    accessToEsiaAddress: z.boolean().optional(),
    accessToRsaagIdBinding: z.boolean().optional(),
    accessToEsiaDataBinding: z.boolean().optional(),
    accessToEmailBinding: z.boolean().optional(),
    accessToPhoneBinding: z.boolean().optional(),
    accessToAuthMethodBinding: z.boolean().optional(),
    accessToEsiaAddressBinding: z.boolean().optional(),

    // ЕСИАDATA
    birth_cert_doc: z.boolean().optional(),
    default_data: z.boolean().optional(),
    drivers_licence_doc: z.boolean().optional(),
    ext_org_info: z.boolean().optional(),
    kids: z.boolean().optional(),
    residence_doc: z.boolean().optional(),
    temporary_residence_doc: z.boolean().optional(),
    vehicles: z.boolean().optional(),
  })
  .refine(
    (data) => {
      if (data.acceptedSystem === 'rejected') {
        return !!data.reasons && data.reasons.trim().length > 0;
      }
      data.reasons = undefined;
      return true;
    },
    {
      message: 'Причина отклонения обязательна.',
      path: ['reasons'],
    }
  );

export type SystemParameters = z.infer<typeof SystemParametersSchema>;
