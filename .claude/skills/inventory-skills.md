# 📦 Inventory Skills

Atomic, reusable capabilities owned by `inventory.agent`.

---

## `update_stock`

**Purpose**: Atomically adjust stock levels for a product following a business event.

**Inputs**
```typescript
{
  sku_or_item_id: string;
  movement_type: 'SALE' | 'PURCHASE' | 'RETURN' | 'ADJUSTMENT' | 'DAMAGE' | 'TRANSFER';
  quantity_change: number;      // positive = add, negative = deduct
  weight_change_grams?: number; // for weight-based items (3 decimal precision)
  reference_id: string;         // invoice_id / grn_id / adjustment_id
  store_id: string;
  adjusted_by: string;          // user_id
  reason?: string;              // mandatory for ADJUSTMENT and DAMAGE
}
```

**Outputs**
```typescript
{
  sku_or_item_id: string;
  movement_id: string;
  previous_quantity: number;
  quantity_change: number;
  new_quantity: number;
  previous_weight_grams?: number;
  new_weight_grams?: number;
  stock_movement_log_id: string;
  low_stock_alert_triggered: boolean;
}
```

**Rules**
- Entire operation must execute in a **single DB transaction** — no partial updates.
- REJECT if `new_quantity < 0` AND `allow_negative_stock = false` for that item.
- ALWAYS write a `StockMovementLog` entry alongside the stock update.
- ALWAYS check `reorder_point` after update — trigger `low_stock_alert` if breached.
- ALWAYS call `accounting.agent → post_journal` for inventory valuation changes (PURCHASE, DAMAGE, ADJUSTMENT).
- Weight changes use **3 decimal precision** (milligrams matter for jewellery).

---

## `weight_handling`

**Purpose**: Calculate the final sale price for weight-based jewellery items using live metal rates.

**Inputs**
```typescript
{
  item_id: string;
  metal_type: 'GOLD_22K' | 'GOLD_18K' | 'SILVER' | 'PLATINUM';
  gross_weight_grams: number;    // total weight including setting/stones
  net_weight_grams: number;      // pure metal weight
  stone_weight_grams?: number;   // deducted from gross for metal price
  making_charges_type: 'FLAT' | 'PERCENTAGE';
  making_charges_value: number;
  wastage_percentage?: number;   // typical 3–12% for hand-crafted items
  hallmark_charge?: number;      // flat BIS hallmarking charge
}
```

**Outputs**
```typescript
{
  live_rate_per_gram: number;    // fetched from rate API
  metal_value: number;           // net_weight × rate
  wastage_amount: number;        // (gross_weight × wastage%) × rate
  making_charges: number;
  hallmark_charge: number;
  base_price: number;            // sum of above (pre-GST)
  gst_rate: number;              // 3% for jewellery
  gst_amount: number;
  total_price: number;           // base_price + gst_amount
  rate_timestamp: string;        // ISO 8601 — when rate was fetched
}
```

**Rules**
- Live rate must be fetched fresh for each calculation — NEVER use cached rate older than 15 minutes.
- Record the `rate_timestamp` on the invoice line item for audit.
- Wastage percentage must be within configured bounds (0–15%); reject otherwise.
- Stone weight is NOT subject to metal rate — stones are priced separately if applicable.
- All weights stored with 3 decimal places; all monetary values with 2 decimal places.

---

## `low_stock_alert`

**Purpose**: Evaluate current stock against reorder points and dispatch alerts when threshold is breached.

**Inputs**
```typescript
{
  sku_or_item_id: string;
  store_id: string;
  current_quantity: number;
  current_weight_grams?: number;
}
```

**Outputs**
```typescript
{
  alert_triggered: boolean;
  alert_id?: string;
  sku_or_item_id: string;
  reorder_point: number;
  current_level: number;
  suggested_reorder_quantity: number;
  alert_sent_to: string[];    // user IDs / roles notified
}
```

**Rules**
- Alert is triggered when `current_quantity ≤ reorder_point`.
- Duplicate alerts suppressed: do not re-alert for the same SKU within a 24-hour window.
- Notification targets: configured per store (typically Store Manager + Purchase Manager).
- Suggested reorder quantity = `max_stock_level − current_quantity` (from item config).
- Read-only: this skill only reads and dispatches — it does NOT modify stock.
