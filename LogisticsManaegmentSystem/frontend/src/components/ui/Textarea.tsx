import { AlertCircle } from 'lucide-react'
import type { ReactNode, TextareaHTMLAttributes } from 'react'
import { useId } from 'react'
import { cn } from '@/lib/cn'

export interface TextareaProps extends TextareaHTMLAttributes<HTMLTextAreaElement> {
  label?: string
  error?: string
  hint?: ReactNode
  containerClassName?: string
}

export function Textarea({
  label,
  error,
  hint,
  className,
  containerClassName,
  rows = 3,
  id,
  ...props
}: TextareaProps) {  const generatedId = useId()
  const textareaId = id ?? generatedId
  const describedBy = error ? `${textareaId}-error` : hint ? `${textareaId}-hint` : undefined

  return (
    <div className={cn('w-full', containerClassName)}>
      {label && (
        <label htmlFor={textareaId} className="mb-1.5 block text-xs font-medium text-ink-700">
          {label}
          {props.required && <span className="ml-0.5 text-danger-600">*</span>}
        </label>
      )}

      <textarea
        id={textareaId}
        rows={rows}
        aria-invalid={error ? true : undefined}
        aria-describedby={describedBy}
        className={cn(
          'block w-full rounded-md border bg-white text-sm text-ink-900 shadow-xs transition-colors',
          'placeholder:text-ink-400',
          'focus:border-brand-500 focus:ring-2 focus:ring-brand-500/25 focus:outline-none',
          'disabled:cursor-not-allowed disabled:bg-ink-100 disabled:text-ink-500',
          error ? 'border-danger-400 focus:border-danger-500 focus:ring-danger-500/25' : 'border-ink-300',
          className,
        )}
        {...props}
      />

      {error ? (
        <p id={`${textareaId}-error`} className="mt-1.5 flex items-center gap-1 text-xs text-danger-600">
          <AlertCircle className="size-3.5 shrink-0" aria-hidden />
          {error}
        </p>
      ) : hint ? (
        <p id={`${textareaId}-hint`} className="mt-1.5 text-xs text-ink-500">
          {hint}
        </p>
      ) : null}
    </div>
  )
}
