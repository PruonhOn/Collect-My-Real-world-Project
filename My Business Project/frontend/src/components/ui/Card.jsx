export function Card({ children, className = '', ...props }) {
  return (
    <div className={`rounded-xl bg-white shadow-sm ring-1 ring-slate-200 ${className}`} {...props}>
      {children}
    </div>
  )
}

export function CardHeader({ title, description, actions, className = '' }) {
  return (
    <div className={`flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 px-5 py-4 ${className}`}>
      <div>
        {title && <h2 className="text-base font-semibold text-slate-900">{title}</h2>}
        {description && <p className="mt-0.5 text-sm text-slate-500">{description}</p>}
      </div>
      {actions && <div className="flex items-center gap-2">{actions}</div>}
    </div>
  )
}

export function CardBody({ children, className = '' }) {
  return <div className={`px-5 py-4 ${className}`}>{children}</div>
}

export default Card
