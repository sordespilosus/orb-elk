export type RolesParams = {
  order_by?: 'id' | 'name' | 'public_name';
  is_asc?: boolean;
};

export type Role = {
  id: number;
  public_name: string;
  name: string;
};

export type RolesResponce = {
  status: boolean;
  data: Role[];
};
