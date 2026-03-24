# 🔐 Security Rules

Security is NON-NEGOTIABLE in a financially critical system. Every layer must be protected.

## Authentication

- **JWT Bearer Tokens** — all API requests must carry a valid token.
- Token expiry: **15 minutes** for access tokens; **7 days** for refresh tokens.
- Refresh tokens must be rotated on every use (rotation + revocation).
- Store refresh tokens in DB (hashed) to support revocation.
- On logout: revoke refresh token immediately.

## Authorization: Role-Based Access Control (RBAC)

### Roles
| Role | Description |
|------|-------------|
| `SUPER_ADMIN` | Full system access, multi-store management |
| `STORE_MANAGER` | Full access within their store(s) |
| `CASHIER` | POS billing only — no reports or settings |
| `ACCOUNTANT` | Accounting, reports — no POS |
| `AUDITOR` | Read-only access to all financial records |
| `RECOVERY_AGENT` | Recovery module only |

### Rules
- Every API route must declare its required role(s) via `@Roles(...)` decorator / middleware.
- A user can only access data belonging to their assigned store(s).
- Super-admin actions must be logged with enhanced detail.
- Role changes require approval by a higher-role user + audit log entry.

## Input Validation & Injection Prevention

- **ALL** user inputs must pass through Zod DTO validation before processing.
- Use **parameterized queries** exclusively (Prisma handles this — never use raw string interpolation in DB queries).
- Sanitize all string inputs — strip HTML tags where plain text is expected.
- Prevent **Mass Assignment**: never spread request body directly into DB calls.
- File uploads: validate MIME type + file size + scan for malicious content.

## Transport Security

- **HTTPS only** — no HTTP in production. Redirect HTTP → HTTPS.
- HSTS header: `Strict-Transport-Security: max-age=31536000; includeSubDomains`.
- TLS 1.2 minimum; TLS 1.3 preferred.
- Secure cookies: `HttpOnly; Secure; SameSite=Strict`.

## Financial Data Protection

- **Never log** full card numbers, CVV, bank account details, or UPI credentials.
- **Mask sensitive fields** in logs: last 4 digits only for cards/accounts.
- Invoice amounts and customer PII stored with **field-level encryption** for PII fields.
- Payment tokens from gateways must never be stored raw — store only gateway reference IDs.

## API Security

- **Rate Limiting**: 100 req/min per IP for public routes; 1000 req/min for authenticated.
- **CORS**: Whitelist only known frontend origins — no wildcard `*` in production.
- Security headers: `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `CSP` header.
- Expose only necessary HTTP methods per route.

## Audit & Monitoring

- Log every authentication event: login, logout, failed attempts, token refresh.
- Alert on: 5+ failed logins for the same user within 10 minutes (brute force detection).
- Alert on: any access to financial records outside business hours (configurable).
- Retain audit logs for **7 years** (Indian statutory requirement for financial records).
- Audit logs are **append-only** — no user or admin can delete them.

## Dependency Security

- Run `npm audit` / `composer audit` in CI pipeline — block merge if critical vulnerabilities found.
- Pin exact dependency versions in `package-lock.json` / `composer.lock`.
- Regular dependency updates reviewed and tested before applying.

## Secrets Management

- NEVER hardcode secrets, API keys, or credentials in source code.
- Use `.env` files for local development; use secrets manager (AWS Secrets Manager / Vault) for production.
- `.env` files must be in `.gitignore` — never committed.
- Rotate all secrets on suspected compromise immediately.
