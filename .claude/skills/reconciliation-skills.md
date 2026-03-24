# 🔗 Reconciliation Skills

Atomic, reusable capabilities owned by `reconciliation.agent`.

---

## `match_transactions`

**Purpose**: Automatically match imported bank statement entries to system payment records.

**Inputs**
```typescript
{
  bank_statement_id: string;
  reconciliation_date: string;   // ISO 8601 date
  store_id: string;
  match_tolerance_amount?: number;  // default 0.50 (rounding tolerance)
  match_date_window_days?: number;  // default 2
}
```

**Outputs**
```typescript
{
  reconciliation_run_id: string;
  matched: Array<{
    bank_entry_id: string;
    system_payment_id: string;
    match_confidence: 'HIGH' | 'MEDIUM' | 'LOW';
    match_criteria: string[];   // e.g., ["amount", "reference_id"]
  }>;
  unmatched_bank_entries: Array<{
    bank_entry_id: string;
    amount: number;
    date: string;
    narration: string;
    suggested_match?: string;   // nearest candidate
  }>;
  unmatched_system_payments: Array<{
    payment_id: string;
    amount: number;
    date: string;
  }>;
  summary: {
    total_bank_entries: number;
    matched_count: number;
    unmatched_count: number;
    variance_amount: number;
  };
}
```

**Matching Algorithm (Priority Order)**
1. Exact: `amount + gateway_reference_id`
2. `amount + date (±window_days) + last 4 digits of account/card`
3. `amount (±tolerance) + date (±window_days)`
4. No match → flagged as `PENDING_REVIEW`

**Rules**
- Auto-match is advisory: a human must CONFIRM matches above `LOW` confidence if amount > ₹50,000.
- Bank entries are immutable after import — corrections via adjustment journal entries only.
- `PENDING_REVIEW` items must not block period close but must be resolved within 7 days.
- Always record `match_confidence` so auditors can review auto-matched items.

---

## `settlement_tracking`

**Purpose**: Track the lifecycle of electronic payment settlements from initiation to bank credit.

**Inputs**
```typescript
{
  gateway: 'RAZORPAY' | 'PAYTM' | 'STRIPE' | 'PHONEPE' | 'OTHER';
  settlement_file_id?: string;   // if processing a settlement batch file
  transaction_ids?: string[];    // if tracking specific transactions
  store_id: string;
}
```

**Outputs**
```typescript
{
  settlements: Array<{
    gateway_txn_id: string;
    payment_id: string;           // system payment reference
    gross_amount: number;
    fee_amount: number;
    gst_on_fee: number;
    net_settled: number;
    settlement_date: string;
    settlement_utr: string;       // bank UTR for bank matching
    status: 'PENDING' | 'SETTLED' | 'REVERSED' | 'DISPUTED';
  }>;
  total_gross: number;
  total_fees: number;
  total_net: number;
  clearing_account_balance: number;   // should approach 0 after posting
}
```

**Rules**
- Gateway Clearing Account must reach zero balance after all settlements are posted.
- Every settled transaction MUST have a corresponding journal entry via `accounting.agent`.
- Settlement UTR is used for bank reconciliation matching.
- Reversed/refunded settlements must create a reversal journal entry.
- Fee amounts must be inclusive of 18% GST on the gateway fee (Input Tax Credit applicable).

---

## `fee_calculation`

**Purpose**: Compute transaction fees, gateway charges, and GST on fees for each payment.

**Inputs**
```typescript
{
  gateway: string;
  payment_mode: 'CARD' | 'UPI' | 'NETBANKING' | 'WALLET';
  gross_amount: number;
  gateway_fee_rate?: number;    // overrides default if provided (decimal, e.g., 0.02)
  gst_on_fee_rate?: number;     // default 0.18 (18% GST on gateway fee)
}
```

**Outputs**
```typescript
{
  gross_amount: number;
  fee_rate: number;             // e.g., 0.02 = 2%
  fee_base: number;             // gross_amount × fee_rate
  gst_on_fee: number;           // fee_base × gst_on_fee_rate
  total_fee: number;            // fee_base + gst_on_fee
  net_settled: number;          // gross_amount − total_fee
  journal_lines: Array<{
    account_code: string;
    debit?: number;
    credit?: number;
    description: string;
  }>;
}
```

**Standard Fee Rates (Configurable)**
| Gateway | Mode | Fee Rate |
|---------|------|----------|
| Razorpay | UPI | 0% |
| Razorpay | Card | 2% |
| Razorpay | NetBanking | 1.5% |
| Paytm | UPI | 0% |
| Paytm | Wallet | 1.99% |

**Rules**
- Fee rates are configurable per gateway in system settings — NEVER hardcode.
- GST on gateway fees (18%) is an Input Tax Credit for the business — must be booked separately.
- Fee calculation must always produce the journal lines to be passed to `accounting.agent`.
- Any fee rate change requires an admin audit log entry.
