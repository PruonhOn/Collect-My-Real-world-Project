const TONES = {
  success: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
  danger: 'bg-red-50 text-red-700 ring-red-600/20',
  warning: 'bg-amber-50 text-amber-700 ring-amber-600/20',
  info: 'bg-sky-50 text-sky-700 ring-sky-600/20',
  neutral: 'bg-slate-100 text-slate-700 ring-slate-500/20',
  brand: 'bg-brand-50 text-brand-700 ring-brand-600/20',
}

export function Badge({ children, tone = 'neutral', className = '' }) {
  return (
    <span
      className={`inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium capitalize ring-1 ring-inset ${TONES[tone] ?? TONES.neutral} ${className}`}
    >
      {children}
    </span>
  )
}

export function StatusBadge({ status, tone }) {
  return <Badge tone={tone}>{String(status ?? 'unknown').replace(/_/g, ' ')}</Badge>
}

export default Badge
