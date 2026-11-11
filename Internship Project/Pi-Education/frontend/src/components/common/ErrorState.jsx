import { useI18n } from '../../i18n'

export default function ErrorState({ title, retry }) {
  const { t } = useI18n()
  return (
    <div className="flex min-h-[40vh] flex-col items-center justify-center gap-5 px-4 text-center">
      <div className="flex h-16 w-16 items-center justify-center rounded-2xl bg-red-50 text-red-500 ring-1 ring-red-100">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
          <circle cx="12" cy="12" r="10" />
          <line x1="12" y1="8" x2="12" y2="12" />
          <line x1="12" y1="16" x2="12.01" y2="16" />
        </svg>
      </div>
      <h2 className="text-lg font-semibold text-slate-800">{title || t('common.error')}</h2>
      {retry && (
        <button type="button" onClick={retry} className="btn-primary">
          Retry
        </button>
      )}
    </div>
  )
}