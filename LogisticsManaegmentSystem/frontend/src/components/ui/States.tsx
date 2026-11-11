import { AlertTriangle, Inbox, RefreshCw } from 'lucide-react'
import type { ReactNode } from 'react'
import { Button } from '@/components/ui/Button'

/**
 * A table or panel with no records.
 *
 * Always states *why* it is empty and what to do next. A bare "No data" makes
 * an operator think the filter is broken rather than that there is nothing to
 * show yet.
 */
export function EmptyState({
  title = 'No records found',
  description,
  action,
  icon,
  className,
}: {
  title?: string
  description?: string
  action?: ReactNode
  icon?: ReactNode
  className?: string
}) {
  return (
    <div className={`flex flex-col items-center justify-center px-6 py-14 text-center ${className ?? ''}`}>
      <div className="bg-ink-100 text-ink-400 mb-3 flex size-11 items-center justify-center rounded-full">
        {icon ?? <Inbox className="size-5" aria-hidden />}
      </div>
      <h3 className="text-sm font-semibold text-ink-900">{title}</h3>
      {description && <p className="mt-1 max-w-sm text-sm text-ink-500">{description}</p>}
      {action && <div className="mt-4">{action}</div>}
    </div>
  )
}

/**
 * A failed request. Always offers a retry, because most failures in this
 * system are transient (timeout, stale connection) rather than permanent.
 */
export function ErrorState({
  title = 'Something went wrong',
  message,
  onRetry,
  retrying = false,
  className,
}: {
  title?: string
  message?: string
  onRetry?: () => void
  retrying?: boolean
  className?: string
}) {
  return (
    <div className={`flex flex-col items-center justify-center px-6 py-14 text-center ${className ?? ''}`}>
      <div className="bg-danger-50 text-danger-600 mb-3 flex size-11 items-center justify-center rounded-full">
        <AlertTriangle className="size-5" aria-hidden />
      </div>
      <h3 className="text-sm font-semibold text-ink-900">{title}</h3>
      {message && <p className="mt-1 max-w-md text-sm text-ink-500">{message}</p>}
      {onRetry && (
        <Button
          variant="outline"
          size="sm"
          className="mt-4"
          onClick={onRetry}
          loading={retrying}
          icon={<RefreshCw className="size-3.5" aria-hidden />}
        >
          Try again
        </Button>
      )}
    </div>
  )
}
