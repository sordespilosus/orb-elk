// src/utils/helpers/__tests__/systemTransformers.test.ts

import { transformSystemParametersToClient } from '@/utils/helpers/systemTransformers';
import { SystemParameters } from '@/validation/system-parameters.ts';
import { Client } from '@/api/clients';

describe('transformSystemParametersToClient', () => {
  let input: SystemParameters;
  let expectedOutput: Omit<Client, 'created_at' | 'id' | 'password_client' | 'personal_access_client' | 'revoked' | 'status_changed_at' | 'update_agreement' | 'updated_at' | 'user_id' | 'reasons_for_rejections' | 'secret' | 'ord'>;

  beforeEach(() => {
    input = {
      systemName: 'Тестовая система',
      systemDescription: 'Тестовая система',
      systemRedirectUrl: 'http://redirect.url',
      systemAddress: 'http://system.address',
      systemEnterUrl: 'http://enter.url',
      systemNotificationUrl: 'http://notification.url',
      requiredESIA: true,
      accountLevelESIA: 'NONE',
      hideSystem: true,
      loginForm: true,
      needOrganization: true,
      rememberUser: true,
      permissionsWindow: true,
      accessToRsaagId: true,
      accessToPersonalInfo: true,
      accessToEsiaData: true,
      accessToEmail: true,
      accessToPhone: true,
      accessUserIdGos: true,
      accessToPassportData: true,
      accessToNumberSNILS: true,
      accessByUserOrganizations: true,
      accessToAuthMethod: true,
      accessToEsiaAddress: true,
      accessToRsaagIdBinding: true,
      accessToPersonalInfoBinding: true,
      accessToEsiaDataBinding: true,
      accessToEmailBinding: true,
      accessToPhoneBinding: true,
      accessUserIdGosBinding: true,
      accessToPassportDataBinding: true,
      accessToNumberSNILSBinding: true,
      accessByUserOrganizationsBinding: true,
      accessToAuthMethodBinding: true,
      accessToEsiaAddressBinding: true,
      loginWithUsernameAndPassword: true,
      loginViaGosUslugi: true,
      loginVK: true,
      userAgreementSystem: 'Пользовательское соглашение',
      agreementUpdatedAt: '2025-01-09',
      file: 'logo.png',
      acceptedSystem: 'approved',
      reasons: 'Некоторые причины',
      ext_org_info: true,
      default_data: true,
      temporary_residence_doc: true,
      drivers_licence_doc: true,
      residence_doc: true,
      birth_cert_doc: true,
      vehicles: true,
      kids: true,
    };

    expectedOutput = {
      name: 'Тестовая система',
      description: 'Тестовая система',
      redirect: ['http://redirect.url'],
      url_site: 'http://system.address',
      url_login: 'http://enter.url',
      url_notifications: 'http://notification.url',
      need_esia: true,
      esia_level: 'NONE',
      is_hidden: true,
      only_login: true,
      only_esia: true,
      need_organization: true,
      dont_remember: true,
      without_oauth_notify: true,
      scope: {
        rsaag_id: true,
        personal_data: true,
        esia_data: true,
        email: true,
        phone: true,
        esia_user_id: true,
        passport: true,
        snils: true,
        organizations_user: true,
        auth_method: true,
        esia_address: true,
      },
      scope_required: {
        rsaag_id: true,
        personal_data: true,
        esia_data: true,
        email: true,
        phone: true,
        esia_user_id: true,
        passport: true,
        snils: true,
        organizations_user: true,
        auth_method: true,
        esia_address: true,
      },
      auth_methods: ['login', 'esia', 'vk'],
      agreement: 'Пользовательское соглашение',
      agreement_updated_at: '2025-01-09',
      logo: 'logo.png',
      status: 'approved',
      reasons: undefined,
      client_hits_esia_scope: {
        ext_org_info: true,
        default_data: true,
        temporary_residence_doc: true,
        drivers_licence_doc: true,
        residence_doc: true,
        birth_cert_doc: true,
        vehicles: true,
        kids: true,
      },
    };
  });

  it('должен корректно преобразовывать полный объект SystemParameters в формат Client', () => {
    const result = transformSystemParametersToClient(input);
    expect(result).toEqual(expectedOutput);
  });

  it('должен обрабатывать пустой объект SystemParameters без ошибок', () => {
    const emptyInput = {} as SystemParameters;
    const result = transformSystemParametersToClient(emptyInput);
    expect(result).toBeDefined();
  });
});
