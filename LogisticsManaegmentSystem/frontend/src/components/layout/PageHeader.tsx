import type { ReactNode } from 'react'

export interface Breadcrumb {
  label: string
  to?: string
}

export function PageHeader({
  title,
  description,
  breadcrumbs,
  actions,
  children,
}: {
  title: string
  description?: string
  breadcrumbs?: Breadcrumb[]
  actions?: ReactNode
  /** Filter bar rendered directly under the title row. */
  children?: ReactNode
}) {
  return (
    <div className="border-ink-200 border-b bg-white">
      <div className="px-4 py-4 sm:px-6">
        {breadcrumbs && breadcrumbs.length > 0 && (
          <nav aria-label="Breadcrumb" className="mb-1.5">
            <ol className="text-xs text-ink-500">
              {breadcrumbs.map((crumb, index) => (
                <li key={`${crumb.label}-${index}`} className="inline-flex items-center">
                  {index > 0 && <span className="px-1.5 text-ink-300">/</span>}
                  {crumb.to ? (
                    <a href={crumb.to} className="hover:text-brand-700 transition-colors">
                      {crumb.label}
                    </a>
                  ) : (
                    <span className="text-ink-700">{crumb.label}</span>
                  )}
                </li>
              ))}
            </ol>
          </nav>
        )}

        <div className="flex flex-wrap items-start justify-between gap-3">
          <div className="min-w-0">
            <h1 className="truncate text-lg font-semibold text-ink-900">{title}</h1>
            {description && <p className="mt-0.5 text-sm text-ink-500">{description}</p>}
          </div>

          {actions && <div className="flex shrink-0 flex-wrap items-center gap-2">{actions}</div>}
        </div>

        {children && <div className="mt-4">{children}</div>}
      </div>
    </div>
  )
}
