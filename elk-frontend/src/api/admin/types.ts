export type FetchLogsParams = {
  ip?: string;
  page?: number;
  per_page?: number;
  order_by?: 'id' | 'user_id' | 'date_created' | 'method' | 'url';
  is_asc?: boolean;
  max_id?: number;
  min_id?: number;
  max_date?: string;
  min_date?: string;
  method_get?: boolean;
  method_post?: boolean;
  method_others?: boolean;
};

export type CreateAgreementBody = {
  text_agreement: string;
};

export type Log = {
  id: number;
  user_id: number;
  ip: string;
  url: string;
  get: string[];
  post: string[];
  date_created: string;
  method: 'GET' | 'POST';
  header: {
    host: string[];
    connection: string[];
    'sec-ch-ua-platform': string[];
    'user-agent': string[];
    'sec-ch-ua': string[];
    'sec-ch-ua-mobile': string[];
    accept: string[];
    'sec-fetch-site': string[];
    'sec-fetch-mode': string[];
    referer: string[];
    'accept-encoding': string[];
    'accept-language': string[];
    cookie: string[];
  };
};

export type Logs = {
  countLogs: number;
  logs: Log[];
};
