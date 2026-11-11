import { ArrowDown, ArrowUp, ChevronsUpDown } from 'lucide-react'
import type { HTMLAttributes, ReactNode, TdHTMLAttributes, ThHTMLAttributes } from 'react'
import { cn } from '@/lib/cn'

export function Table({ className, ...props }: HTMLAttributes<HTMLTableElement>) {
  return (
    <div className="scrollbar-thin w-full overflow-x-auto">
      <table className={cn('w-full border-collapse text-sm', className)} {...props} />
    </div>
  )
}

export function THead({ className, ...props }: HTMLAttributes<HTMLTableSectionElement>) {
  return <thead className={cn('bg-ink-50', className)} {...props} />
}

export function TBody({ className, ...props }: HTMLAttributes<HTMLTableSectionElement>) {
  return <tbody className={cn('divide-ink-200 divide-y', className)} {...props} />
}

export function TFoot({ className, ...props }: HTMLAttributes<HTMLTableSectionElement>) {
  return (
    <tfoot className={cn('bg-ink-50 border-ink-200 border-t', className)} {...props} />
  )
}

export function TR({
  className,
  ...props
}: HTMLAttributes<HTMLTableRowElement>) {
  return (
    <tr className={cn('hover:bg-ink-50/70 transition-colors', className)} {...props} />
  )
}

export interface THProps extends ThHTMLAttributes<HTMLTableCellElement> {
  /** Pass the API sort key. Omit for a non-sortable column. */
  sortKey?: string
  sortBy?: string
  sortDir?: 'asc' | 'desc'
  onSort?: (key: string) => void
  align?: 'left' | 'center' | 'right'
}

export function TH({
  sortKey,
  sortBy,
  sortDir = 'asc',
  onSort,
  align = 'left',
  className,
  children,
  ...props
}: THProps) {
  const isActive = sortKey !== undefined && sortKey === sortBy

  return (
    <th
      scope="col"
      aria-sort={isActive ? (sortDir === 'asc' ? 'ascending' : 'descending') : undefined}
      className={cn(
        'px-4 py-2.5 text-xs font-semibold tracking-wide text-ink-600 uppercase whitespace-nowrap',
        align === 'right' && 'text-right',
        align === 'center' && 'text-center',
        align === 'left' && 'text-left',
        className,
      )}
      {...props}
    >
      {sortKey && onSort ? (
        <button
          type="button"
          onClick={() => onSort(sortKey)}
          className={cn(
            'hover:text-ink-900 -mx-1 inline-flex items-center gap-1 rounded px-1 py-0.5 transition-colors',
            isActive && 'text-brand-700',
          )}
        >
          {children}
          {isActive ? (
            sortDir === 'asc' ? (
              <ArrowUp className="size-3" aria-hidden />
            ) : (
              <ArrowDown className="size-3" aria-hidden />
            )
          ) : (
            <ChevronsUpDown className="size-3 text-ink-300" aria-hidden />
          )}
        </button>
      ) : (
        children
      )}
    </th>
  )
}

export function TD({
  className,
  align = 'left',
  ...props
}: TdHTMLAttributes<HTMLTableCellElement> & { align?: 'left' | 'center' | 'right' }) {
  return (
    <td
      className={cn(
        'px-4 py-2.5 text-ink-700 align-middle',
        align === 'right' && 'text-right',
        align === 'center' && 'text-center',
        className,
      )}
      {...props}
    />
  )
}

/** Primary cell content: a bold reference plus optional secondary lines. */
export function CellStack({
  primary,
  secondary,
}: {
  primary: ReactNode
  secondary?: ReactNode
}) {
  return (
    <div className="min-w-0">
      <div className="truncate font-medium text-ink-900">{primary}</div>
      {secondary && <div className="mt-0.5 truncate text-xs text-ink-500">{secondary}</div>}
    </div>
  )
}
