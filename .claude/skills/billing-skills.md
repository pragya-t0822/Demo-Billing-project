# 🧾 Billing Skills

Atomic, reusable capabilities owned by `billing.agent`.

---

## `create_invoice`

**Purpose**: Generate a new GST-compliant invoice record in the system.

**Inputs**
```typescript
{
  customer_id: string;
  store_id: string;
  line_items: Array<{
    sku_or_item_id: string;
    quantity: number;        // for SKU items
    weight_grams?: number;   // for weight-based items
    unit_price: number;
    hsn_code: string;
    gst_rate: number;        // e.g., 3 for 3% GST
    discount_amount?: number;
  }>;
  payment_mode: 'CASH' | 'CARD' | 'UPI' | 'CREDIT';
  notes?: string;
}
```

**Outputs**
```typescript
{
  invoice_id: string;        // e.g., "INV-2026-00123"
  invoice_number: string;    // sequential, gapless
  status: 'DRAFT' | 'CONFIRMED';
  subtotal: number;
  total_discount: number;
  taxable_amount: number;
  cgst_total: number;
  sgst_total: number;
  igst_total: number;
  grand_total: number;
  created_at: string;        // ISO 8601
}
```

**Rules**
- Invoice numbers must be sequential per financial year (reset April 1).
- Status starts as DRAFT; transitions to CONFIRMED only after stock validation.
- Line items are immutable once status = CONFIRMED.
- A cancelled invoice generates a credit note (CN-YYYY-NNNNN), never deleted.

---

## `calculate_gst`

**Purpose**: Compute correct GST split (CGST+SGST or IGST) based on supply type.

**Inputs**
```typescript
{
  taxable_amount: number;
  gst_rate: number;           // e.g., 3, 5, 12, 18, 28
  supply_type: 'INTRA' | 'INTER';  // same state = INTRA
  hsn_code: string;
}
```

**Outputs**
```typescript
{
  cgst_rate: number;     // gst_rate / 2 if INTRA, else 0
  sgst_rate: number;     // gst_rate / 2 if INTRA, else 0
  igst_rate: number;     // gst_rate if INTER, else 0
  cgst_amount: number;
  sgst_amount: number;
  igst_amount: number;
  total_tax: number;
  total_with_tax: number;
}
```

**Rules**
- Rounding: Tax amounts rounded to 2 decimal places using standard rounding (0.5 → up).
- For Jewellery: GST is 3% on gold/silver items; making charges may attract 5%.
- HSN code must be validated against the approved HSN master list.
- NEVER compute tax on the discount amount — discount is applied first.

---

## `apply_discount`

**Purpose**: Apply promotional, loyalty, or manual discounts to line items or invoice total.

**Inputs**
```typescript
{
  invoice_id: string;
  discount_type: 'PERCENTAGE' | 'FLAT';
  discount_value: number;
  apply_to: 'LINE_ITEM' | 'INVOICE_TOTAL';
  line_item_id?: string;         // required if apply_to = LINE_ITEM
  discount_reason: string;       // mandatory for audit
  authorized_by: string;         // user_id of approver (if > threshold)
}
```

**Outputs**
```typescript
{
  original_amount: number;
  discount_applied: number;
  net_taxable_amount: number;
  authorization_required: boolean;
}
```

**Rules**
- Discounts above 10% require manager authorization (`authorized_by` must be a MANAGER role).
- Discounts are applied on the base price **before** GST calculation.
- Discount reason is mandatory for audit compliance.
- Jewellery making charges discounts must be logged separately from item price discounts.

---

## `process_payment`

**Purpose**: Record a payment against a confirmed invoice and trigger accounting entries.

**Inputs**
```typescript
{
  invoice_id: string;
  payment_mode: 'CASH' | 'CARD' | 'UPI' | 'NETBANKING' | 'CHEQUE' | 'CREDIT';
  amount_paid: number;
  gateway_transaction_id?: string;   // required for digital payments
  cheque_number?: string;            // required for CHEQUE
  bank_reference?: string;
}
```

**Outputs**
```typescript
{
  payment_id: string;
  invoice_id: string;
  amount_paid: number;
  outstanding_balance: number;
  payment_status: 'PAID' | 'PARTIAL' | 'PENDING';
  journal_entry_id: string;    // always present
}
```

**Rules**
- ALWAYS call `accounting.agent → post_journal` after recording payment.
- For digital payments: move amount to Gateway Clearing Account (not directly to Bank).
- For Cash: debit Cash-in-Hand account immediately.
- Overpayments must be recorded as Customer Advance (Liability), not as revenue.
- Partial payments are allowed; outstanding balance tracked per invoice.
