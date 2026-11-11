import { useLanguage } from '../../context/LanguageContext'

const LANGUAGE_LABELS = {
  km: 'langKm',
  en: 'langEn',
  zh: 'langZh',
  fr: 'langFr',
}

export default function StatsSection({ stats }) {
  const { t } = useLanguage()

  if (!stats || typeof stats.books !== 'number') return null

  const languageLabel = (code) => t(LANGUAGE_LABELS[code] || code)
  const languages = Array.isArray(stats.languages) ? stats.languages : []
  const languagesText = languages.length ? languages.map(languageLabel).join(', ') : `${stats.languagesCount ?? 0} ${t('statLanguages')}`

  const items = [
    stats.books !== undefined && { value: `${stats.books}+`, label: t('statBooks'), accent: 'text-indigo-600' },
    stats.categories !== undefined && { value: String(stats.categories), label: t('statCategories'), accent: 'text-emerald-600' },
    stats.members !== undefined && { value: String(stats.members), label: t('statMembers'), accent: 'text-amber-600' },
    languages.length > 0 && { value: languagesText, small: true, label: t('statLanguages'), accent: 'text-sky-600' },
  ].filter(Boolean)

  if (items.length === 0) return null

  return (
    <section className="max-w-[78rem] mx-auto px-4 sm:px-6 lg:px-8 pt-14">
      <div className="rounded-3xl bg-white border border-gray-100 shadow-sm p-6 sm:p-10">
        <div className="text-center mb-8">
          <h2 className="text-2xl sm:text-3xl font-bold text-gray-900">{t('statsTitle')}</h2>
        </div>
        <div className="grid grid-cols-2 lg:grid-cols-4 gap-6 text-center">
          {items.map((item, i) => (
            <div key={i} className="px-2 py-4 rounded-2xl bg-gray-50/70">
              <div className={`font-extrabold ${item.small ? 'text-lg leading-snug' : 'text-3xl sm:text-4xl'} ${item.accent}`}>
                {item.value}
              </div>
              <div className="mt-1.5 text-sm text-gray-500 font-medium">{item.label}</div>
            </div>
          ))}
        </div>
      </div>
    </section>
  )
}