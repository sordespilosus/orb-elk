export type SetStatusBody = {
  client_id: number;
  status: 'rejected' | 'accepted';
  changes_ids: number[];
  reasons: string | null;
};
