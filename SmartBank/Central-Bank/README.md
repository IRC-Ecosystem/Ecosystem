# Central Bank Core CBDC Simulation

Academic Central Bank Core simulation for CBDC settlement. This project is not connected to Bank Indonesia, BCA, QRIS, real banks, real payment gateways, or production payment networks.

## Architecture

The backend is a NestJS modular monolith using MySQL 8.x/InnoDB and Prisma. Central Bank Core is the only module that mutates balances. Wallet/provider layers may create requests, but final validation, settlement, ledger posting, fee/tax accounting, audit logging, reversal, and supply reporting are centralized in `SettlementService`.

The repository also includes a React/Vite frontend test client in `frontend/`. It is a user-facing wallet/testing surface only: it never mutates balances locally and always calls the Central Bank Core API.

Important choices:
- IDs use readable UUID strings stored as `CHAR(36)`.
- Money is stored as integer minor units in MySQL `BIGINT`.
- TypeScript financial logic uses `bigint`.
- Amounts are returned as strings by the response interceptor to avoid JSON precision loss.
- Settlement locks wallet account rows with `SELECT ... FOR UPDATE` in deterministic account id order.
- Deadlock/lock retry is capped at 3 attempts.
- Ledger entries are append-only. Corrections use `REVERSAL`.
- The design uses programmable payments, not programmable retail money.
- Loan disbursement uses `LOAN_POOL_ACCOUNT`, seeded from reserve with ledger entries.
- Logistics fee uses flat `5_000`.

## Financial Invariants

- No balance mutation outside `SettlementService`.
- No settled transaction without at least two ledger entries.
- Ledger total `DEBIT` must equal total `CREDIT`.
- Every ledger amount must be greater than zero.
- Every financial POST requires `Idempotency-Key`.
- Same idempotency key + route + actor + request body returns the same response.
- Same idempotency key with a different body returns `IDEMPOTENCY_CONFLICT`.
- `total_supply = reserve_balance + circulating_supply + sink_or_burn_accounting`.
- Total supply must not exceed `1_000_000_000`.
- Initial distribution cap is `2%` of total supply.

## Main Folder Structure

```text
frontend/
  src/
    App.tsx               wallet testing UI and workflows
    api.ts                API envelope, JWT, request/idempotency helpers
    styles.css            responsive financial operations UI
src/
  common/                 response envelope, auth guard, errors, request helpers
  modules/
    audit/                audit log writer
    auth/                 register/login and initial wallet creation
    central-bank/         supply, ledger query, reversal endpoint
    fees/                 fee quote from fee rules
    health/               health endpoint
    idempotency/          idempotency key persistence
    ledger/               double-entry validation and posting
    loans/                loan API facade
    money/                bigint money helper
    payment-requests/     payment request lifecycle
    prisma/               Prisma client service
    settlement/           atomic balance mutation engine
    wallets/              balance and transaction reads
prisma/
  schema.prisma
  migrations/20260601160000_init/migration.sql
  seed.ts
test/
```

## Endpoints

- `GET /api/v1/health`
- `POST /api/v1/auth/register`
- `POST /api/v1/auth/login`
- `GET /api/v1/wallets/me/balance`
- `GET /api/v1/wallets/me/transactions`
- `POST /api/v1/fees/quote`
- `POST /api/v1/transfers`
- `POST /api/v1/payment-requests`
- `POST /api/v1/payment-requests/:id/pay`
- `POST /api/v1/loans/apply`
- `POST /api/v1/loans/:id/repay`
- `GET /api/v1/central-bank/supply`
- `GET /api/v1/central-bank/ledger`
- `POST /api/v1/central-bank/reversals`

Financial POST endpoints require:

```text
Authorization: Bearer <token>
X-Request-Id: <uuid/string>
Idempotency-Key: <unique-key>
```

`POST /api/v1/auth/register` also requires `Idempotency-Key` because it creates an initial distribution transaction.

## Frontend Test Client

The frontend provides:

- Register and login.
- Wallet balance and transaction history.
- Transfer with visible `Idempotency-Key`.
- Fee quote for transfer.
- Payment request creation and payment.
- Loan apply and repay.
- Central bank supply read.
- Ledger query.
- Response console showing raw API envelopes and errors.

Every financial action sends:

- `Authorization`
- `X-Request-Id`
- `Idempotency-Key`

The UI is intentionally a compact operational testing surface, not a production wallet app or central-bank dashboard.

## Database Schema

Migration created:

```text
prisma/migrations/20260601160000_init/migration.sql
```

Tables:

- `users`
- `wallet_accounts`
- `monetary_policy_events`
- `transactions`
- `ledger_entries`
- `payment_requests`
- `fee_rules`
- `loans`
- `audit_logs`
- `idempotency_keys`

Seed creates system accounts:

- `CENTRAL_RESERVE`
- `ISSUANCE_ACCOUNT`
- `FEE_BANK`
- `FEE_GATEWAY`
- `FEE_MARKETPLACE`
- `FEE_POS`
- `FEE_SUPPLIER`
- `FEE_LOGISTICS`
- `TAX_SINK`
- `LOAN_POOL_ACCOUNT`
- `BURN_OR_SINK_ACCOUNT`
- `CLEARING_ACCOUNT`

Seed creates fee rules:

- Bank fee `100 bps`
- Gateway fee `50 bps`
- System tax `200 bps`
- Marketplace fee `200 bps`
- POS fee `100 bps`
- Supplier fee `300 bps`
- Logistics fee flat `5_000`

## Local Setup

Install dependencies:

```bash
pnpm install
```

Run MySQL:

```bash
docker compose up -d mysql
```

If Docker Hub times out while pulling MySQL, retry after the network stabilizes:

```bash
docker pull mysql:8.0
docker compose up -d mysql
```

The compose file uses `mysql:8.0`, which satisfies the MySQL 8.x requirement. A `TLS handshake timeout` is a Docker Hub/network pull issue, not a database schema or application error.

Copy env:

```bash
cp .env.example .env
```

Generate Prisma client:

```bash
pnpm exec prisma generate
```

Run migration:

```bash
pnpm exec prisma migrate dev
```

If `migrate dev` returns `P3014` because Prisma cannot create a shadow database, grant local development privileges to the compose user:

```bash
docker exec central-bank-core-mysql mysql -uroot -proot_password -e "GRANT ALL PRIVILEGES ON *.* TO 'central_bank'@'%' WITH GRANT OPTION; FLUSH PRIVILEGES;"
pnpm exec prisma migrate dev
```

This broad grant is intended for the local Docker database only. A production-like deployment should use a narrower migration user or `prisma migrate deploy`.

Run seed:

```bash
pnpm prisma:seed
```

Run project:

```bash
pnpm start:dev
```

Run the frontend test client in another terminal:

```bash
cp frontend/.env.example frontend/.env
pnpm frontend:dev
```

Open:

```text
http://localhost:5173
```

The frontend calls `http://localhost:3000/api/v1` by default. Override it with `VITE_API_BASE_URL` from `frontend/.env` if needed.

Run tests:

```bash
pnpm test
```

Build frontend:

```bash
pnpm frontend:build
```

Validate Prisma schema:

```bash
$env:DATABASE_URL="mysql://central_bank:central_bank_password@localhost:3306/central_bank_core"; pnpm exec prisma validate
```

## Prisma Migration Guide

Central-Bank uses **Prisma 6.1.0** with MySQL 8.x for schema, migrations, and seeding. Wallet/Gateway do not use Prisma (raw `mysql2` / no DB).

### Schema & Migrations Overview

```text
Central-Bank/prisma/
├── schema.prisma                  # datasource: mysql, generator: prisma-client-js
├── seed.ts                        # idempotent system account seeder
└── migrations/                    # 11 migrations, chronological
    ├── 20260601160000_init
    ├── 20260609120000_add_topup_withdrawal_transaction_types
    ├── 20260609153000_add_updated_at_defaults
    ├── 20260609154500_add_staff_user_roles
    ├── 20260610152000_add_pending_loan_status
    ├── 20260611071000_add_kyc_identity_documents
    ├── 20260617090000_add_user_pending_role
    ├── 20260618090000_add_phone_pin_hash
    ├── 20260618150000_add_account_number
    ├── 20260619100000_add_loan_recommendation
    └── 20260620120000_add_issuance_burn_transaction_types
```

### Local Development (Laragon / host MySQL)

```bash
# 1. Generate Prisma client (idempotent, run after every schema/migration change)
pnpm exec prisma generate

# 2. Apply migrations + create new migration file from schema diff
pnpm exec prisma migrate dev

# 3. (Optional) Reset database — drops all data, re-applies all migrations + seed
pnpm exec prisma migrate reset

# 4. Show migration status
pnpm exec prisma migrate status

# 5. Seed system accounts (CENTRAL_RESERVE, ISSUANCE_ACCOUNT, fee rules, etc.)
pnpm prisma:seed

# Or one-shot via npm scripts
pnpm prisma:generate
pnpm prisma:migrate
pnpm prisma:seed
```

If `migrate dev` returns `P3014` (shadow database permission error), grant privileges:

```bash
docker exec -it <mysql-container> mysql -uroot -p<password> -e \
  "GRANT ALL PRIVILEGES ON *.* TO 'central_bank'@'%' WITH GRANT OPTION; FLUSH PRIVILEGES;"
pnpm exec prisma migrate dev
```

### Docker / Production (in-container)

The `Central-Bank/Dockerfile` runs migrations + seed + app as a single CMD chain (line 38):

```dockerfile
CMD ["sh", "-c", "./node_modules/.bin/prisma migrate deploy \
  && node dist-seed/seed.js \
  && exec node dist/main.js"]
```

- **`migrate deploy`** = production-safe, applies pending migrations, no schema diff
- **Build stage** runs `npx prisma generate` once before `npm run build` (client is baked into image)
- **First boot** = all 11 migrations applied in order
- **Subsequent boots** = `migrate deploy` is a no-op (no pending migrations)

```bash
# Watch migration logs during container start
docker compose logs -f central-bank | grep -E "migrations|seed|started"

# Expected first-boot output:
#   prisma:migrate  11 migrations found
#   prisma:migrate  All migrations applied
#   Seed: 12 system accounts upserted
#   Seed: fee rules created
#   Application listening on port 3000
```

### Seed Idempotency

`seed.ts` uses `prisma.walletAccount.upsert` for system accounts (idempotent on restart). However, `seedLoanPoolFunding` and `seedMonetaryPolicyEvent` use `prisma.X.create` guarded by `findFirst` — restart-safe. `upsertFeeRules` is **not** yet fully idempotent and will fail with `ER_DUP_ENTRY` on second container restart. Fix in progress.

If a seed error occurs, run manually inside container:

```bash
docker compose exec central-bank node dist-seed/seed.js
# Or reset and reseed (CAUTION: drops all data)
docker compose exec central-bank npx prisma migrate reset --force
```

### Common Commands

| Task | Command |
|---|---|
| Validate schema syntax | `pnpm exec prisma validate` |
| Format schema file | `pnpm exec prisma format` |
| View current DB schema | `pnpm exec prisma db pull` (regenerates from DB) |
| Generate client only | `pnpm exec prisma generate` |
| Open Prisma Studio (GUI) | `pnpm exec prisma studio` |
| Create empty migration | `pnpm exec prisma migrate dev --create-only --name <name>` |
| Apply pending (prod) | `pnpm exec prisma migrate deploy` |
| Mark failed migration resolved | `pnpm exec prisma migrate resolve --applied <name>` |

### Troubleshooting

| Error | Penyebab | Fix |
|---|---|---|
| `P1001: Can't reach database` | MySQL not running / wrong host | Cek `docker compose ps mysql`, verify `DATABASE_URL` |
| `P3009: migrate found failed migrations` | Previous migration crashed mid-way | `prisma migrate resolve --rolled-back <name>` lalu re-apply |
| `P3014: Prisma can't create shadow DB` | User lacks CREATE privilege | Grant `ALL PRIVILEGES` untuk dev user (lihat atas) |
| `ER_DUP_ENTRY` on container restart | Seed `create` not guarded by `findFirst` | Manual `migrate reset --force` (data loss) atau perbaiki seed.ts |
| Port 3301 vs 3306 mismatch | Compose pakai 3301, MySQL default 3306 | Tambah `--port=3301` di MySQL `command` (lihat `DOCKER_SETUP.md`) |

---

## Verification Run

Last local verification:

```text
pnpm exec prisma generate  -> passed
pnpm run build             -> passed
pnpm frontend:build        -> passed
pnpm test                  -> 6 suites passed, 11 tests passed
pnpm exec prisma validate  -> passed with DATABASE_URL set
```

## Technical Trade-Offs

- This is a foundation, not a full production banking system.
- Tests currently cover core ledger/money primitives, fee quote, idempotency, deadlock retry, and invariant documentation. Full MySQL integration tests should be added next.
- Register creates user/wallet first, then calls settlement for initial distribution. The financial balance mutation itself remains atomic inside settlement.
- JWT auth is development-grade and does not include refresh token rotation.
- Authorization for central-bank endpoints is authenticated-only in this phase; role enforcement should be hardened.
- Audit metadata is intentionally small and avoids sensitive fields.
- No programmable money conditions are attached to units of CBDC.

## Next Steps

1. Add MySQL integration tests for register, transfer, idempotency replay/conflict, payment request, loan, reversal, and concurrency.
2. Add role guards for auditor/admin/operator access.
3. Add OpenAPI documentation and request examples.
4. Add richer audit metadata, structured application logs, and request correlation.
5. Add CI workflow running Prisma validate, build, and test.
6. Add read-model pagination for ledger and transaction history.
