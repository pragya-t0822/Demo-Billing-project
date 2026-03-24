# Modern Retail Billing & Accounting System

## Overview
A production-grade **Retail Billing & Accounting Platform** built for fintech-level accuracy, Indian GST compliance, and multi-store scalability. Specialized support for jewellery and high-value inventory.

## Business Domain
- **Primary Market**: Retail shops including Jewellery stores (gold, silver, precious items)
- **Compliance**: Indian GST — CGST, SGST, IGST
- **Fiscal Year**: April 1 – March 31 (Indian standard)
- **Scale**: Designed for multi-store operations from day one

## Core Modules

| Module | Agent | Key Function |
|--------|-------|-------------|
| Billing (POS) | `billing-agent` | GST-compliant checkout, invoice generation, payment collection |
| Accounting | `accounting-agent` | Double-entry journal, ledger, trial balance, period close |
| Inventory | `inventory-agent` | SKU + weight-based stock, jewellery pricing, reorder alerts |
| Reconciliation | `reconciliation-agent` | Bank statement matching, gateway settlement, fee accounting |
| Recovery | `recovery-agent` | Overdue detection, WhatsApp reminders, payment links |
| Reporting | `reporting-agent` | P&L, Balance Sheet, Cash Flow, GST returns |

## Architecture Summary
- **Frontend**: React 19 + Vite + Tailwind CSS v4 (`/frontend`)
- **Backend**: Node.js + TypeScript + Prisma + PostgreSQL (`/backend`)
- **AI Layer**: Claude Agents + Skills + Workflows (`/.claude`)
- Pattern: Clean Architecture (Controller → Service → Repository)

## Financial Principles (Non-Negotiable)
1. **Double Entry**: Every transaction balances (Debits = Credits)
2. **Immutability**: No editing posted journal entries — reversals only
3. **GST Compliance**: Line-item level tax storage, CGST/SGST/IGST
4. **Audit Trail**: Every financial action logged (user, timestamp, before/after)
5. **No Orphans**: Every invoice/payment has corresponding journal entries

## Current Status
- Frontend modules initialized: Billing, Accounting, Inventory, Reconciliation, Recovery, Reporting, Settings
- Tailwind CSS v4 configured with theme variables
- Routing and sidebar navigation established
- Agent and skill definitions complete (see `.claude/agents/` and `.claude/skills/`)

## Key Reference Files
- Financial rules: `.claude/rules/finance.md`
- Business workflows: `.claude/rules/workflows.md`
- Architecture: `.claude/rules/architecture.md`
- Backend rules: `.claude/rules/backend.md`
- Security rules: `.claude/rules/security.md`
- Agent details: `.claude/agents/*.agent.md`
- Skill specs: `.claude/skills/*-skills.md`
