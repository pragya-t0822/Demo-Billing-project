# 🔧 Backend Development Rules

## Tech Stack
- **Runtime**: Node.js (TypeScript strict mode)
- **Framework**: Express / Fastify (REST API)
- **ORM**: Prisma with PostgreSQL
- **Auth**: JWT via Bearer token (Laravel Sanctum-compatible interface)
- **Validation**: Zod for all input DTOs

## Architecture Pattern: Controller → Service → Repository

```
HTTP Request
    │
    ▼
Controller       ← Validates input (DTO), handles HTTP concerns only
    │
    ▼
Service          ← Business logic, orchestration, calls agents/skills
    │
    ▼
Repository       ← Data access via Prisma, NO business logic here
    │
    ▼
Database (PostgreSQL)
```

**Rules:**
- Controllers NEVER contain business logic — only input validation + HTTP response.
- Repositories NEVER contain business logic — only DB queries.
- Services own all business rules and agent/skill calls.
- Cross-module calls go through service interfaces, not direct DB access.

## File & Folder Structure

```
/backend
  /src
    /modules
      /billing
        billing.controller.ts
        billing.service.ts
        billing.repository.ts
        billing.dto.ts
        billing.types.ts
      /accounting
        ...
      /inventory
        ...
    /middleware
      auth.middleware.ts
      rbac.middleware.ts
      audit.middleware.ts
      error.middleware.ts
    /shared
      /utils
      /errors
      /types
    app.ts
    server.ts
  /prisma
    schema.prisma
    /migrations
```

## TypeScript Rules
- `strict: true` — no implicit `any`, no loose types.
- All function parameters and return types must be explicitly typed.
- Use `type` for unions/intersections; `interface` for object shapes.
- Use `readonly` on DTO fields that must not be mutated.
- Prefer `unknown` over `any` when type is genuinely unknown.

## Input Validation (Zod DTOs)
```typescript
// Example billing DTO
export const CreateInvoiceDto = z.object({
  customer_id: z.string().uuid(),
  store_id: z.string().uuid(),
  line_items: z.array(LineItemSchema).min(1),
  payment_mode: z.enum(['CASH', 'CARD', 'UPI', 'CREDIT']),
});
export type CreateInvoiceInput = z.infer<typeof CreateInvoiceDto>;
```
- Validate at the controller layer BEFORE any service call.
- Return `400 Bad Request` with field-level error details on validation failure.
- Never trust incoming data — always parse through Zod schema.

## Error Handling
- Use custom error classes: `ValidationError`, `NotFoundError`, `BusinessRuleError`, `JournalImbalanceError`.
- Global error middleware converts errors to standardized JSON responses.
- Never expose raw stack traces in production responses.
- Log all 5xx errors with full context (user_id, request body, stack trace).

## Database Rules
- All financial DB operations MUST use **Prisma transactions** (`prisma.$transaction()`).
- Never use raw SQL unless absolutely necessary — always prefer Prisma query API.
- Soft-delete pattern for all financial records (`deleted_at` column, never hard delete).
- Timestamps: `created_at`, `updated_at` on every table (auto-managed by Prisma).

## API Response Standard
```typescript
// Success
{ "success": true, "data": { ... } }

// Error
{ "success": false, "error": { "code": "VALIDATION_ERROR", "message": "...", "details": [...] } }

// Paginated
{ "success": true, "data": [...], "pagination": { "page": 1, "per_page": 20, "total": 100 } }
```

## Logging
- Use structured logging (JSON format) in production.
- Log level per environment: `debug` (dev), `info` (staging), `warn/error` (prod).
- Every financial action MUST emit an audit log (see finance rules).
- Include `request_id` in all log lines for traceability.
