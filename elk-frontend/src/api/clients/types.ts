export interface ClientsResponse {
  status: boolean;
  data: ClientBody[];
  scope: ScopeNames;
  esiaScopes: ESCPS;
}

export interface ScopeNames {
  rsaag_id: string;
  personal_data: string;
  esia_data: string;
  email: string;
  phone: string;
  esia_user_id: string;
  fb_user_id: string;
  vk_user_id: string;
  passport: string;
  esia_address: string;
  snils: string;
  organizations_user: string;
  auth_method: string;
}

export interface ESCPS {
  ext_org_info: ESCPSInfo;
  default_data: ESCPSInfo;
  temporary_residence_doc: ESCPSInfo;
  drivers_licence_doc: ESCPSInfo;
  residence_doc: ESCPSInfo;
  birth_cert_doc: ESCPSInfo;
  vehicles: ESCPSInfo;
  kids: ESCPSInfo;
}

export interface ESCPSInfo {
  id: number;
  description: string;
  name: string;
}

export interface ClientBody {
  id: number;
  name: string;
  logo?: string | null;
  description?: string | null;
  url_site: string;
  scope: EScope;
  client_hits_esia_scope: ClientHitsEsiaScope;
  revoked_agreement: boolean;
  agreement_updated_at?: string | null;
}

export interface EScope {
  rsaag_id?: boolean;
  personal_data?: boolean;
  esia_data?: boolean;
  email?: boolean;
  phone?: boolean;
  esia_user_id?: boolean;
  fb_user_id?: boolean;
  vk_user_id?: boolean;
  passport?: boolean;
  esia_address?: boolean;
  snils?: boolean;
  organizations_user?: boolean;
  auth_method?: boolean;
}

export interface ScopeRequired {
  rsaag_id?: boolean;
  personal_data?: boolean;
  esia_data?: boolean;
  email?: boolean;
  phone?: boolean;
  esia_user_id?: boolean;
  fb_user_id?: boolean;
  vk_user_id?: boolean;
  passport?: boolean;
  esia_address?: boolean;
  snils?: boolean;
  organizations_user?: boolean;
  auth_method?: boolean;
}

export interface ReasonsForRejection {
  reasons: unknown;
  created_at: string;
}

export interface ClientHitsEsiaScope {
  ext_org_info?: boolean;
  default_data?: boolean;
  temporary_residence_doc?: boolean;
  drivers_licence_doc?: boolean;
  residence_doc?: boolean;
  birth_cert_doc?: boolean;
  vehicles?: boolean;
  kids?: boolean;
}

export type ClientsModerationResponse = {
  status: boolean;
  data: {
    scope: Scope;
    esiaScopes: EsiaScopes;
    newClients?: Client[];
    changesClients?: Client[];
  };
};

export type EsiaScopes = {
  ext_org_info: {
    id: number;
    description: string;
    name: string;
  };
  default_data: {
    id: number;
    description: string;
    name: string;
  };
  temporary_residence_doc: {
    id: number;
    description: string;
    name: string;
  };
  drivers_licence_doc: {
    id: number;
    description: string;
    name: string;
  };
  residence_doc: {
    id: number;
    description: string;
    name: string;
  };
  birth_cert_doc: {
    id: number;
    description: string;
    name: string;
  };
  vehicles: {
    id: number;
    description: string;
    name: string;
  };
  kids: {
    id: number;
    description: string;
    name: string;
  };
};

export type Scope = {
  rsaag_id: string;
  personal_data: string;
  esia_data: string;
  email: string;
  phone: string;
  esia_user_id: string;
  fb_user_id: string;
  vk_user_id: string;
  passport: string;
  esia_address: string;
  snils: string;
  organizations_user: string;
  auth_method: string;
};

export type Client = {
  id: number;
  user_id: number;
  name: string;
  secret?: string;
  redirect?: string[];
  personal_access_client: boolean;
  password_client: boolean;
  revoked: boolean;
  created_at: string;
  updated_at: string;
  url_login?: string;
  url_notifications: string;
  url_site: string;
  logo: string;
  need_esia: boolean;
  is_hidden: boolean;
  ord: number;
  description?: string;
  need_organization: boolean;
  scope: {
    rsaag_id?: boolean;
    personal_data?: boolean;
    esia_data?: boolean;
    email?: boolean;
    phone?: boolean;
    esia_user_id?: boolean;
    passport?: boolean;
    esia_address?: boolean;
    snils?: boolean;
    organizations_user?: boolean;
    auth_method?: boolean;
  };
  only_esia: boolean;
  dont_remember: boolean;
  scope_required: {
    rsaag_id?: boolean;
    personal_data?: boolean;
    esia_data?: boolean;
    email?: boolean;
    phone?: boolean;
    esia_user_id?: boolean;
    passport?: boolean;
    esia_address?: boolean;
    snils?: boolean;
    organizations_user?: boolean;
    auth_method?: boolean;
  };
  esia_level: 'TRUSTED' | 'STANDART' | 'NONE';
  status?: 'approved' | 'rejected' | 'sent';
  status_changed_at: string;
  without_oauth_notify: boolean;
  auth_methods?: ('login' | 'esia' | 'vk' | 'fb')[];
  only_login: boolean;
  reasons_for_rejections?: { reasons?: string; created_at: string }[];
  update_agreement: boolean;
  client_hits_esia_scope?: {
    ext_org_info: boolean;
    default_data: boolean;
    temporary_residence_doc: boolean;
    drivers_licence_doc: boolean;
    residence_doc: boolean;
    birth_cert_doc: boolean;
    vehicles: boolean;
    kids: boolean;
  };
  agreement?: string;
  revoked_agreement?: boolean;
  agreement_updated_at?: string;
  reasons?: string;
};
