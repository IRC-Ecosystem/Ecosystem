import { client } from './client';
import type { ApiResult } from './types';

export type Notification = {
  id: string;
  user_id: string;
  type: string;
  channel: string;
  source_app: string | null;
  source_ref: string | null;
  title: string;
  body: string;
  payload: Record<string, any> | null;
  read_at: string | null;
  created_at: string;
};

export const NotificationsApi = {
  list: async (type?: string, unreadOnly?: boolean, page = 1, limit = 20): Promise<ApiResult<{ items: Notification[]; total: number }>> => {
    const params = new URLSearchParams();
    if (type) params.append('type', type);
    if (unreadOnly) params.append('unread_only', 'true');
    params.append('page', page.toString());
    params.append('limit', limit.toString());
    return client.get(`/users/me/notifications?${params.toString()}`);
  },

  unreadCount: async (): Promise<ApiResult<{ count: number }>> => {
    return client.get('/users/me/notifications/unread-count');
  },

  markRead: async (id: string): Promise<ApiResult<Notification>> => {
    return client.patch(`/users/me/notifications/${id}/read`, {});
  },

  readAll: async (): Promise<ApiResult<{ updated: number }>> => {
    return client.post('/users/me/notifications/read-all', {});
  }
};
