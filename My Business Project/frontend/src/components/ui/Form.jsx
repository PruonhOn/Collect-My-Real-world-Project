import { forwardRef } from 'react'

const BASE =
  'block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-500'

export const Label = ({ children, htmlFor, hint, className = '' }) => (
  <label htmlFor={htmlFor} className={`mb-1.5 block text-sm font-medium text-slate-700 ${className}`}>
    {children}
    {hint && <span className="ml-1 text-xs font-normal text-slate-400">{hint}</span>}
  </label>
)

export const Input = forwardRef(function Input({ className = '', invalid = false, ...props }, ref) {
  return (
    <input
      ref={ref}
      aria-invalid={invalid || undefined}
      className={`${BASE} ${invalid ? 'border-red-400 focus:border-red-500 focus:ring-red-100' : ''} ${className}`}
      {...props}
    />
  )
})

export const Select = forwardRef(function Select({ className = '', invalid = false, children, ...props }, ref) {
  return (
    <select
      ref={ref}
      aria-invalid={invalid || undefined}
      className={`${BASE} ${invalid ? 'border-red-400 focus:border-red-500 focus:ring-red-100' : ''} ${className}`}
      {...props}
    >
      {children}
    </select>
  )
})

export const Textarea = forwardRef(function Textarea({ className = '', invalid = false, ...props }, ref) {
  return (
    <textarea
      ref={ref}
      aria-invalid={invalid || undefined}
      className={`${BASE} min-h-24 resize-y ${invalid ? 'border-red-400 focus:border-red-500 focus:ring-red-100' : ''} ${className}`}
      {...props}
    />
  )
})

export function FieldError({ children }) {
  if (!children) {
    return null
  }

  return <p className="mt-1 text-xs text-red-600">{children}</p>
}

export function FormRow({ label, htmlFor, error, hint, children }) {
  return (
    <div>
      {label && <Label htmlFor={htmlFor} hint={hint}>{label}</Label>}
      {children}
      <FieldError>{error}</FieldError>
    </div>
  )
}

export default Input
