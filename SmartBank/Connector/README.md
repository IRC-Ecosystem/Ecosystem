# SmartBank Connector

This is the API Connector service for sister applications to connect to the SmartBank CBDC ecosystem.

## Stack
- Node.js 20
- Express
- Prisma
- MySQL

## Setup

Panduan lengkap lokal, Docker, provisioning API key, linkage, dan troubleshooting tersedia di [CONNECTOR_SETUP.md](./CONNECTOR_SETUP.md).

## Local setup (Terminal + Laragon)

1. Start MySQL from Laragon (default `127.0.0.1:3306`).
2. Ensure the global `../.env` defines matching `SERVICE_TOKEN` and
   `JWT_SECRET` values for Connector and Central Bank.
3. Copy `.env.example` to `.env.local` when local overrides are needed. The
   local configuration reuses `MYSQL_USER` and `MYSQL_PASSWORD` from the
   global environment file.
4. Create the Connector database and grant the global MySQL user access:

   ```bash
   npm run db:setup
   ```

   Alternatively, create only the database from the Laragon MySQL console:

   ```sql
   CREATE DATABASE IF NOT EXISTS connector_db
     CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

5. Install dependencies and prepare Prisma:

   ```bash
   npm install
   npm run prisma:generate
   npm run prisma:push
   ```

6. Create a local sister app API key for POS testing:

   ```bash
   npm run seed:service-key -- POS "WarungPOS"
   ```

   Use the printed API key as `Authorization: Bearer <api-key>` from the
   sister app simulator. The key is shown once and only its bcrypt hash is
   stored in `connector_db`.

7. Make sure Central Bank is available at `http://127.0.0.1:3000`, then run:

   ```bash
   npm run dev
   ```

Connector runs at `http://127.0.0.1:5000`. Set `CONNECTOR_DATABASE_URL` in
`.env.local` only when a full URL must override the global MySQL credentials.

Environment loading order is global `../.env`, Connector `.env`, then
Connector `.env.local`. Docker-injected process variables remain supported;
`.env.local` is intended only for local development.

Docker runs `prisma db push` before starting Connector, so `connector_db` is prepared automatically after MySQL becomes healthy.

## Admin and security

- Admin API: `/v1/connect/admin` using `X-Admin-Api-Key`.
- Register service: `POST /v1/connect/admin/services`.
- Rotate/revoke keys: `POST` or `DELETE /v1/connect/admin/services/{id}/keys/{keyId}`.
- Query audit: `GET /v1/connect/admin/audit`.
- Verify HMAC audit chain: `GET /v1/connect/admin/audit/verify`.
- Set a unique 32+ character `CONNECTOR_AUDIT_HMAC_SECRET`; do not reuse `JWT_SECRET`.

## Testing

- `npm test`: build + unit tests.
- `npm run test:e2e`: live SmartBank register → OTP inbox → link → PIN payment flow. Set `CONNECTOR_API_KEY` first.
- `npm run test:load`: k6 100 RPS profile; run only against an approved staging environment.
