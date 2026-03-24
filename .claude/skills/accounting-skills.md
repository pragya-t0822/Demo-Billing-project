# 💰 Accounting Skills

Atomic, reusable capabilities owned by `accounting.agent`.

---

## `post_journal`

**Purpose**: Create an immutable double-entry journal entry in the ledger.

**Inputs**
```typescript
{
  reference_type: 'INVOICE' | 'PAYMENT' | 'SETTLEMENT' | 'ADJUSTMENT' | 'REVERSAL';
  reference_id: string;         // invoice_id, payment_id, etc.
  fiscal_period: string;        // e.g., "2026-03"
  narration: string;            // human-readable description
  posted_by: string;            // user_id
  lines: Array<{
    account_code: string;       // from Chart of Accounts
    debit_amount?: number;      // provide one of debit or credit
    credit_amount?: number;
    cost_center?: string;       // for multi-store allocation
    description?: string;
  }>;
}
```

**Outputs**
```typescript
{
  journal_entry_id: string;     // e.g., "JE-2026-00456"
  status: 'POSTED';             // always POSTED on success
  total_debit: number;
  total_credit: number;
  audit_log_id: string;
  posted_at: string;            // ISO 8601
}
```

**Rules**
- `sum(debit_amount) === sum(credit_amount)` — REJECT if not equal (throw `JournalImbalanceError`).
- All `account_code` values must exist in Chart of Accounts — REJECT if not found.
- Fiscal period must be OPEN — REJECT if period is CLOSED or LOCKED.
- Status is always `POSTED` on creation — no draft journal entries.
- Reversal entries must reference `original_journal_entry_id`.
- Audit log entry is written atomically with the journal entry (same DB transaction).

---

## `generate_ledger`

**Purpose**: Retrieve the full transaction history for a specific account with running balances.

**Inputs**
```typescript
{
  account_code: string;
  start_date: string;       // ISO 8601 date
  end_date: string;         // ISO 8601 date
  store_id?: string;        // filter by store/cost center
  page?: number;
  per_page?: number;        // max 500
}
```

**Outputs**
```typescript
{
  account_code: string;
  account_name: string;
  opening_balance: number;
  closing_balance: number;
  entries: Array<{
    date: string;
    journal_entry_id: string;
    narration: string;
    debit: number;
    credit: number;
    running_balance: number;
  }>;
  total_debits: number;
  total_credits: number;
  pagination: { page: number; total_pages: number; total_records: number; };
}
```

**Rules**
- Read-only operation — NEVER modifies any record.
- Opening balance is calculated from all entries before `start_date`.
- Running balance uses account normal balance convention (Assets/Expenses: debit-normal; Liabilities/Equity/Revenue: credit-normal).
- Voided/reversed entries are shown with a REVERSED flag but remain in the ledger.

---

## `trial_balance`

**Purpose**: Verify that total debits equal total credits across all accounts for a period.

**Inputs**
```typescript
{
  as_of_date: string;       // ISO 8601 date
  store_id?: string;
  include_zero_balance?: boolean;
}
```

**Outputs**
```typescript
{
  as_of_date: string;
  status: 'BALANCED' | 'IMBALANCED';
  total_debits: number;
  total_credits: number;
  variance: number;         // must be 0.00 for BALANCED
  accounts: Array<{
    account_code: string;
    account_name: string;
    debit_balance: number;
    credit_balance: number;
  }>;
  alert?: string;           // present only if IMBALANCED
}
```

**Rules**
- If `variance !== 0`, immediately alert the finance team and log a `CRITICAL` audit event.
- `variance` should always be `0.00` — any non-zero value indicates a system integrity issue.
- This skill must be run before every period-close and before generating Balance Sheet.
- Read-only — NEVER modifies records.
- Imbalance must be investigated and resolved via reversal/adjustment entries before proceeding.
