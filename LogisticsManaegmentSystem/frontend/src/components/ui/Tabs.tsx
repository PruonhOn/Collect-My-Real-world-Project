import { useId, useRef, type ReactNode } from 'react'
import { cn } from '@/lib/cn'

export interface TabItem {
  key: string
  label: string
  badge?: ReactNode
}

/**
 * Tab strip used by the detail pages (Shipment: General, Containers, Tracking,
 * Customs, ...).
 *
 * Implements the WAI-ARIA tabs pattern: arrow keys move between tabs, Home and
 * End jump to the ends, and only the active tab is in the tab order.
 */
export function Tabs({
  items,
  active,
  onChange,
  className,
}: {
  items: TabItem[]
  active: string
  onChange: (key: string) => void
  className?: string
}) {
  const baseId = useId()
  const refs = useRef<Record<string, HTMLButtonElement | null>>({})

  function onKeyDown(event: React.KeyboardEvent) {
    const currentIndex = items.findIndex((item) => item.key === active)
    if (currentIndex === -1) return

    let nextIndex: number | null = null

    switch (event.key) {
      case 'ArrowRight':
        nextIndex = (currentIndex + 1) % items.length
        break
      case 'ArrowLeft':
        nextIndex = (currentIndex - 1 + items.length) % items.length
        break
      case 'Home':
        nextIndex = 0
        break
      case 'End':
        nextIndex = items.length - 1
        break
      default:
        return
    }

    event.preventDefault()

    const nextKey = items[nextIndex].key
    onChange(nextKey)
    refs.current[nextKey]?.focus()
  }

  return (
    <div className={cn('scrollbar-thin overflow-x-auto border-b border-ink-200', className)}>
      <div role="tablist" aria-orientation="horizontal" onKeyDown={onKeyDown} className="flex min-w-max gap-1 px-4 sm:px-6">
        {items.map((item) => {
          const isActive = item.key === active

          return (
            <button
              key={item.key}
              ref={(element) => {
                refs.current[item.key] = element
              }}
              role="tab"
              id={`${baseId}-tab-${item.key}`}
              aria-selected={isActive}
              aria-controls={`${baseId}-panel-${item.key}`}
              tabIndex={isActive ? 0 : -1}
              onClick={() => onChange(item.key)}
              className={cn(
                'relative -mb-px flex items-center gap-1.5 border-b-2 px-3 py-2.5 text-sm font-medium whitespace-nowrap transition-colors',
                isActive
                  ? 'border-brand-600 text-brand-700'
                  : 'text-ink-500 hover:border-ink-300 hover:text-ink-800',
              )}
            >
              {item.label}
              {item.badge !== undefined && (
                <span
                  className={cn(
                    'tabular rounded px-1.5 py-0.5 text-xs',
                    isActive ? 'bg-brand-50 text-brand-700' : 'bg-ink-100 text-ink-600',
                  )}
                >
                  {item.badge}
                </span>
              )}
            </button>
          )
        })}
      </div>
    </div>
  )
}

export function TabPanel({
  tabKey,
  active,
  children,
}: {
  tabKey: string
  active: string
  children: ReactNode
}) {
  // Inactive panels are unmounted, not just hidden, so a heavy tab such as
  // Tracking or Documents does not keep rendering off-screen.
  if (tabKey !== active) return null

  return (
    <div role="tabpanel" tabIndex={0} className="focus:outline-none">
      {children}
    </div>
  )
}
