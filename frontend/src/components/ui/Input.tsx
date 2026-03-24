import React from 'react';

interface InputProps extends React.InputHTMLAttributes<HTMLInputElement> {
  label?: string;
  error?: string;
  helper?: string;
  leftIcon?: React.ReactNode;
  rightIcon?: React.ReactNode;
}

export const Input = React.forwardRef<HTMLInputElement, InputProps>(
  ({ label, error, helper, leftIcon, rightIcon, className = '', id, ...props }, ref) => {
    const inputId = id ?? label?.toLowerCase().replace(/\s+/g, '-');
    return (
      <div className="w-full">
        {label && (
          <label htmlFor={inputId} className="block text-sm font-medium text-slate-700 mb-1">
            {label}
            {props.required && <span className="text-red-500 ml-1">*</span>}
          </label>
        )}
        <div className="relative">
          {leftIcon && (
            <span className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">{leftIcon}</span>
          )}
          <input
            ref={ref}
            id={inputId}
            className={`
              block w-full rounded-lg border bg-white px-3 py-2 text-sm text-slate-900
              placeholder:text-slate-400
              focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent
              disabled:bg-slate-50 disabled:opacity-60 disabled:cursor-not-allowed
              ${error ? 'border-red-400' : 'border-slate-300'}
              ${leftIcon  ? 'pl-9'  : ''}
              ${rightIcon ? 'pr-9'  : ''}
              ${className}
            `}
            aria-describedby={error ? `${inputId}-error` : helper ? `${inputId}-helper` : undefined}
            aria-invalid={!!error}
            {...props}
          />
          {rightIcon && (
            <span className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400">{rightIcon}</span>
          )}
        </div>
        {error && (
          <p id={`${inputId}-error`} className="mt-1 text-xs text-red-600">{error}</p>
        )}
        {helper && !error && (
          <p id={`${inputId}-helper`} className="mt-1 text-xs text-slate-500">{helper}</p>
        )}
      </div>
    );
  }
);
Input.displayName = 'Input';
