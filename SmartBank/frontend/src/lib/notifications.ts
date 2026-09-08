import { fetchApi } from "@/lib/api";

export type SmartBankNotification = {
  id: string;
  user_id: string;
  type: string;
  channel: string;
  source_app: string | null;
  source_ref: string | null;
  title: string;
  body: string;
  payload: Record<string, unknown> | null;
  read_at: string | null;
  created_at: string;
};

type Envelope<T> = T | { data?: T };

function unwrap<T>(response: Envelope<T>): T {
  return "data" in (response as object) && (response as { data?: T }).data
    ? (response as { data: T }).data
    : (response as T);
}

export async function listNotifications(options?: { type?: string; unreadOnly?: boolean }) {
  const params = new URLSearchParams({ page: "1", limit: "100" });
  if (options?.type) params.set("type", options.type);
  if (options?.unreadOnly) params.set("unread_only", "true");
  const response = await fetchApi<Envelope<{ items: SmartBankNotification[]; total: number }>>(
    `/api/bank/users/me/notifications?${params.toString()}`,
  );
  return unwrap(response);
}

export async function unreadNotificationCount() {
  const response = await fetchApi<Envelope<{ count: number }>>(
    "/api/bank/users/me/notifications/unread-count",
  );
  return unwrap(response).count;
}

export async function markNotificationRead(id: string) {
  return fetchApi(`/api/bank/users/me/notifications/${encodeURIComponent(id)}/read`, {
    method: "PATCH",
    body: JSON.stringify({}),
  });
}

export async function markAllNotificationsRead() {
  return fetchApi("/api/bank/users/me/notifications/read-all", {
    method: "POST",
    body: JSON.stringify({}),
  });
}
