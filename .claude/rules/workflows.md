# 🔁 Business Workflow Rules

Workflows define multi-step business processes. Every workflow MUST be followed completely — no steps may be skipped.

## Rule: Never Skip the Accounting Step

In any workflow that involves a financial event (sale, payment, adjustment), the accounting journal entry step is **MANDATORY**. Skipping it creates orphan transactions — a critical financial integrity violation.

---

## Workflow 1: Complete Sales Billing

```
Trigger: Customer checkout at POS

Steps:
1.  [billing.agent]        Validate customer + cart + store context
2.  [inventory.agent]      Check stock availability for all line items
                           → REJECT if any item has insufficient stock
3.  [billing.agent]        calculate_gst for each line item
4.  [billing.agent]        apply_discount (if applicable, with authorization)
5.  [billing.agent]        create_invoice → status = DRAFT
6.  [inventory.agent]      update_stock → DEDUCT quantities (atomic)
7.  [billing.agent]        Confirm invoice → status = CONFIRMED (immutable)
8.  [accounting.agent]     post_journal → Sales entry
                           DR Accounts Receivable / Cash
                           CR Sales Revenue + CGST Payable + SGST Payable
9.  [billing.agent]        process_payment → record payment
10. [accounting.agent]     post_journal → Payment receipt entry
11. [billing.agent]        Generate invoice PDF + optional WhatsApp receipt

On Failure at step 6:
→ Rollback: Delete DRAFT invoice, do NOT proceed
On Failure at step 8:
→ CRITICAL: Alert accounting team, flag invoice as PENDING_JOURNAL
```

---

## Workflow 2: Payment Gateway Settlement

```
Trigger: Daily settlement file received from payment gateway

Steps:
1.  [reconciliation.agent]  Import settlement file
2.  [reconciliation.agent]  settlement_tracking → list settled transactions
3.  [reconciliation.agent]  fee_calculation → compute fee + GST on fee per txn
4.  [accounting.agent]      post_journal → move from Clearing to Bank
                            DR Bank Account (net)
                            DR Gateway Fee Expense (fee)
                            DR GST Input Credit (18% of fee)
                            CR Payment Gateway Clearing (gross)
5.  [reconciliation.agent]  Verify Clearing Account balance → must approach 0
6.  [reconciliation.agent]  match_transactions → match to bank statement
7.  [reconciliation.agent]  Generate settlement reconciliation report

On Clearing Account balance ≠ 0:
→ Flag as PENDING_REVIEW, alert finance team
```

---

## Workflow 3: Bank Reconciliation

```
Trigger: Daily (automated CRON at 09:00)

Steps:
1.  [reconciliation.agent]  Import bank statement (CSV/API)
2.  [reconciliation.agent]  match_transactions → auto-match entries
3.  [reconciliation.agent]  Review unmatched entries
4.  [accounting.agent]      post_journal → adjustments for approved items
5.  [reconciliation.agent]  Calculate variance:
                            Bank closing balance vs System closing balance
6.  If variance = 0 → Mark period RECONCILED
    If variance ≠ 0 → Flag PENDING, escalate to STORE_MANAGER
```

---

## Workflow 4: Payment Recovery Cycle

```
Trigger: Daily CRON at 08:00

Steps:
1.  [recovery.agent]    detect_overdue → identify all outstanding invoices
2.  [recovery.agent]    Classify by days_overdue → assign escalation stage
3.  [recovery.agent]    generate_payment_link for each invoice
4.  [recovery.agent]    send_reminder → WhatsApp + Email per stage template
5.  [recovery.agent]    Log all dispatch events
6.  [recovery.agent]    Update recovery_status on each invoice

On Payment via Link:
7.  [billing.agent]     process_payment
8.  [accounting.agent]  post_journal → cash receipt entry
9.  [recovery.agent]    Close recovery record
```

---

## Workflow 5: Inventory Purchase Receipt

```
Trigger: Goods Received Note (GRN) submitted by purchase manager

Steps:
1.  [inventory.agent]   Validate GRN: supplier, PO reference, item list
2.  [inventory.agent]   update_stock → ADD quantities/weights
3.  [accounting.agent]  post_journal → Inventory receipt
                        DR Inventory Asset
                        CR Accounts Payable (if credit) / Cash (if cash)
4.  [inventory.agent]   Update FIFO stack / weighted average cost
5.  [reporting.agent]   Update inventory valuation snapshot
```

---

## Workflow 6: Month-End Financial Close

```
Trigger: Last day of month (manual approval required for close)

Pre-conditions:
- All bank reconciliations for the month must be RECONCILED
- All unmatched bank entries must be RESOLVED
- GST Input Credit must be reconciled

Steps:
1.  [accounting.agent]   trial_balance → must show 0 variance
2.  [accounting.agent]   Post accrual entries (depreciation, prepaid amortisation)
3.  [accounting.agent]   Post GST settlement entry
4.  [reporting.agent]    profit_loss → generate month P&L
5.  [reporting.agent]    balance_sheet → generate month-end snapshot
6.  [reporting.agent]    cash_flow → generate for the month
7.  [accounting.agent]   Lock fiscal period (no further entries)
8.  Archive reports to financial document store

On trial_balance showing imbalance:
→ HALT — do NOT close period until imbalance resolved
```

---

## Workflow 7: Invoice Cancellation

```
Trigger: Cancel request for a CONFIRMED invoice

Steps:
1.  [billing.agent]     Validate: invoice is CONFIRMED and not already PAID
2.  [billing.agent]     Create Credit Note (CN-YYYY-NNNNN) referencing original invoice
3.  [inventory.agent]   update_stock → RESTORE deducted quantities
4.  [accounting.agent]  post_journal → Reversal entry (mirror of original sales entry)
5.  [billing.agent]     Set original invoice status = CANCELLED
6.  [billing.agent]     Set credit note status = ISSUED

Rules:
- NEVER delete a confirmed invoice — cancellation only via reversal
- Credit note number must be sequential like invoice numbers
- If original payment was received → trigger refund workflow
```
