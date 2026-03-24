/** Format a number as Indian Rupees: ₹1,18,000.00 */
export const formatINR = (amount: number): string =>
  new Intl.NumberFormat('en-IN', {
    style: 'currency',
    currency: 'INR',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(amount);

/** Format a negative amount with parentheses in red: (₹500.00) */
export const formatINRSigned = (amount: number): { text: string; negative: boolean } => ({
  text: amount < 0 ? `(${formatINR(Math.abs(amount))})` : formatINR(amount),
  negative: amount < 0,
});

/** Format grams with 3 decimal places: 10.250 g */
export const formatGrams = (grams: number): string =>
  `${grams.toFixed(3)} g`;

/** Format percentage: 18.00% */
export const formatPct = (pct: number): string =>
  `${pct.toFixed(2)}%`;
