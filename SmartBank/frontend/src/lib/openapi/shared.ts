export const apiResponse = {
  type: "object",
  properties: {
    success: { type: "boolean", example: true },
    data: { nullable: true },
    error: {
      nullable: true,
      type: "object",
      properties: {
        code: { type: "string" },
        message: { type: "string" },
        details: { type: "object", additionalProperties: true },
      },
    },
    meta: {
      type: "object",
      properties: {
        request_id: { type: "string" },
        timestamp: { type: "string", format: "date-time" },
      },
    },
  },
};

export const standardResponses = {
  "200": { description: "Permintaan berhasil" },
  "400": { description: "Request tidak valid" },
  "401": { description: "Autentikasi tidak valid atau tidak disertakan" },
  "403": { description: "Akses ditolak untuk role/layanan ini" },
  "404": { description: "Resource tidak ditemukan" },
  "409": { description: "Konflik atau idempotency key telah digunakan" },
  "429": { description: "Rate limit terlampaui" },
  "500": { description: "Kesalahan internal server" },
};

export const bearerAuth = {
  type: "http",
  scheme: "bearer",
  bearerFormat: "JWT",
  description: "JWT access token dari endpoint login",
};

export const idempotencyKey = {
  type: "apiKey",
  in: "header",
  name: "Idempotency-Key",
  description: "Kunci unik untuk mencegah transaksi finansial diproses dua kali",
};

export const jsonBody = (schema: object, required = true) => ({
  required,
  content: { "application/json": { schema } },
});

export const ref = (name: string) => ({ $ref: `#/components/schemas/${name}` });
