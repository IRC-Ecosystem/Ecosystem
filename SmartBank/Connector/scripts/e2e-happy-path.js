const crypto = require('crypto');

const gatewayUrl = (process.env.GATEWAY_URL || 'http://localhost:4000').replace(/\/$/, '');
const connectorUrl = (process.env.CONNECTOR_URL || 'http://localhost:5000').replace(/\/$/, '');
const connectorKey = process.env.CONNECTOR_API_KEY;

if (!connectorKey) {
  console.error('CONNECTOR_API_KEY wajib di-set ke API key service POS sebelum menjalankan E2E.');
  process.exit(1);
}

async function json(url, init = {}) {
  const response = await fetch(url, init);
  const body = await response.json().catch(() => ({}));
  if (!response.ok || body.success === false) {
    const error = new Error(`${response.status} ${JSON.stringify(body.error || body)}`);
    error.status = response.status;
    throw error;
  }
  return body.data ?? body;
}

const gateway = (path, method = 'GET', body, token, idempotencyKey) => json(`${gatewayUrl}${path}`, {
  method,
  headers: { 'Content-Type': 'application/json', ...(token ? { Authorization: `Bearer ${token}` } : {}), ...(method !== 'GET' ? { 'Idempotency-Key': idempotencyKey || crypto.randomUUID() } : {}) },
  ...(body ? { body: JSON.stringify(body) } : {}),
});

const connector = (path, method = 'GET', body, key = crypto.randomUUID()) => json(`${connectorUrl}${path}`, {
  method,
  headers: { Authorization: `Bearer ${connectorKey}`, 'Content-Type': 'application/json', ...(method !== 'GET' ? { 'Idempotency-Key': key } : {}) },
  ...(body ? { body: JSON.stringify(body) } : {}),
});

async function registerAndLogin(label, suffix) {
  const phone = `+628${String(suffix).slice(-9)}`;
  const email = `${label.toLowerCase().replace(/[^a-z0-9]+/g, '-')}.${suffix}@e2e.smartbank.local`;
  const registration = { name: label, email, phone, password: 'E2ePass123!', pin: '123456', role: 'RETAIL_CUSTOMER' };
  for (let attempt = 0; attempt < 3; attempt += 1) {
    try {
      await gateway('/api/wallet/v1/auth/register', 'POST', registration, undefined, `e2e-register-${suffix}`);
      break;
    } catch (error) {
      if (error.status < 500 || attempt === 2) throw error;
      await new Promise((resolve) => setTimeout(resolve, 1000 * (attempt + 1)));
    }
  }
  let login;
  for (let attempt = 0; attempt < 3; attempt += 1) {
    try {
      login = await gateway('/api/wallet/v1/auth/login', 'POST', { email, password: 'E2ePass123!' });
      break;
    } catch (error) {
      if (error.status < 500 || attempt === 2) throw error;
      await new Promise((resolve) => setTimeout(resolve, 1000 * (attempt + 1)));
    }
  }
  return { phone, token: login.accessToken ?? login.data?.accessToken, user: login.user ?? login.data?.user };
}

async function link(user, externalId) {
  const otpRequest = await connector('/v1/connect/users/otp/request', 'POST', { phone: user.phone, purpose: 'WALLET_LINK' });
  const inbox = await gateway('/api/bank/users/me/notifications?limit=20&page=1', 'GET', undefined, user.token);
  const items = inbox.items ?? inbox.data?.items ?? [];
  const notification = items.find((item) => item.source_ref === otpRequest.request_id);
  if (!notification?.payload?.otp_code) throw new Error('OTP tidak ditemukan di inbox SmartBank');
  const verified = await connector('/v1/connect/users/otp/verify', 'POST', { request_id: otpRequest.request_id, code: notification.payload.otp_code });
  return connector('/v1/connect/users/link', 'POST', { external_user_id: externalId, verification_token: verified.verification_token });
}

async function main() {
  const suffix = Date.now();
  const buyer = await registerAndLogin('E2E Buyer', suffix);
  const seller = await registerAndLogin('E2E Seller', suffix + 1);
  const buyerExternalId = `pos-e2e-buyer-${suffix}`;
  const sellerExternalId = `pos-e2e-seller-${suffix}`;
  const externalRefId = `e2e-${suffix}`;
  const paymentKey = `e2e-payment-${suffix}`;
  const refundKey = `e2e-refund-${suffix}`;
  await link(buyer, buyerExternalId);
  await link(seller, sellerExternalId);
  const paymentBody = {
    buyer_external_id: buyerExternalId,
    seller_external_id: sellerExternalId,
    gross_amount: '1000',
    pin: '123456',
    description: 'Connector E2E happy path',
    external_ref_id: externalRefId,
  };
  const payment = await connector('/v1/connect/payment-requests', 'POST', paymentBody, paymentKey);
  if (payment.status !== 'SETTLED') throw new Error(`Payment tidak SETTLED: ${JSON.stringify(payment)}`);
  const paymentReplay = await connector('/v1/connect/payment-requests', 'POST', paymentBody, paymentKey);
  if (paymentReplay.transaction_id !== payment.transaction_id) throw new Error('Replay payment membuat transaksi baru');

  const refundBody = { external_ref_id: externalRefId, reason_code: 'E2E_VERIFIED_REFUND' };
  const refund = await connector('/v1/connect/payment-requests/refunds', 'POST', refundBody, refundKey);
  if (refund.status !== 'SETTLED' || !refund.reversal_transaction_id) throw new Error(`Refund tidak SETTLED: ${JSON.stringify(refund)}`);
  const refundReplay = await connector('/v1/connect/payment-requests/refunds', 'POST', refundBody, refundKey);
  if (refundReplay.reversal_transaction_id !== refund.reversal_transaction_id) throw new Error('Replay refund membuat reversal baru');

  console.log(JSON.stringify({
    ok: true,
    external_ref_id: externalRefId,
    payment_request_id: payment.payment_request_id,
    transaction_id: payment.transaction_id,
    reversal_transaction_id: refund.reversal_transaction_id,
  }));
}

main().catch((error) => { console.error(error); process.exit(1); });
