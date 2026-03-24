# 📊 Reporting Skills

Atomic, reusable capabilities owned by `reporting.agent`. All skills are read-only.

---

## `profit_loss`

**Purpose**: Generate a structured Profit & Loss (Income) Statement for a given period.

**Inputs**
```typescript
{
  start_date: string;           // ISO 8601 date
  end_date: string;             // ISO 8601 date
  store_id?: string;            // omit for consolidated
  comparison_period?: {
    start_date: string;
    end_date: string;
  };
  format?: 'JSON' | 'PDF' | 'CSV' | 'XLSX';
}
```

**Outputs**
```typescript
{
  report_id: string;
  period: { start: string; end: string; };
  store: string;

  revenue: {
    product_sales: number;
    jewellery_sales: number;
    making_charges: number;
    other_income: number;
    total_revenue: number;
  };

  cost_of_goods_sold: {
    opening_stock: number;
    purchases: number;
    closing_stock: number;
    total_cogs: number;
  };

  gross_profit: number;
  gross_margin_pct: number;

  operating_expenses: {
    salaries: number;
    rent: number;
    utilities: number;
    marketing: number;
    gateway_fees: number;
    other_expenses: number;
    total_opex: number;
  };

  operating_profit: number;
  ebitda: number;

  other_items: {
    depreciation: number;
    interest: number;
    total_other: number;
  };

  profit_before_tax: number;
  tax_provision: number;
  net_profit: number;
  net_margin_pct: number;

  comparison?: { /* same structure for prior period */ };
}
```

**Rules**
- Must call `trial_balance` first — do not generate if trial balance shows imbalance.
- Figures must tie back to posted journal entries — no estimates or adjustments outside the ledger.
- Comparison period figures must use the same accounting method.

---

## `balance_sheet`

**Purpose**: Generate a Balance Sheet snapshot as of a specific date.

**Inputs**
```typescript
{
  as_of_date: string;           // ISO 8601 date
  store_id?: string;
  format?: 'JSON' | 'PDF' | 'CSV' | 'XLSX';
}
```

**Outputs**
```typescript
{
  report_id: string;
  as_of_date: string;

  assets: {
    current_assets: {
      cash_and_bank: number;
      accounts_receivable: number;
      inventory: number;
      gst_input_credit: number;
      other_current: number;
      total_current: number;
    };
    fixed_assets: {
      gross_block: number;
      accumulated_depreciation: number;
      net_block: number;
    };
    total_assets: number;
  };

  liabilities: {
    current_liabilities: {
      accounts_payable: number;
      gst_payable: number;
      customer_advances: number;
      other_current: number;
      total_current: number;
    };
    long_term_liabilities: {
      loans: number;
      other_long_term: number;
      total_long_term: number;
    };
    total_liabilities: number;
  };

  equity: {
    share_capital: number;
    retained_earnings: number;
    current_year_profit: number;
    total_equity: number;
  };

  balance_check: {
    total_assets: number;
    total_liabilities_and_equity: number;
    balanced: boolean;           // MUST be true
    variance: number;            // MUST be 0.00
  };
}
```

**Rules**
- REJECT generation if `balance_check.balanced = false` — alert accounting.agent immediately.
- Read-only — never modifies any records.
- Inventory value must match the current inventory valuation report.

---

## `cash_flow`

**Purpose**: Generate a Cash Flow Statement showing sources and uses of cash.

**Inputs**
```typescript
{
  start_date: string;
  end_date: string;
  store_id?: string;
  method?: 'DIRECT' | 'INDIRECT';   // default INDIRECT
  format?: 'JSON' | 'PDF' | 'CSV' | 'XLSX';
}
```

**Outputs**
```typescript
{
  report_id: string;
  period: { start: string; end: string; };
  method: string;

  operating_activities: {
    net_profit: number;
    adjustments: {
      depreciation: number;
      changes_in_receivables: number;
      changes_in_inventory: number;
      changes_in_payables: number;
      changes_in_gst: number;
      other_adjustments: number;
    };
    net_operating_cashflow: number;
  };

  investing_activities: {
    asset_purchases: number;
    asset_sales: number;
    net_investing_cashflow: number;
  };

  financing_activities: {
    loan_proceeds: number;
    loan_repayments: number;
    owner_drawings: number;
    net_financing_cashflow: number;
  };

  net_change_in_cash: number;
  opening_cash_balance: number;
  closing_cash_balance: number;
  bank_balance_confirmation: number;   // must match Bank account ledger
}
```

**Rules**
- `closing_cash_balance` must match the Bank + Cash ledger balances as of `end_date`.
- If mismatch detected, flag and do not publish the report.
- Read-only — no modifications to financial records.
