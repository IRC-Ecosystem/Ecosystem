import { bearerAuth, idempotencyKey, jsonBody, ref, standardResponses } from "./shared";

const serverUrl = process.env.NEXT_PUBLIC_WALLET_URL || "http://localhost:6969";
const auth = [{ bearerAuth: [] }];
const secured = (pin = false, idempotent = false) => [{ bearerAuth: [], ...(pin ? { walletPin: [] } : {}), ...(idempotent ? { idempotencyKey: [] } : {}) }];
const op = (tag: string, summary: string, options: Record<string, unknown> = {}) => ({ tags: [tag], summary, security: auth, responses: standardResponses, ...options });
const bodyOp = (tag: string, summary: string, schema: string, pin = false, idempotent = false) => op(tag, summary, { security: secured(pin, idempotent), requestBody: jsonBody(ref(schema)) });

export const walletSpec = {
  openapi: "3.1.0",
  info: { title: "SmartBank Wallet API", version: "1.0.0", description: "API E-Wallet Tier-2. Endpoint finansial tertentu membutuhkan PIN 6 digit dan Idempotency-Key. Semua nominal adalah integer minor-unit." },
  servers: [{ url: serverUrl, description: "Wallet Service" }],
  components: {
    securitySchemes: { bearerAuth, walletPin: { type: "apiKey", in: "header", name: "X-Wallet-Pin" }, idempotencyKey },
    schemas: {
      Register: { type: "object", required: ["name", "email", "phone", "password", "pin"], properties: { name: { type: "string" }, email: { type: "string", format: "email" }, phone: { type: "string" }, password: { type: "string", minLength: 8 }, pin: { type: "string", pattern: "^[0-9]{6}$" }, role: { type: "string" } } },
      Login: { type: "object", required: ["email", "password"], properties: { email: { type: "string", format: "email" }, password: { type: "string", format: "password" } } },
      Amount: { type: "object", required: ["amount"], properties: { amount: { type: "integer", minimum: 1, example: 50000 } } },
      Profile: { type: "object", properties: { name: { type: "string" }, phone: { type: "string" } } },
      Security: { type: "object", properties: { password: { type: "string", minLength: 8 }, pin: { type: "string", pattern: "^[0-9]{6}$" } } },
      Upgrade: { type: "object", required: ["role", "businessName", "nik"], properties: { role: { type: "string", enum: ["MERCHANT", "SUPPLIER", "ANALYTICS_VIEWER"] }, businessName: { type: "string" }, nik: { type: "string", pattern: "^[0-9]{16}$" } } },
      KycDocument: { type: "object", required: ["documentType", "documentNumber", "documentName", "documentDataUrl"], properties: { documentType: { type: "string", enum: ["KTP", "SIM", "PASSPORT"] }, documentNumber: { type: "string" }, documentName: { type: "string" }, documentDataUrl: { type: "string", description: "PNG/JPG/WEBP/PDF data URL" } } },
      Transfer: { type: "object", required: ["amount"], properties: { to_wallet_id: { type: "string", format: "uuid" }, to_account_number: { type: "string", pattern: "^[0-9]{10}$" }, amount: { type: "integer", minimum: 1 }, note: { type: "string" } } },
      PayInvoice: { type: "object", properties: { pin: { type: "string", pattern: "^[0-9]{6}$" } } },
    },
  },
  paths: {
    "/": { get: op("Health", "Informasi Wallet Service", { security: [] }) },
    "/api/v1/auth/register": { post: bodyOp("Autentikasi", "Registrasi pengguna wallet", "Register") },
    "/api/v1/auth/login": { post: bodyOp("Autentikasi", "Login pengguna wallet", "Login") },
    "/api/v1/wallets/me/balance": { get: op("Wallet", "Saldo wallet") },
    "/api/v1/wallets/me/transactions": { get: op("Wallet", "Riwayat transaksi") },
    "/api/v1/wallets/me/kyc-document": { get: op("Wallet", "Lihat dokumen KYC"), put: bodyOp("Wallet", "Unggah dokumen KYC", "KycDocument") },
    "/api/v1/wallets/lookup": { get: op("Transfer", "Cari penerima dari nomor rekening", { parameters: [{ name: "account_number", in: "query", required: true, schema: { type: "string", pattern: "^[0-9]{10}$" } }] }) },
    "/api/v1/wallets/me/topup": { post: bodyOp("Transaksi", "Top-up simulasi", "Amount", true) },
    "/api/v1/wallets/me/withdraw": { post: bodyOp("Transaksi", "Penarikan simulasi", "Amount", true) },
    "/api/v1/wallets/me/claim-stimulus": { post: op("Transaksi", "Klaim stimulus", { security: secured(true) }) },
    "/api/v1/wallets/me/profile": { put: bodyOp("Wallet", "Perbarui profil", "Profile") },
    "/api/v1/wallets/me/security": { put: bodyOp("Wallet", "Perbarui password atau PIN", "Security") },
    "/api/v1/wallets/me/upgrade": { put: bodyOp("Wallet", "Ajukan upgrade akun", "Upgrade") },
    "/api/v1/wallets/me/subscribe-insight": { post: op("Wallet", "Berlangganan UMKM Insight") },
    "/api/v1/transfers": { post: bodyOp("Transfer", "Transfer dana P2P", "Transfer", true, true) },
    "/api/v1/payment-requests/{id}/pay": { post: bodyOp("Payment", "Bayar invoice", "PayInvoice", true, true), parameters: [{ name: "id", in: "path", required: true, schema: { type: "string" } }] },
    "/api/v1/loans/me": { get: op("Kredit", "Daftar pinjaman pengguna") },
    "/api/v1/loans/apply": { post: bodyOp("Kredit", "Ajukan pinjaman", "Amount", false, true) },
    "/api/v1/loans/{loan_id}/repay": { post: bodyOp("Kredit", "Bayar cicilan", "Amount", false, true), parameters: [{ name: "loan_id", in: "path", required: true, schema: { type: "string" } }] },
  },
};
