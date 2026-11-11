import type { LucideIcon } from 'lucide-react'
import { cn } from '@/lib/cn'
import { Skeleton } from '@/components/ui/Skeleton'

export type StatTone = 'neutral' | 'brand' | 'success' | 'warning' | 'danger' | 'info' | 'premium'

const TONE_STYLES: Record<StatTone, string> = {
  neutral: 'bg-ink-100 text-ink-600',
  brand: 'bg-brand-50 text-brand-700',
  success: 'bg-success-50 text-success-700',
  warning: 'bg-warning-50 text-warning-700',
  danger: 'bg-danger-50 text-danger-700',
  info: 'bg-info-50 text-info-700',
  premium: 'bg-premium-50 text-premium-700',
}

export interface StatCardProps {
  label: string
  value: React.ReactNode
  icon?: LucideIcon
  tone?: StatTone
  /** Change versus the previous period, e.g. "+12.4%". */
  delta?: string
  deltaDirection?: 'up' | 'down' | 'flat'
  /** Whether an upward change is a good thing; flips the delta colour. */
  higherIsBetter?: boolean
  hint?: string
  loading?: boolean
  onClick?: () => void
}

export function StatCard({
  label,
  value,
  icon: Icon,
  tone = 'neutral',
  delta,
  deltaDirection = 'flat',
  higherIsBetter = true,
  hint,
  loading = false,
  onClick,
}: StatCardProps) {
  const isGood =
    deltaDirection === 'flat'
      ? null
      : (deltaDirection === 'up') === higherIsBetter

  const Wrapper = onClick ? 'button' : 'div'

  return (
    <Wrapper
      {...(onClick ? { type: 'button' as const, onClick } : {})}
      className={cn(
        'rounded-lg border border-ink-200 bg-white p-4 text-left shadow-xs transition-shadow',
        onClick && 'hover:border-brand-300 hover:shadow-sm',
      )}
    >
      <div className="flex items-start justify-between gap-3">
        <p className="text-xs font-medium text-ink-500">{label}</p>

        {Icon && (
          <span className={cn('flex size-8 shrink-0 items-center justify-center rounded-md', TONE_STYLES[tone])}>
            <Icon className="size-4" aria-hidden />
          </span>
        )}
      </div>

      {loading ? (
        <Skeleton className="mt-2.5 h-7 w-24" />
      ) : (
        <p className="tabular mt-2 text-2xl leading-none font-semibold text-ink-900">{value}</p>
      )}

      {delta && !loading && (
        <p
          className={cn(
            'mt-2 text-xs font-medium',
            isGood === null ? 'text-ink-500' : isGood ? 'text-success-700' : 'text-danger-600',
          )}
        >
          {delta}
          {hint && <span className="ml-1 font-normal text-ink-500">{hint}</span>}
        </p>
      )}

      {hint && !delta && !loading && <p className="mt-2 text-xs text-ink-500">{hint}</p>}
    </Wrapper>
  )
}
