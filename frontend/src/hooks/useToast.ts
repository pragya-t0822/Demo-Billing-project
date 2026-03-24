import { useState, useCallback } from 'react';

export type ToastVariant = 'success' | 'error' | 'warning' | 'info';

export interface Toast {
  id: string;
  message: string;
  variant: ToastVariant;
}

export const useToast = () => {
  const [toasts, setToasts] = useState<Toast[]>([]);

  const add = useCallback((message: string, variant: ToastVariant = 'info') => {
    const id = Math.random().toString(36).slice(2);
    setToasts(prev => [...prev, { id, message, variant }]);
    setTimeout(() => setToasts(prev => prev.filter(t => t.id !== id)), 4000);
  }, []);

  const remove = useCallback((id: string) => {
    setToasts(prev => prev.filter(t => t.id !== id));
  }, []);

  return {
    toasts,
    toast: {
      success: (msg: string) => add(msg, 'success'),
      error:   (msg: string) => add(msg, 'error'),
      warning: (msg: string) => add(msg, 'warning'),
      info:    (msg: string) => add(msg, 'info'),
    },
    remove,
  };
};
