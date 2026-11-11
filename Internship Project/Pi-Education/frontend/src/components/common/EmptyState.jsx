import { useI18n } from '../../i18n'

export default function EmptyState({ title, description }) {
  const { t } = useI18n()
  return (
    <div className="flex flex-col items-center justify-center gap-4 px-4 py-20 text-center">
      <div className="flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 ring-1 ring-slate-200">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round">
          <path d="M4 19.5A2.5 2.5 0 016.5 17H20" />
          <path d="M6.5 2H20v20H6.5A2.5 2.5 0 014 19.5v-15A2.5 2.5 0 016.5 2z" />
        </svg>
      </div>
      <h3 className="text-lg font-semibold text-slate-800">{title || t('common.empty')}</h3>
      {description && <p className="max-w-sm text-sm leading-relaxed text-slate-500">{description}</p>}
    </div>
  )
}