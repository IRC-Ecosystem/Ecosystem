import crypto from 'node:crypto';
import { config } from '../config/config.js';
import { CustomError } from '../utils/errors.js';

async function request(path, { method = 'GET', token, service = false, body, idempotencyKey } = {}) {
  const controller = new AbortController();
  const timeout = setTimeout(() => controller.abort(), 15000);
  try {
    const response = await fetch(`${config.centralBank.url}/api/v1${path}`, {
      method, signal: controller.signal,
      headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${service ? config.centralBank.serviceToken : token}`, 'X-Service-Name': 'WalletApp', 'X-Request-Id': `wallet_${crypto.randomUUID()}`, ...(idempotencyKey ? { 'Idempotency-Key': idempotencyKey } : {}) },
      ...(body === undefined ? {} : { body: JSON.stringify(body) }),
    });
    const envelope = await response.json().catch(() => ({}));
    if (!response.ok) throw new CustomError(envelope.error?.code || 'UPSTREAM_ERROR', envelope.error?.message || `Central Bank HTTP ${response.status}`, response.status);
    return envelope.data ?? envelope;
  } catch (error) {
    if (error instanceof CustomError) throw error;
    throw new CustomError('CENTRAL_BANK_UNAVAILABLE', error.name === 'AbortError' ? 'Central Bank timeout' : 'Central Bank tidak dapat dijangkau', 503);
  } finally { clearTimeout(timeout); }
}
const integer = (value) => Number.parseInt(String(value ?? 0), 10);

export const centralBankService = {
  async createAccount(name, email, phone, password, pin) { const data = await request('/auth/register', { method: 'POST', body: { name, email, phone, password, pin: String(pin) }, idempotencyKey: `cb_reg_${email.replace(/[@.]/g, '_')}` }); return { user_id: data.user_id, wallet_id: data.wallet_id, initial_balance: integer(data.initial_distribution?.initial_balance) }; },
  async listMyLoans(_walletId, token) { const list = await request('/loans/me', { token }); return (Array.isArray(list) ? list : []).map((loan) => ({ ...loan, principal: integer(loan.principal), interest_amount: integer(loan.interest_amount), total_due: integer(loan.total_due), paid_amount: integer(loan.paid_amount), remaining: integer(loan.remaining) })); },
  async getLoanLimit(token) { const data = await request('/loans/me/limit', { token }); return { cap: integer(data.cap), outstanding: integer(data.outstanding), remaining: integer(data.remaining) }; },
  async getWalletByAccountNumber(accountNumber) { return request(`/users/by-account/${encodeURIComponent(accountNumber)}`, { service: true }); },
  async getWalletByUserId(userId) { const data = await request(`/users/${encodeURIComponent(userId)}/wallet`, { service: true }); return { ...data, available_balance: integer(data.available_balance), hold_balance: integer(data.hold_balance) }; },
  async getBalance(_walletId, token) { const data = await request('/wallets/me/balance', { token }); return { ...data, available_balance: integer(data.available_balance), hold_balance: integer(data.hold_balance) }; },
  async getTransactions(_walletId, token) { const data = await request('/wallets/me/transactions', { token }); return (Array.isArray(data) ? data : []).map((transaction) => ({ ...transaction, gross_amount: integer(transaction.gross_amount), total_debit: integer(transaction.total_debit), fee_total: integer(transaction.fee_total), tax_total: integer(transaction.tax_total) })); },
  async transfer(_fromWalletId, toWalletId, amount, note = '', idempotencyKey, token) { const data = await request('/transfers', { method: 'POST', token, idempotencyKey: idempotencyKey || `wallet-transfer-${crypto.randomUUID()}`, body: { to_wallet_id: toWalletId, amount: String(amount), note } }); return { id: data.transaction_id, transaction_type: 'TRANSFER', status: data.status, gross_amount: integer(data.amount), total_debit: integer(data.total_debit), fee_total: integer(data.fee_total), tax_total: integer(data.tax_total), note, created_at: data.created_at || new Date().toISOString() }; },
  async payPaymentRequest(paymentRequestId, _payerWalletId, token) { return request(`/payment-requests/${encodeURIComponent(paymentRequestId)}/pay`, { method: 'POST', token, idempotencyKey: `wallet-payment-${paymentRequestId}` }); },
  async applyLoan(_walletId, amount, token) { return request('/loans/apply', { method: 'POST', token, idempotencyKey: `wallet-loan-${crypto.randomUUID()}`, body: { amount: String(amount) } }); },
  async repayLoan(loanId, _walletId, amount, token) { return request(`/loans/${encodeURIComponent(loanId)}/repay`, { method: 'POST', token, idempotencyKey: `wallet-repay-${loanId}-${crypto.randomUUID()}`, body: { amount: String(amount) } }); },
  async subscribeInsight(walletId, token) { const payeeWalletId = process.env.UMKM_INSIGHT_WALLET_ID || ''; if (!payeeWalletId) throw new CustomError('SERVICE_NOT_CONFIGURED', 'Wallet penerima UMKM Insight belum dikonfigurasi', 503); return this.transfer(walletId, payeeWalletId, 10000, 'Berlangganan Premium UMKM Insight', `wallet-insight-${crypto.randomUUID()}`, token); },
  async requestUpgrade(role, businessName, nik, token) { return request('/wallets/me/upgrade-request', { method: 'POST', token, body: { role, businessName, nik } }); },
  async topUp() { throw new CustomError('FORBIDDEN', 'Top up hanya dapat diproses Teller', 403); },
  async withdraw() { throw new CustomError('FORBIDDEN', 'Penarikan hanya dapat diproses Teller', 403); },
  async claimStimulus() { throw new CustomError('FORBIDDEN', 'Stimulus hanya dapat diproses Central Bank', 403); },
};
