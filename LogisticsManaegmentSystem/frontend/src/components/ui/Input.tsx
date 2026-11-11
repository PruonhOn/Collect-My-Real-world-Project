import { AlertCircle } from 'lucide-react'
import type { InputHTMLAttributes, ReactNode } from 'react'
import { useId } from 'react'
import { cn } from '@/lib/cn'

// `prefix` is already an HTML attribute (a `string`), so it has to be dropped
// from the base before this component redefines it as a `ReactNode` adornment.
export interface InputProps extends Omit<InputHTMLAttributes<HTMLInputElement>, 'prefix'> {
  label?: string
  error?: string
  hint?: ReactNode
  /** Renders in the input before the text. */
  prefix?: ReactNode
  suffix?: ReactNode
  containerClassName?: string
}

export function Input({
  label,
  error,
  hint,
  prefix,
  suffix,
  className,
  containerClassName,
  id,
  ...props
}: InputProps) {
  const generatedId = useId()
  const inputId = id ?? generatedId
  const describedBy = error ? `${inputId}-error` : hint ? `${inputId}-hint` : undefined

  return (
    <div className={cn('w-full', containerClassName)}>
      {label && (
        <label
          htmlFor={inputId}
          className="mb-1.5 block text-xs font-medium text-ink-700"
        >
          {label}
          {props.required && <span className="ml-0.5 text-danger-600">*</span>}
        </label>
      )}

      <div className="relative">
        {prefix && (
          <span className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-ink-400">
            {prefix}
          </span>
        )}

        <input
          id={inputId}
          aria-invalid={error ? true : undefined}
          aria-describedby={describedBy}
          className={cn(
            'block h-9.5 w-full rounded-md border bg-white text-sm text-ink-900 shadow-xs transition-colors',
            'placeholder:text-ink-400',
            'focus:border-brand-500 focus:ring-2 focus:ring-brand-500/25 focus:outline-none',
            'disabled:cursor-not-allowed disabled:bg-ink-100 disabled:text-ink-500',
            error ? 'border-danger-400 focus:border-danger-500 focus:ring-danger-500/25' : 'border-ink-300',
            prefix && 'pl-9',
            suffix && 'pr-9',
            className,
          )}
          {...props}
        />

        {suffix && (
          <span className="absolute inset-y-0 right-0 flex items-center pr-3 text-ink-400">
            {suffix}
          </span>
        )}
      </div>

      {error ? (
        <p id={`${inputId}-error`} className="mt-1.5 flex items-center gap-1 text-xs text-danger-600">
          <AlertCircle className="size-3.5 shrink-0" aria-hidden />
          {error}
        </p>
      ) : hint ? (
        <p id={`${inputId}-hint`} className="mt-1.5 text-xs text-ink-500">
          {hint}
        </p>
      ) : null}
    </div>
  )
}
