# Connector Security Audit

Date: 2026-07-20

## Implemented controls

- Sister apps authenticate with bcrypt-hashed, rotatable API keys.
- Admin routes use a separate constant-time-compared admin key.
- Every mutating Connector endpoint requires an idempotency key and rejects payload reuse conflicts.
- OTP codes use cryptographically secure random generation, are bcrypt-hashed, expire after five minutes, and block after three failed attempts.
- Service and endpoint rate limits protect OTP, linkage, and payment flows.
- CentralBank calls use timeouts, bounded retries, and a circuit breaker.
- Payments are delegated to CentralBank for wallet ownership, PIN validation, atomic settlement, and double-entry ledger posting.
- Connector audit entries form an HMAC-SHA256 chain using a dedicated secret.
- API error responses do not include PINs, API keys, service tokens, or OTP hashes.

## Residual risks and deployment requirements

- TLS is mandatory outside local development because the transaction PIN crosses POS, Connector, and CentralBank in request memory.
- The in-memory rate limiter is per Connector instance. A multi-replica deployment must move counters to Redis or another shared store.
- Secrets must come from a secret manager in production and must not use `.env.example` placeholder values.
- Run `GET /v1/connect/admin/audit/verify` regularly and alert when the chain is invalid.
- Run the k6 profile in `load/k6-100rps.js` against staging; do not load-test production without an approved window.
- Conduct dependency scanning and an external penetration test before production use.
- POS and SmartBank cannot share one database transaction. POS records the successful settlement before local stock finalization and reuses the invoice idempotency key on retry; production operations still need reconciliation/alerting for settlements whose POS finalization remains pending.
