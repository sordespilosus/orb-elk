import { SystemParameters } from '@/validation/system-parameters.ts';
import { Client } from '@/api/clients';

/**
 * Преобразует данные из SystemParameters в формат Client.
 * acceptedSystem - не используется
 * permissionsWindow - не используется
 */
export const transformSystemParametersToClient = (
  data: SystemParameters
): Omit<
  Client,
  | 'created_at'
  | 'id'
  | 'password_client'
  | 'personal_access_client'
  | 'revoked'
  | 'status_changed_at'
  | 'update_agreement'
  | 'updated_at'
  | 'user_id'
  | 'reasons_for_rejections'
  | 'secret'
  | 'ord'
> => ({
  name: data.systemName,
  description: data.systemDescription,
  redirect: data.systemRedirectUrl ? [data.systemRedirectUrl] : [],
  url_site: data.systemAddress,
  url_login: data.systemEnterUrl,
  url_notifications: data.systemNotificationUrl || '',
  need_esia: data.requiredESIA || false,
  esia_level: (data.accountLevelESIA as Client['esia_level']) || 'STANDART',
  is_hidden: data.hideSystem || false,
  only_login: data.loginForm || false,
  only_esia: data.requiredESIA || false,
  need_organization: data.needOrganization || false,
  // revoked: data.acceptedSystem || false,
  dont_remember: data.rememberUser || false,
  without_oauth_notify: data.permissionsWindow || false,
  scope: {
    rsaag_id: data.accessToRsaagId || false, // Доступ к открытой сессии пользователя (ID пользователя)
    personal_data: data.accessToPersonalInfo || false, // Доступ к персональной информации (ФИО, дате рождения, городу проживания, логину)
    esia_data: data.accessToEsiaData || false, // Доступ к персональной информации из Госуслуг
    email: data.accessToEmail || false, // Доступ к E-mail
    phone: data.accessToPhone || false, // Доступ к номеру телефона
    esia_user_id: data.accessUserIdGos || false, // Доступ к ID пользователя в Госуслугах
    passport: data.accessToPassportData || false, // Доступ к паспортным данным
    snils: data.accessToNumberSNILS || false, // Доступ к номеру СНИЛС
    organizations_user: data.accessByUserOrganizations || false, // Доступ организациям пользователя
    auth_method: data.accessToAuthMethod || false, // Доступ к методу авторизации
    esia_address: data.accessToEsiaAddress || false, // Доступ к адресу пользователя
  },
  scope_required: {
    rsaag_id: data.accessToRsaagIdBinding || false, // Доступ к открытой сессии пользователя (ID пользователя)
    personal_data: data.accessToPersonalInfoBinding || false, // Доступ к персональной информации (ФИО, дате рождения, городу проживания, логину)
    esia_data: data.accessToEsiaDataBinding || false, // Доступ к персональной информации из Госуслуг
    email: data.accessToEmailBinding || false, // Доступ к E-mail
    phone: data.accessToPhoneBinding || false, // Доступ к номеру телефона
    esia_user_id: data.accessUserIdGosBinding || false, // Доступ к ID пользователя в Госуслугах
    passport: data.accessToPassportDataBinding || false, // Доступ к паспортным данным
    snils: data.accessToNumberSNILSBinding || false, // Доступ к номеру СНИЛС
    organizations_user: data.accessByUserOrganizationsBinding || false, // Доступ организациям пользователя
    auth_method: data.accessToAuthMethodBinding || false, // Доступ к методу авторизации
    esia_address: data.accessToEsiaAddressBinding || false, // Доступ к адресу пользователя
  },
  auth_methods: [
    ...(data.loginWithUsernameAndPassword ? (['login'] as const) : []),
    ...(data.loginViaGosUslugi ? (['esia'] as const) : []),
    ...(data.loginVK ? (['vk'] as const) : []),
  ],
  agreement: data.userAgreementSystem,
  agreement_updated_at: data.agreementUpdatedAt,
  logo: data.file || '',
  status: data.acceptedSystem || undefined,
  reasons: data.acceptedSystem === 'rejected' ? data.reasons : undefined,
  client_hits_esia_scope: {
    ext_org_info: data.ext_org_info || false,
    default_data: data.default_data || false,
    temporary_residence_doc: data.temporary_residence_doc || false,
    drivers_licence_doc: data.drivers_licence_doc || false,
    residence_doc: data.residence_doc || false,
    birth_cert_doc: data.birth_cert_doc || false,
    vehicles: data.vehicles || false,
    kids: data.kids || false,
  },
});
