# 📱 Recovery Skills

Atomic, reusable capabilities owned by `recovery.agent`.

---

## `detect_overdue`

**Purpose**: Scan all invoices to identify outstanding dues and classify them by days overdue.

**Inputs**
```typescript
{
  store_id?: string;            // omit for all stores
  as_of_date?: string;          // defaults to today
  min_days_overdue?: number;    // default 1
  max_days_overdue?: number;    // omit for no upper limit
  exclude_customer_ids?: string[];
}
```

**Outputs**
```typescript
{
  scan_id: string;
  scan_date: string;
  total_overdue_count: number;
  total_overdue_amount: number;
  buckets: {
    '1_7_days':  { count: number; amount: number; invoices: OverdueInvoice[]; };
    '8_15_days': { count: number; amount: number; invoices: OverdueInvoice[]; };
    '16_30_days':{ count: number; amount: number; invoices: OverdueInvoice[]; };
    '31_60_days':{ count: number; amount: number; invoices: OverdueInvoice[]; };
    '60_plus':   { count: number; amount: number; invoices: OverdueInvoice[]; };
  };
}

// OverdueInvoice shape:
{
  invoice_id: string;
  customer_id: string;
  customer_name: string;
  customer_mobile: string;
  invoice_amount: number;
  amount_paid: number;
  outstanding_balance: number;
  due_date: string;
  days_overdue: number;
  recovery_stage: number;       // 1–5
  last_reminder_sent?: string;  // ISO 8601
}
```

**Rules**
- Only invoices with `status = CONFIRMED | PARTIAL` and `due_date < as_of_date` are included.
- Invoices with `recovery_status = CLOSED | LEGAL | PAUSED` are excluded by default.
- NEVER include zero-outstanding invoices.
- Read-only — this skill does NOT modify any records.

---

## `send_reminder`

**Purpose**: Dispatch a recovery reminder to the customer via WhatsApp and/or Email.

**Inputs**
```typescript
{
  invoice_id: string;
  customer_id: string;
  reminder_stage: 1 | 2 | 3 | 4 | 5;
  channels: Array<'WHATSAPP' | 'EMAIL' | 'SMS'>;
  payment_link_id?: string;      // attach if available
  custom_message?: string;       // override template if provided
  sent_by: 'SYSTEM' | string;    // user_id or SYSTEM for automated
}
```

**Outputs**
```typescript
{
  reminder_log_id: string;
  invoice_id: string;
  channels_sent: Array<{
    channel: string;
    status: 'SENT' | 'FAILED' | 'QUEUED';
    message_id?: string;         // gateway message ID
    error?: string;
  }>;
  next_reminder_date: string;    // calculated from dunning schedule
}
```

**Message Templates by Stage**
| Stage | Tone | Includes |
|-------|------|----------|
| 1 | Friendly reminder | Due amount, payment link |
| 2 | Polite follow-up | Outstanding, payment link, contact info |
| 3 | Firm notice | Due amount, deadline, escalation warning |
| 4 | Final notice | Amount, legal warning, last chance |
| 5 | Internal flag | Not sent to customer — internal escalation |

**Rules**
- ALWAYS log every reminder dispatch (message content, timestamp, delivery status).
- Duplicate suppression: do not send same stage reminder twice within 24 hours.
- Failed sends must be retried up to 3 times with exponential backoff.
- Legal-flagged customers (Stage 5) must NOT receive automated messages — human takes over.
- WhatsApp messages must use pre-approved templates (WABA compliance).
- All message content is auditable — store full message text in log.

---

## `generate_payment_link`

**Purpose**: Create a secure, expiring payment link for one-click invoice recovery.

**Inputs**
```typescript
{
  invoice_id: string;
  customer_id: string;
  amount: number;               // outstanding balance or custom amount
  expiry_hours?: number;        // default 48
  payment_methods?: Array<'UPI' | 'CARD' | 'NETBANKING' | 'WALLET'>;
  notes?: string;
  created_by: string;           // user_id or 'SYSTEM'
}
```

**Outputs**
```typescript
{
  link_id: string;              // e.g., "PL-2026-00234"
  short_url: string;            // e.g., "https://pay.store.in/p/ABC123"
  invoice_id: string;
  amount: number;
  expires_at: string;           // ISO 8601
  status: 'ACTIVE';
  qr_code_url?: string;         // optional QR code for WhatsApp
}
```

**Rules**
- Link is single-use — mark USED immediately on successful payment.
- Expired links must return a clear expiry message to the customer.
- Link amount must not exceed invoice outstanding balance.
- On payment via link: automatically call `billing.agent → process_payment`, then `accounting.agent → post_journal`.
- Links must be tracked in the audit log with creator and expiry details.
- HTTPS only — no HTTP payment links allowed.
