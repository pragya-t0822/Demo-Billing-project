# 🧩 Skills Registry

Skills are atomic, reusable capabilities that agents invoke to perform specific business logic. Each skill is **owned by exactly one agent** and has defined inputs, outputs, and rules.

---

## Skill Catalog

| Skill | Owning Agent | File | Category |
|-------|-------------|------|----------|
| `create_invoice` | `billing.agent` | [billing-skills.md](billing-skills.md) | Billing |
| `calculate_gst` | `billing.agent` | [billing-skills.md](billing-skills.md) | Billing |
| `apply_discount` | `billing.agent` | [billing-skills.md](billing-skills.md) | Billing |
| `process_payment` | `billing.agent` | [billing-skills.md](billing-skills.md) | Billing |
| `post_journal` | `accounting.agent` | [accounting-skills.md](accounting-skills.md) | Accounting |
| `generate_ledger` | `accounting.agent` | [accounting-skills.md](accounting-skills.md) | Accounting |
| `trial_balance` | `accounting.agent` | [accounting-skills.md](accounting-skills.md) | Accounting |
| `update_stock` | `inventory.agent` | [inventory-skills.md](inventory-skills.md) | Inventory |
| `weight_handling` | `inventory.agent` | [inventory-skills.md](inventory-skills.md) | Inventory |
| `low_stock_alert` | `inventory.agent` | [inventory-skills.md](inventory-skills.md) | Inventory |
| `match_transactions` | `reconciliation.agent` | [reconciliation-skills.md](reconciliation-skills.md) | Reconciliation |
| `settlement_tracking` | `reconciliation.agent` | [reconciliation-skills.md](reconciliation-skills.md) | Reconciliation |
| `fee_calculation` | `reconciliation.agent` | [reconciliation-skills.md](reconciliation-skills.md) | Reconciliation |
| `detect_overdue` | `recovery.agent` | [recovery-skills.md](recovery-skills.md) | Recovery |
| `send_reminder` | `recovery.agent` | [recovery-skills.md](recovery-skills.md) | Recovery |
| `generate_payment_link` | `recovery.agent` | [recovery-skills.md](recovery-skills.md) | Recovery |
| `profit_loss` | `reporting.agent` | [reporting-skills.md](reporting-skills.md) | Reporting |
| `balance_sheet` | `reporting.agent` | [reporting-skills.md](reporting-skills.md) | Reporting |
| `cash_flow` | `reporting.agent` | [reporting-skills.md](reporting-skills.md) | Reporting |

---

## Usage Rules

- Skills are **atomic** — one responsibility, one concern, one output contract.
- Skills **never call other skills directly** — the owning agent orchestrates the sequence.
- **Only the owning agent** may invoke a skill — no cross-agent skill calls.
- Every skill that modifies financial data **MUST** trigger an audit log entry.
- Skills are **idempotent** where possible — safe to retry on transient failure.
- Every skill has a defined **input schema**, **output schema**, and **guard rules** — follow them exactly.

---

## Skill Execution Chain

```
create_invoice
  └─► calculate_gst       (billing.agent — per line item before total)
  └─► apply_discount      (billing.agent — if discount present, before GST)
  └─► update_stock        (inventory.agent — after invoice CONFIRMED, not draft)
  └─► post_journal        (accounting.agent — sales entry, MANDATORY)

process_payment
  └─► post_journal        (accounting.agent — payment receipt entry, MANDATORY)

update_stock
  └─► low_stock_alert     (inventory.agent — after every deduction)
  └─► post_journal        (accounting.agent — for purchase/adjustment movements)

settlement_tracking
  └─► fee_calculation     (reconciliation.agent — per settled transaction)
  └─► post_journal        (accounting.agent — settlement journal entry, MANDATORY)

detect_overdue
  └─► generate_payment_link  (recovery.agent — per overdue invoice)
  └─► send_reminder          (recovery.agent — per escalation stage)

profit_loss
  └─► trial_balance       (accounting.agent — pre-validation before report generation)

balance_sheet
  └─► trial_balance       (accounting.agent — must be balanced before sheet is valid)
```

---

## Financial Skills — Audit Requirements

Skills that post or modify financial data must include an audit log entry:

| Skill | Audit Action |
|-------|-------------|
| `create_invoice` | `CREATE_INVOICE` |
| `process_payment` | `PROCESS_PAYMENT` |
| `post_journal` | `POST_JOURNAL` |
| `update_stock` | `UPDATE_STOCK` |
| `match_transactions` | `MATCH_TRANSACTION` |
| `settlement_tracking` | `PROCESS_SETTLEMENT` |
| `generate_payment_link` | `GENERATE_PAYMENT_LINK` |
| `send_reminder` | `SEND_REMINDER` |
