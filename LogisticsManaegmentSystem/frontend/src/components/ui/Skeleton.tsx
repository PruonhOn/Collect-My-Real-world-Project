import { cn } from '@/lib/cn'

/** Placeholder rows shown while a table loads, to avoid a layout jump. */
export function Skeleton({ className }: { className?: string }) {
  return <div className={cn('bg-ink-200 animate-pulse rounded', className)} />
}

export function TableSkeleton({ rows = 8, columns = 6 }: { rows?: number; columns?: number }) {
  return (
    <div className="divide-ink-200 divide-y" aria-hidden>
      {Array.from({ length: rows }).map((_, rowIndex) => (
        <div key={rowIndex} className="flex items-center gap-4 px-4 py-3">
          {Array.from({ length: columns }).map((__, colIndex) => (
            <Skeleton
              key={colIndex}
              className={cn('h-3.5', colIndex === 0 ? 'w-40' : 'flex-1')}
            />
          ))}
        </div>
      ))}
    </div>
  )
}

export function CardSkeleton({ className }: { className?: string }) {
  return (
    <div className={cn('rounded-lg border border-ink-200 bg-white p-5', className)}>
      <Skeleton className="h-3 w-24" />
      <Skeleton className="mt-3 h-7 w-32" />
      <Skeleton className="mt-4 h-2.5 w-full" />
    </div>
  )
}
