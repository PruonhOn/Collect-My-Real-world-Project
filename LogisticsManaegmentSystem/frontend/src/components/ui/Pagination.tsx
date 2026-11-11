import { ChevronLeft, ChevronRight } from 'lucide-react'
import type { PaginationMeta } from '@/types/api'
import { cn } from '@/lib/cn'
import { Select } from '@/components/ui/Select'

/**
 * Server-side pagination control.
 *
 * Page count always comes from the API's `meta.total`, never from the number
 * of rows currently in the DOM. Row count and range come from the server too,
 * so a page showing items 981-1000 of 24,318 is labelled correctly.
 */
export function Pagination({
  meta,
  perPage,
  onPageChange,
  onPerPageChange,
  className,
}: {
  meta: PaginationMeta
  perPage: number
  onPageChange: (page: number) => void
  onPerPageChange: (perPage: number) => void
  className?: string
}) {
  const { current_page, last_page, total, from, to } = meta

  if (total === 0) {
    return null
  }

  // A compact window around the current page, e.g. 4 5 [6] 7 8
  const pages: (number | 'gap')[] = []
  const window = 1

  for (let page = 1; page <= last_page; page += 1) {
    const inWindow = Math.abs(page - current_page) <= window

    if (page === 1 || page === last_page || inWindow) {
      pages.push(page)
    } else if (pages[pages.length - 1] !== 'gap') {
      pages.push('gap')
    }
  }

  return (
    <div
      className={cn(
        'border-ink-200 flex flex-wrap items-center justify-between gap-3 border-t px-4 py-3',
        className,
      )}
    >
      <div className="text-xs text-ink-600">
        Showing <span className="tabular font-medium text-ink-900">{from ?? 0}</span> to{' '}
        <span className="tabular font-medium text-ink-900">{to ?? 0}</span> of{' '}
        <span className="tabular font-medium text-ink-900">{total.toLocaleString()}</span>
      </div>

      <div className="flex items-center gap-4">
        <div className="w-32">
          <Select
            aria-label="Rows per page"
            value={perPage}
            onChange={(event) => onPerPageChange(Number(event.target.value))}
            options={[20, 50, 100].map((size) => ({
              value: size,
              label: `${size} / page`,
            }))}
          />
        </div>

        <nav className="flex items-center gap-1" aria-label="Pagination">
          <PageButton
            onClick={() => onPageChange(current_page - 1)}
            disabled={current_page <= 1}
            label="Previous page"
          >
            <ChevronLeft className="size-4" aria-hidden />
          </PageButton>

          {pages.map((page, index) =>
            page === 'gap' ? (
              <span key={`gap-${index}`} className="px-1 text-xs text-ink-400">
                &hellip;
              </span>
            ) : (
              <button
                key={page}
                type="button"
                onClick={() => onPageChange(page)}
                aria-current={page === current_page ? 'page' : undefined}
                className={cn(
                  'tabular h-8 min-w-8 rounded-md px-2 text-xs font-medium transition-colors',
                  page === current_page
                    ? 'bg-brand-600 text-white'
                    : 'text-ink-600 hover:bg-ink-100 hover:text-ink-900',
                )}
              >
                {page}
              </button>
            ),
          )}

          <PageButton
            onClick={() => onPageChange(current_page + 1)}
            disabled={current_page >= last_page}
            label="Next page"
          >
            <ChevronRight className="size-4" aria-hidden />
          </PageButton>
        </nav>
      </div>
    </div>
  )
}

function PageButton({
  onClick,
  disabled,
  label,
  children,
}: {
  onClick: () => void
  disabled: boolean
  label: string
  children: React.ReactNode
}) {
  return (
    <button
      type="button"
      onClick={onClick}
      disabled={disabled}
      aria-label={label}
      className={cn(
        'flex size-8 items-center justify-center rounded-md transition-colors',
        disabled
          ? 'text-ink-300 cursor-not-allowed'
          : 'text-ink-600 hover:bg-ink-100 hover:text-ink-900',
      )}
    >
      {children}
    </button>
  )
}
