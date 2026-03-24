/** Format ISO string → "23 Mar 2026" */
export const formatDate = (iso: string): string =>
  new Date(iso).toLocaleDateString('en-IN', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  });

/** Today's date as YYYY-MM-DD */
export const today = (): string => new Date().toISOString().split('T')[0];

/** First day of current month as YYYY-MM-DD */
export const startOfMonth = (): string => {
  const d = new Date();
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-01`;
};
