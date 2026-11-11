import type { HTMLAttributes } from 'react'
import { cn } from '@/lib/cn'

export type Tone = 'neutral' | 'brand' | 'success' | 'warning' | 'danger' | 'info' | 'purple' | 'premium'

const TONES: Record<Tone, string> = {
  neutral: 'bg-ink-100 text-ink-700 ring-ink-200',
  brand: 'bg-brand-50 text-brand-700 ring-brand-200',
  success: 'bg-success-50 text-success-700 ring-success-500/25',
  warning: 'bg-warning-50 text-warning-700 ring-warning-500/25',
  danger: 'bg-danger-50 text-danger-700 ring-danger-500/25',
  info: 'bg-info-50 text-info-700 ring-info-500/25',
  purple: 'bg-purple-50 text-purple-700 ring-purple-500/25',
  premium: 'bg-premium-50 text-premium-700 ring-premium-200',
}

export interface BadgeProps extends HTMLAttributes<HTMLSpanElement> {
  tone?: Tone
  dot?: boolean
}

export function Badge({ tone = 'neutral', dot = false, className, children, ...props }: BadgeProps) {
  return (
    <span
      className={cn(
        'inline-flex items-center gap-1.5 rounded-md px-2 py-0.5 text-xs font-medium whitespace-nowrap ring-1 ring-inset',
        TONES[tone],
        className,
      )}
      {...props}
    >
      {dot && <span className="size-1.5 rounded-full bg-current" aria-hidden />}
      {children}
    </span>
  )
}
