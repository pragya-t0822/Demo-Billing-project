import { AlertCircle, CheckCircle2, Info, XCircle, X } from 'lucide-react';
import { useState } from 'react';

type AlertVariant = 'success' | 'error' | 'warning' | 'info';

interface AlertProps {
  variant?: AlertVariant;
  title?: string;
  children: React.ReactNode;
  dismissible?: boolean;
  className?: string;
}

const config: Record<AlertVariant, { icon: React.ElementType; cls: string }> = {
  success: { icon: CheckCircle2, cls: 'bg-green-50 border-green-200 text-green-800' },
  error:   { icon: XCircle,      cls: 'bg-red-50   border-red-200   text-red-800'   },
  warning: { icon: AlertCircle,  cls: 'bg-yellow-50 border-yellow-200 text-yellow-800' },
  info:    { icon: Info,         cls: 'bg-blue-50  border-blue-200  text-blue-800'  },
};

export const Alert = ({ variant = 'info', title, children, dismissible, className = '' }: AlertProps) => {
  const [visible, setVisible] = useState(true);
  const { icon: Icon, cls } = config[variant];

  if (!visible) return null;

  return (
    <div className={`flex gap-3 rounded-lg border p-4 text-sm ${cls} ${className}`} role="alert">
      <Icon size={16} className="mt-0.5 shrink-0" />
      <div className="flex-1">
        {title && <p className="font-semibold mb-0.5">{title}</p>}
        <div>{children}</div>
      </div>
      {dismissible && (
        <button onClick={() => setVisible(false)} className="shrink-0 opacity-60 hover:opacity-100">
          <X size={14} />
        </button>
      )}
    </div>
  );
};
