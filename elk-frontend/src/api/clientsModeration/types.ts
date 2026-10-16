export type SetStatusParams = {
  client_id: number;
  status: 'approved' | 'rejected';
  ord?: number;
  reasons?: string | null;
};
