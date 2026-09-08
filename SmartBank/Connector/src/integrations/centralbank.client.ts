import { env } from '../config/env';

export class CentralBankClient {
  private static readonly baseUrl = env.CENTRAL_BANK_URL;
  private static readonly token = env.CENTRAL_BANK_SERVICE_TOKEN;
  private static failures = 0;
  private static openedAt = 0;
  private static readonly failureThreshold = 5;
  private static readonly resetTimeoutMs = 30_000;

  private static get headers() {
    return {
      'Authorization': `Bearer ${this.token}`,
      'Content-Type': 'application/json',
    };
  }

  private static async request(url: string, init: RequestInit = {}) {
    if (this.failures >= this.failureThreshold && Date.now() - this.openedAt < this.resetTimeoutMs) {
      throw new Error('CentralBank circuit breaker is open');
    }

    let lastError: unknown;
    for (let attempt = 0; attempt < 3; attempt += 1) {
      const controller = new AbortController();
      const timeout = setTimeout(() => controller.abort(), 8_000);
      try {
        const response = await fetch(url, { ...init, signal: controller.signal });
        if (response.status >= 500) throw new Error(`CentralBank HTTP ${response.status}`);
        this.failures = 0;
        this.openedAt = 0;
        return response;
      } catch (error) {
        lastError = error;
        if (attempt < 2) await new Promise((resolve) => setTimeout(resolve, 100 * (2 ** attempt)));
      } finally {
        clearTimeout(timeout);
      }
    }
    this.failures += 1;
    if (this.failures >= this.failureThreshold) this.openedAt = Date.now();
    throw lastError;
  }

  static async lookupPhone(phone: string) {
    const res = await this.request(`${this.baseUrl}/api/v1/internal/users/by-phone/${encodeURIComponent(phone)}`, {
      headers: this.headers,
    });
    if (res.status === 404) return null;
    if (!res.ok) throw new Error(`CentralBank lookup failed: ${res.status}`);
    const body = await res.json();
    return body.data ?? body;
  }

  static async createNotification(payload: any) {
    const res = await this.request(`${this.baseUrl}/api/v1/internal/notifications`, {
      method: 'POST',
      headers: this.headers,
      body: JSON.stringify(payload),
    });
    if (!res.ok) throw new Error(`CentralBank notification failed: ${res.status}`);
    return res.json();
  }

  static async settlePayment(payload: any, idempotencyKey: string, delegatedUserId: string, serviceName: string) {
    const res = await this.request(`${this.baseUrl}/api/v1/internal/payment-requests/settle`, {
      method: 'POST',
      headers: {
        ...this.headers,
        'Idempotency-Key': idempotencyKey,
        'X-Delegated-User-Id': delegatedUserId,
        'X-Service-Name': serviceName,
      },
      body: JSON.stringify(payload),
    });
    
    const body = await res.json();
    return { status: res.status, body };
  }

  static async reversePayment(originalTransactionId: string, reasonCode: string, idempotencyKey: string, delegatedUserId: string, serviceName: string) {
    const res = await this.request(`${this.baseUrl}/api/v1/internal/payment-requests/reverse`, {
      method: 'POST',
      headers: {
        ...this.headers,
        'Idempotency-Key': idempotencyKey,
        'X-Delegated-User-Id': delegatedUserId,
        'X-Service-Name': serviceName,
      },
      body: JSON.stringify({ original_transaction_id: originalTransactionId, reason_code: reasonCode }),
    });

    const body = await res.json();
    return { status: res.status, body };
  }
}
