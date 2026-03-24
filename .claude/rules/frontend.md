# 🎨 Frontend Development Rules

Built for performance, financial accuracy, and usability in a high-traffic retail environment.

## Technologies
- **React 19** with **Vite**
- **Tailwind CSS v4** — use `@theme` variables and `@apply`, never hardcoded hex colors
- **Lucide React** — for all icons (no mixing icon libraries)
- **Axios** — for all API calls via service layer
- **React Router v6** — for routing
- **Zustand or React Context** — for global state (keep lightweight)

---

## Module Structure

Each business module is **fully isolated** under `src/modules/`:

```
src/modules/billing/
  BillingPage.tsx           ← Route-level component (thin, delegates to components)
  components/
    InvoiceForm.tsx
    CartItems.tsx
    PaymentPanel.tsx
  hooks/
    useInvoice.ts           ← All invoice state logic here
    useCart.ts
  services/
    billingService.ts       ← All API calls for this module
  types.ts                  ← Module-specific TypeScript types
```

**Rules:**
- No cross-module direct imports — use shared `src/hooks/` or `src/services/` for shared logic.
- Page components are thin — they compose components and wire up hooks, no raw logic.
- Each module owns its own TypeScript types in `types.ts`.

---

## Shared UI Components (`src/components/ui/`)

Always use shared components — never reinvent:

| Component | Usage |
|-----------|-------|
| `Button` | All clickable actions |
| `Card` | Section containers |
| `Input` | All form inputs |
| `Table` | Data grids |
| `Modal` | Dialogs and confirmations |
| `Badge` | Status indicators |
| `Spinner` | Loading states |
| `Alert` | Error/success messages |

**Rules:**
- Never write inline styles — use Tailwind utility classes or theme variables.
- Use `--color-primary`, `--color-accent`, `--color-danger` etc. — never hardcoded hex.
- Components must support `className` prop for extending styles.

---

## POS (Point of Sale) Requirements

POS is the most performance-critical module. These are HARD requirements:

- **< 2 second response time** for all POS interactions.
- **Keyboard-first**: Full checkout flow operable without a mouse.
  - `Enter` to confirm selections
  - `F1`–`F10` for common actions (configurable)
  - Arrow keys for item navigation
- **Optimistic UI**: Show immediate feedback; sync with backend asynchronously.
- **Offline grace**: Degrade gracefully if network is slow — show clear status.
- Barcode scanner support via keyboard input capture.

---

## API Service Layer

All backend communication goes through typed service functions:

```typescript
// src/modules/billing/services/billingService.ts
export const billingService = {
  createInvoice: (data: CreateInvoiceInput): Promise<Invoice> =>
    api.post('/invoices', data),

  processPayment: (id: string, data: PaymentInput): Promise<Payment> =>
    api.post(`/invoices/${id}/payment`, data),
};
```

**Rules:**
- NEVER call `fetch` or `axios` directly inside a component.
- All service functions must be typed with input and output interfaces.
- Handle `401` globally (redirect to login) and `5xx` globally (show error toast).
- Loading states: every async call must set a loading flag — no uncontrolled async UI.

---

## State Management Rules

- Use **local state** (`useState`) for component-level UI state.
- Use **custom hooks** for module-level state (e.g., `useCart`, `useInvoice`).
- Use **Zustand store** for cross-module state (e.g., authenticated user, active store).
- NEVER store sensitive financial data in `localStorage` or `sessionStorage`.

---

## Error & Loading States

Every data-fetching component MUST handle:
1. **Loading**: Show `Spinner` or skeleton — no blank/stuck UI.
2. **Error**: Show `Alert` with a message — no silent failures.
3. **Empty State**: Show meaningful empty message — no blank tables.

---

## Financial Display Rules

- **Currency**: Always display with `₹` symbol and 2 decimal places (e.g., `₹1,18,000.00`).
- **Indian Number Format**: Use lakh/crore formatting (`1,00,000`) not western millions.
- **Negative amounts**: Display in red with parentheses format `(₹500.00)`.
- **GST breakdown**: Always show CGST + SGST or IGST explicitly in invoice views.
- **Weights**: Display in grams with 3 decimal places (e.g., `10.250 g`).
