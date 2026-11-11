import { useI18n } from '../../i18n'

export default function Loading({ full = false }) {
  const { t } = useI18n()
  return (
    <div
      className={`flex items-center justify-center ${full ? 'min-h-[60vh]' : 'py-12'}`}
      role="status"
      aria-live="polite"
    >
      <div className="flex flex-col items-center gap-4">
        <div className="relative flex h-14 w-14 items-center justify-center">
          <div className="absolute inset-0 animate-spin rounded-full border-4 border-indigo-200 border-t-indigo-600" />
          <div className="absolute inset-1.5 rounded-full border-4 border-transparent border-r-violet-400" />
          <span className="text-xs font-extrabold text-indigo-600">Pi</span>
        </div>
        <span className="text-sm font-medium text-slate-500">{t('common.loading')}</span>
      </div>
    </div>
  )
}