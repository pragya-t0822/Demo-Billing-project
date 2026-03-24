import { CheckCircle2, XCircle, AlertCircle, Info, X } from 'lucide-react';
import type { Toast, ToastVariant } from '../../hooks/useToast';

const config: Record<ToastVariant, { icon: React.ElementType; cls: string }> = {
  success: { icon: CheckCircle2, cls: 'bg-green-600' },
  error:   { icon: XCircle,      cls: 'bg-red-600'   },
  warning: { icon: AlertCircle,  cls: 'bg-yellow-500' },
  info:    { icon: Info,         cls: 'bg-blue-600'   },
};

interface ToastItemProps { toast: Toast; onRemove: (id: string) => void; }

const ToastItem = ({ toast, onRemove }: ToastItemProps) => {
  const { icon: Icon, cls } = config[toast.variant];
  return (
    <div className={`flex items-center gap-3 px-4 py-3 rounded-xl text-white shadow-lg min-w-72 max-w-sm ${cls}`}>
      <Icon size={16} className="shrink-0" />
      <p className="flex-1 text-sm font-medium">{toast.message}</p>
      <button onClick={() => onRemove(toast.id)} className="opacity-70 hover:opacity-100 shrink-0">
        <X size={14} />
      </button>
    </div>
  );
};

interface ToastContainerProps { toasts: Toast[]; onRemove: (id: string) => void; }

export const ToastContainer = ({ toasts, onRemove }: ToastContainerProps) => (
  <div className="fixed bottom-6 right-6 z-[100] flex flex-col gap-2" aria-live="polite">
    {toasts.map(t => <ToastItem key={t.id} toast={t} onRemove={onRemove} />)}
  </div>
);
