import { bearerAuth, standardResponses } from "./shared";

const serverUrl = process.env.NEXT_PUBLIC_GATEWAY_URL || process.env.NEXT_PUBLIC_API_BASE_URL || "http://localhost:4000";

export const gatewaySpec = {
  openapi: "3.1.0",
  info: {
    title: "SmartBank API Gateway",
    version: "1.0.0",
    description: "Edge gateway untuk proxy Central Bank (`/api/bank/*`) dan Wallet (`/api/wallet/*`). Pilih dokumen Central Bank atau Wallet di dropdown atas untuk melihat semua endpoint konkret dan payloadnya; ubah prefix target menjadi prefix gateway saat menguji melalui edge.",
  },
  servers: [{ url: serverUrl, description: "API Gateway" }],
  components: { securitySchemes: { bearerAuth }, schemas: {} },
  paths: {
    "/health": { get: { tags: ["Gateway"], summary: "Status API Gateway", responses: standardResponses } },
    "/api/bank/{path}": {
      parameters: [{ name: "path", in: "path", required: true, description: "Path Central Bank setelah /api/v1/ (contoh: wallets/me/balance)", schema: { type: "string" } }],
      get: { tags: ["Central Bank Proxy"], summary: "Proxy GET ke Central Bank", security: [{ bearerAuth: [] }], responses: standardResponses },
      post: { tags: ["Central Bank Proxy"], summary: "Proxy POST ke Central Bank", security: [{ bearerAuth: [] }], responses: standardResponses },
      put: { tags: ["Central Bank Proxy"], summary: "Proxy PUT ke Central Bank", security: [{ bearerAuth: [] }], responses: standardResponses },
      patch: { tags: ["Central Bank Proxy"], summary: "Proxy PATCH ke Central Bank", security: [{ bearerAuth: [] }], responses: standardResponses },
      delete: { tags: ["Central Bank Proxy"], summary: "Proxy DELETE ke Central Bank", security: [{ bearerAuth: [] }], responses: standardResponses },
    },
    "/api/wallet/{path}": {
      parameters: [{ name: "path", in: "path", required: true, description: "Path Wallet setelah /api/ (contoh: v1/wallets/me/balance)", schema: { type: "string" } }],
      get: { tags: ["Wallet Proxy"], summary: "Proxy GET ke Wallet", security: [{ bearerAuth: [] }], responses: standardResponses },
      post: { tags: ["Wallet Proxy"], summary: "Proxy POST ke Wallet", security: [{ bearerAuth: [] }], responses: standardResponses },
      put: { tags: ["Wallet Proxy"], summary: "Proxy PUT ke Wallet", security: [{ bearerAuth: [] }], responses: standardResponses },
      patch: { tags: ["Wallet Proxy"], summary: "Proxy PATCH ke Wallet", security: [{ bearerAuth: [] }], responses: standardResponses },
      delete: { tags: ["Wallet Proxy"], summary: "Proxy DELETE ke Wallet", security: [{ bearerAuth: [] }], responses: standardResponses },
    },
  },
};
