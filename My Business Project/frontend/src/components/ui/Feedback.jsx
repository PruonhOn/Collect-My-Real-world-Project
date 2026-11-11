import { AlertCircle, CheckCircle2, Info, TriangleAlert } from 'lucide-react'

const TONES = {
  info: { wrapper: 'bg-sky-50 text-sky-800 ring-sky-600/20', icon: Info },
  success: { wrapper: 'bg-emerald-50 text-emerald-800 ring-emerald-600/20', icon: CheckCircle2 },
  warning: { wrapper: 'bg-amber-50 text-amber-800 ring-amber-600/20', icon: TriangleAlert },
  danger: { wrapper: 'bg-red-50 text-red-800 ring-red-600/20', icon: AlertCircle },
}

export function Alert({ tone = 'info', title, children, action }) {
  const config = TONES[tone] ?? TONES.info
  const Icon = config.icon

  return (
    <div className={`flex items-start gap-3 rounded-lg px-4 py-3 text-sm ring-1 ring-inset ${config.wrapper}`}>
      <Icon className="mt-0.5 size-4 shrink-0" />
      <div className="flex-1">
        {title && <p className="font-medium">{title}</p>}
        {children && <div className={title ? 'mt-0.5' : ''}>{children}</div>}
      </div>
      {action}
    </div>
  )
}

export function Spinner({ className = 'size-5' }) {
  return (
    <span
      className={`inline-block animate-spin rounded-full border-2 border-slate-300 border-t-brand-600 ${className}`}
      role="status"
      aria-label="Loading"
    />
  )
}

export function LoadingBlock({ label = 'Loading…', className = 'py-12' }) {
  return (
    <div className={`flex items-center justify-center gap-3 text-sm text-slate-500 ${className}`}>
      <Spinner />
      {label}
    </div>
  )
}

export function StatCard({ label, value, hint, icon: Icon, tone = 'brand' }) {
  const tones = {
    brand: 'bg-brand-50 text-brand-700',
    emerald: 'bg-emerald-50 text-emerald-700',
    amber: 'bg-amber-50 text-amber-700',
    sky: 'bg-sky-50 text-sky-700',
  }

  return (
    <div className="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
      <div className="flex items-start justify-between gap-3">
        <div>
          <p className="text-sm font-medium text-slate-500">{label}</p>
          <p className="mt-1 text-2xl font-semibold tracking-tight text-slate-900">{value}</p>
          {hint && <p className="mt-1 text-xs text-slate-500">{hint}</p>}
        </div>
        {Icon && (
          <span className={`flex size-10 items-center justify-center rounded-lg ${tones[tone] ?? tones.brand}`}>
            <Icon className="size-5" />
          </span>
        )}
      </div>
    </div>
  )
}

export function Pagination({ meta, onPageChange }) {
  if (!meta || !meta.last_page || meta.last_page <= 1) {
    return null
  }

  const from = (meta.current_page - 1) * meta.per_page + 1
  const to = Math.min(meta.total, meta.current_page * meta.per_page)

  return (
    <div className="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-5 py-3 text-sm text-slate-600">
      <span>
        Showing {from}-{to} of {meta.total}
      </span>
      <div className="flex items-center gap-2">
        <button
          type="button"
          className="rounded-md px-3 py-1.5 text-sm ring-1 ring-slate-300 transition hover:bg-slate-50 disabled:opacity-40"
          disabled={meta.current_page <= 1}
          onClick={() => onPageChange(meta.current_page - 1)}
        >
          Previous
        </button>
        <span className="text-xs text-slate-500">
          Page {meta.current_page} of {meta.last_page}
        </span>
        <button
          type="button"
          className="rounded-md px-3 py-1.5 text-sm ring-1 ring-slate-300 transition hover:bg-slate-50 disabled:opacity-40"
          disabled={meta.current_page >= meta.last_page}
          onClick={() => onPageChange(meta.current_page + 1)}
        >
          Next
        </button>
      </div>
    </div>
  )
}

export default Alert
