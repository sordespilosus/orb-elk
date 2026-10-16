import { ISwitchBindings } from '@/hooks/useSwitchBinding.ts';
import { SystemParameters } from '@/validation/system-parameters.ts';

const optionsAccountLevelESIA = [
  { value: 'TRUSTED', name: 'Подтвержденные учетные записи' },
  { value: 'STANDART', name: 'Проверенные учетные записи' },
  { value: 'NONE', name: 'Не подтвержденные учетные записи' },
];

const optionsAcceptedSystem = [
  { value: 'approved', name: 'Подтверждена' },
  { value: 'sent', name: 'На модерации' },
  { value: 'rejected', name: 'Отклонена' },
];

const switchBindings: ISwitchBindings<SystemParameters>[] = [
  { parent: 'accessToRsaagId', binding: 'accessToRsaagIdBinding', triggerOn: true },
  { parent: 'accessToPersonalInfo', binding: 'accessToPersonalInfoBinding', triggerOn: true },
  { parent: 'accessToEsiaData', binding: 'accessToEsiaDataBinding', triggerOn: true },
  { parent: 'accessToEmail', binding: 'accessToEmailBinding', triggerOn: true },
  { parent: 'accessToPhone', binding: 'accessToPhoneBinding', triggerOn: true },
  { parent: 'accessUserIdGos', binding: 'accessUserIdGosBinding', triggerOn: true },
  { parent: 'accessToPassportData', binding: 'accessToPassportDataBinding', triggerOn: true },
  { parent: 'accessToNumberSNILS', binding: 'accessToNumberSNILSBinding', triggerOn: true },
  { parent: 'accessByUserOrganizations', binding: 'accessByUserOrganizationsBinding', triggerOn: true },
  { parent: 'accessToAuthMethod', binding: 'accessToAuthMethodBinding', triggerOn: true },
  { parent: 'accessToEsiaAddress', binding: 'accessToEsiaAddressBinding', triggerOn: true },
] as const;

export { optionsAccountLevelESIA, switchBindings, optionsAcceptedSystem };
