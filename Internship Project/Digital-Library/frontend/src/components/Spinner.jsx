import { useLanguage } from '../context/LanguageContext'

export default function Spinner({ size = 40, label: labelProp }) {
  const { t } = useLanguage()
  const label = labelProp ?? t('loading')
  return (
    <div className="flex flex-col items-center justify-center py-16 text-gray-500">
      <div className="relative" style={{ width: size, height: size }} role="status" aria-label={label}>
        <div className="absolute inset-0 rounded-full border-4 border-indigo-100" />
        <div className="absolute inset-0 rounded-full border-4 border-indigo-600 border-t-transparent animate-spin" />
      </div>
      <span className="mt-3 text-sm animate-pulse">{label}</span>
    </div>
  )
}
