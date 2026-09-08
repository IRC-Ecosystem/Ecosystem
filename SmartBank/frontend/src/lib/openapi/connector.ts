import { bearerAuth, idempotencyKey, jsonBody, ref, standardResponses } from "./shared";

const serverUrl = process.env.NEXT_PUBLIC_CONNECTOR_URL || "http://localhost:5000";
const secured = [{ apiKey: [] }];
const mutating = [{ apiKey: [], idempotencyKey: [] }];
const op = (tag: string, summary: string, options: Record<string, unknown> = {}) => ({ tags: [tag], summary, security: secured, responses: standardResponses, ...options });

export const connectorSpec = {
  openapi: "3.1.0",
  info: {
    title: "SmartBank Connector API",
    version: "1.0.0",
    description: "API integrasi aplikasi eksternal untuk linkage SmartBank Wallet, OTP, dan settlement pembayaran. Authorization menggunakan API key layanan minimal 32 karakter.",
  },
  servers: [{ url: serverUrl, description: "Connector Service" }],
  tags: [{ name: "Health" }, { name: "User Linkage" }, { name: "Payments" }, { name: "Admin" }],
  components: {
    securitySchemes: {
      apiKey: { ...bearerAuth, bearerFormat: "Service API Key", description: "API key layanan: Bearer <service-api-key>" },
      idempotencyKey: { ...idempotencyKey, name: "X-Idempotency-Key" },
      adminKey: { type: "apiKey", in: "header", name: "X-Admin-Api-Key" },
    },
    schemas: {
      OtpRequest: { type: "object", required: ["phone"], properties: { phone: { type: "string", example: "081234567890" } } },
      OtpVerify: { type: "object", required: ["request_id", "code"], properties: { request_id: { type: "string", example: "otpreq_..." }, code: { type: "string", pattern: "^[0-9]{6}$" } } },
      LinkUser: { type: "object", required: ["external_user_id", "verification_token"], properties: { external_user_id: { type: "string" }, verification_token: { type: "string", description: "Token sementara hasil verifikasi OTP" } } },
      Payment: { type: "object", required: ["buyer_external_id", "seller_external_id", "gross_amount", "pin", "external_ref_id"], properties: { buyer_external_id: { type: "string" }, seller_external_id: { type: "string" }, gross_amount: { type: "string", pattern: "^[0-9]+$", example: "25000" }, pin: { type: "string", pattern: "^[0-9]{6}$" }, description: { type: "string" }, external_ref_id: { type: "string" } } },
    },
  },
  paths: {
    "/health": { get: op("Health", "Status proses Connector", { security: [] }) },
    "/ready": { get: op("Health", "Readiness database Connector", { security: [] }) },
    "/v1/connect/users/otp/request": { post: op("User Linkage", "Kirim OTP linkage ke inbox SmartBank", { security: mutating, requestBody: jsonBody(ref("OtpRequest")) }) },
    "/v1/connect/users/otp/verify": { post: op("User Linkage", "Verifikasi OTP linkage", { security: mutating, requestBody: jsonBody(ref("OtpVerify")) }) },
    "/v1/connect/users/link": { post: op("User Linkage", "Hubungkan user eksternal ke SmartBank Wallet", { security: mutating, requestBody: jsonBody(ref("LinkUser")) }) },
    "/v1/connect/users/{external_user_id}": {
      get: op("User Linkage", "Ambil linkage user eksternal", { parameters: [{ name: "external_user_id", in: "path", required: true, schema: { type: "string" } }] }),
      delete: op("User Linkage", "Putuskan linkage user eksternal", { security: mutating, parameters: [{ name: "external_user_id", in: "path", required: true, schema: { type: "string" } }] }),
    },
    "/v1/connect/payment-requests": { post: op("Payments", "Proses pembayaran antarpengguna tertaut", { security: [{ apiKey: [], idempotencyKey: [] }], requestBody: jsonBody(ref("Payment")) }) },
    "/v1/connect/admin/services": {
      get: op("Admin", "Daftar sister app", { security: [{ adminKey: [] }] }),
      post: op("Admin", "Daftarkan sister app dan terbitkan API key", { security: [{ adminKey: [], idempotencyKey: [] }] }),
    },
    "/v1/connect/admin/audit": { get: op("Admin", "Query audit Connector", { security: [{ adminKey: [] }] }) },
    "/v1/connect/admin/audit/verify": { get: op("Admin", "Verifikasi HMAC chain audit", { security: [{ adminKey: [] }] }) },
  },
};
