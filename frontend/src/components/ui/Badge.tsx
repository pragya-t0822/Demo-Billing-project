interface BadgeProps {
  children: React.ReactNode;
  variant?: 'success' | 'warning' | 'danger' | 'primary' | 'info' | 'neutral';
  className?: string;
}

const styles: Record<NonNullable<BadgeProps['variant']>, string> = {
  success: 'bg-green-100 text-green-700',
  warning: 'bg-yellow-100 text-yellow-700',
  danger:  'bg-red-100  text-red-700',
  primary: 'bg-blue-100 text-blue-700',
  info:    'bg-cyan-100  text-cyan-700',
  neutral: 'bg-slate-100 text-slate-600',
};

/** Map common status strings to badge variants */
export const statusVariant = (status: string): BadgeProps['variant'] => {
  const s = status.toUpperCase();
  if (['PAID', 'CONFIRMED', 'MATCHED', 'RECONCILED', 'ACTIVE', 'SETTLED', 'POSTED', 'CLOSED'].includes(s)) return 'success';
  if (['DRAFT', 'PENDING', 'LOW', 'PARTIAL'].includes(s)) return 'warning';
  if (['CANCELLED', 'DISPUTED', 'OVERDUE', 'REVERSED', 'FAILED'].includes(s)) return 'danger';
  if (['HIGH'].includes(s)) return 'primary';
  if (['MEDIUM', 'ADJUSTED'].includes(s)) return 'info';
  return 'neutral';
};

export const Badge = ({ children, variant = 'neutral', className = '' }: BadgeProps) => (
  <span className={`inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold uppercase tracking-wide ${styles[variant]} ${className}`}>
    {children}
  </span>
);
