# 🏗️ System Architecture Rules

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Frontend | React 19 + Vite + Tailwind CSS v4 |
| Backend | Node.js + TypeScript (Express/Fastify) |
| Database | PostgreSQL |
| ORM | Prisma |
| Auth | JWT (Bearer tokens) |
| AI Layer | Claude Agents (skills + workflows) |
| Messaging | WhatsApp Business API (recovery reminders) |
| Payment | Razorpay / Paytm / PhonePe (configurable) |

## Monorepo Structure

```
/Project2
  /frontend               ← React application (Vite)
    /src
      /modules            ← One folder per business module
        /billing
        /accounting
        /inventory
        /reconciliation
        /recovery
        /reporting
        /settings
      /components
        /ui               ← Shared: Button, Card, Input, Table, Modal
      /hooks              ← Shared React hooks
      /services           ← API service layer (Axios instances per module)
      /store              ← Global state (Zustand or React Context)
      /utils
  /backend                ← Node.js API
    /src
      /modules            ← Controller / Service / Repository per module
      /middleware         ← auth, rbac, audit, error
      /shared
  /prisma                 ← Schema + migrations
  /.claude                ← AI agent config (agents, skills, rules)
  /docs                   ← Architecture diagrams, API docs
```

## Backend Architecture: Clean Layered Pattern

```
API Request → Middleware (auth → RBAC → audit) → Controller → Service → Repository → DB
                                                          ↓
                                                    Agent / Skill calls
```

- **Middleware**: Cross-cutting concerns — authentication, authorization, audit logging.
- **Controller**: HTTP in/out only. Validates DTO, calls service, returns response. No business logic.
- **Service**: All business rules and orchestration. Calls repositories and agents/skills.
- **Repository**: DB access via Prisma. No business logic. Accepts typed inputs, returns typed outputs.

## Frontend Architecture: Module-Based

```
src/modules/billing/
  BillingPage.tsx          ← Route component
  components/              ← Module-specific UI components
  hooks/                   ← Module-specific state hooks (useBilling, useInvoice)
  services/                ← API calls (billingService.ts)
  types.ts                 ← TypeScript types for this module
```

- Each module is **self-contained** — no cross-module direct imports.
- Shared functionality lives in `src/components/ui/` or `src/hooks/`.
- API calls are always through the service layer, never inline `fetch/axios` in components.

## Database Design Principles

- **Soft Deletes**: All financial records use `deleted_at` — never hard delete.
- **Immutable Records**: `journal_entries`, `invoices` (once confirmed), `audit_logs` — no UPDATE, only INSERT + reversal.
- **Audit Timestamps**: `created_at`, `updated_at` on every table.
- **Multi-store**: All tables with financial data include `store_id` for isolation.
- **Fiscal Year**: Invoice/journal numbering resets per financial year (April 1 → March 31, Indian standard).

## Module Dependency Map

```
billing.agent
    │── depends on ──► inventory.agent  (stock check + deduction)
    │── depends on ──► accounting.agent (journal entry post)

reconciliation.agent
    │── depends on ──► accounting.agent (settlement journal entries)

recovery.agent
    │── depends on ──► billing.agent    (payment recording)
    │── depends on ──► accounting.agent (receipt journal entry)

reporting.agent
    │── depends on ──► accounting.agent (reads ledger data)
    │── read-only ──► all modules
```

## Scaling Considerations

- **Multi-store**: Every query must be filterable by `store_id`. No global queries without explicit intent.
- **Stateless API**: Backend is stateless — scale horizontally behind a load balancer.
- **DB Connection Pooling**: Use PgBouncer or Prisma connection pool for PostgreSQL.
- **Background Jobs**: Recovery CRON, reconciliation imports, report generation → use a job queue (BullMQ / pg-boss).
- **Report Caching**: Heavy reports (Balance Sheet, P&L) cached for 5 minutes — not real-time.
