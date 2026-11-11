import { ChevronDown } from 'lucide-react'
import type { ReactNode, SelectHTMLAttributes } from 'react'
import { useId } from 'react'
import { cn } from '@/lib/cn'

export interface SelectOption {
  value: string | number
  label: string
  disabled?: boolean
}

export interface SelectProps extends Omit<SelectHTMLAttributes<HTMLSelectElement>, 'children'> {
  label?: string
  error?: string
  hint?: ReactNode
  options: SelectOption[]
  placeholder?: string
  containerClassName?: string
}

export function Select({
  label,
  error,
  hint,
  options,
  placeholder,
  className,
  containerClassName,
  id,
  ...props
}: SelectProps) {
  const generatedId = useId()
  const selectId = id ?? generatedId

  return (
    <div className={cn('w-full', containerClassName)}>
      {label && (
        <label htmlFor={selectId} className="mb-1.5 block text-xs font-medium text-ink-700">
          {label}
          {props.required && <span className="ml-0.5 text-danger-600">*</span>}
        </label>
      )}

      <div className="relative">
        <select
          id={selectId}
          aria-invalid={error ? true : undefined}
          className={cn(
            'block h-9.5 w-full appearance-none rounded-md border bg-white pr-9 pl-3 text-sm text-ink-900 shadow-xs transition-colors',
            'focus:border-brand-500 focus:ring-2 focus:ring-brand-500/25 focus:outline-none',
            'disabled:cursor-not-allowed disabled:bg-ink-100 disabled:text-ink-500',
            error ? 'border-danger-400' : 'border-ink-300',
            className,
          )}
          {...props}
        >
          {placeholder && (
            <option value="" disabled>
              {placeholder}
            </option>
          )}
          {options.map((option) => (
            <option key={option.value} value={option.value} disabled={option.disabled}>
              {option.label}
            </option>
          ))}
        </select>

        <ChevronDown
          className="pointer-events-none absolute top-1/2 right-3 size-4 -translate-y-1/2 text-ink-400"
          aria-hidden
        />
      </div>

      {error ? (
        <p className="mt-1.5 text-xs text-danger-600">{error}</p>
      ) : hint ? (
        <p className="mt-1.5 text-xs text-ink-500">{hint}</p>
      ) : null}
    </div>
  )
}
