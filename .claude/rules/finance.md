# ⚖️ Core Financial Rules (Strict — NON-NEGOTIABLE)

These rules MUST be followed by all agents, services, and systems. No exceptions.

---

## 1. Double Entry Accounting

- Every transaction MUST satisfy: **Total Debits = Total Credits**.
- No imbalance is ever acceptable — throw `JournalImbalanceError` and reject the operation.
- Every financial movement must hit **at least two ledger accounts**.
- All journal entries flow through `accounting.agent → post_journal` — never bypass this.

---

## 2. Immutable Accounting

- Journal entries **cannot be edited or deleted** once posted to the ledger.
- To correct an error: create a **reversal entry** (mirror of original with opposite Dr/Cr), then post a corrected entry.
- Invoice records are **immutable once CONFIRMED** — use credit notes for cancellations.
- Audit trails must be preserved permanently (7-year statutory retention in India).

---

## 3. GST Compliance (India)

- Support **CGST + SGST** for intra-state supply; **IGST** for inter-state supply.
- Tax calculation must be precise — round to 2 decimal places per statutory norms.
- GST details stored at **line-item level** in every invoice (not just invoice total).
- Discounts must be applied **before** GST computation on the net taxable amount.
- GST HSN/SAC code is mandatory for every taxable line item.
- **Jewellery GST Rates**: Gold/silver items → 3%; Making charges → 5%.
- GST payable and input credit accounts must be reconciled before period close.

---

## 4. Audit Log Requirements

Every financial action MUST be logged with:
- `user_id` — who performed the action
- `timestamp` — exact ISO 8601 datetime
- `action` — e.g., `POST_JOURNAL`, `CONFIRM_INVOICE`, `PROCESS_PAYMENT`
- `entity_type` + `entity_id` — what was acted upon
- `before_state` — snapshot before change (null for creates)
- `after_state` — snapshot after change

Audit logs are **append-only** — no user or process may delete or modify them.

---

## 5. Data Integrity Rules

| Rule | Detail |
|------|--------|
| No Negative Stock | Reject transactions resulting in negative inventory unless `allow_negative_stock = true` (admin-set per item) |
| No Orphan Transactions | Every confirmed invoice/payment MUST have corresponding journal entries. Alert if missing. |
| No Ledger Mismatch | Trial balance must show zero variance. Any non-zero variance is a CRITICAL system alert. |
| No Period Posting | REJECT any journal entry targeting a CLOSED fiscal period |
| Sequential Numbering | Invoice and journal entry numbers must be sequential with no gaps |

---

## 6. Financial Period Management

- Fiscal year: **April 1 – March 31** (Indian standard).
- Month-end close requires: all reconciliations resolved + trial balance balanced.
- Once a period is CLOSED, no further entries are allowed — corrections go to the next open period.
- Period close must be manually approved by STORE_MANAGER or above.

---

## 7. Inventory Valuation

- Valuation method (FIFO or Weighted Average Cost) must be selected per product category.
- Method cannot be changed mid-financial-year without a period adjustment entry.
- Inventory asset value in the Balance Sheet must always match the inventory valuation report.
- Weight-based items (gold/silver): value recorded at cost price on receipt; revaluation only on specific admin request.
