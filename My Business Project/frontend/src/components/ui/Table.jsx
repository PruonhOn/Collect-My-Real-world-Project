export function Table({ children, className = '' }) {
  return (
    <div className={`scroll-thin overflow-x-auto ${className}`}>
      <table className="min-w-full divide-y divide-slate-200 text-left text-sm">{children}</table>
    </div>
  )
}

export function Th({ children, className = '' }) {
  return (
    <th className={`px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500 ${className}`}>
      {children}
    </th>
  )
}

export function Td({ children, className = '' }) {
  return <td className={`px-4 py-3 align-middle text-slate-700 ${className}`}>{children}</td>
}

export function Tr({ children, className = '' }) {
  return <tr className={`border-t border-slate-100 hover:bg-slate-50/60 ${className}`}>{children}</tr>
}

export function EmptyState({ title = 'Nothing here yet', description, action, icon: Icon }) {
  return (
    <div className="flex flex-col items-center justify-center gap-2 px-6 py-12 text-center">
      {Icon && (
        <span className="flex size-11 items-center justify-center rounded-full bg-slate-100 text-slate-400">
          <Icon className="size-5" />
        </span>
      )}
      <p className="text-sm font-medium text-slate-700">{title}</p>
      {description && <p className="max-w-sm text-sm text-slate-500">{description}</p>}
      {action && <div className="mt-2">{action}</div>}
    </div>
  )
}

export default Table
