import { useLanguage } from '../../context/LanguageContext'
import { useAuth } from '../../context/AuthContext'
import { slugOfName } from '../../utils/grades'

const LANGUAGE_LABELS = {
  km: 'langKm',
  en: 'langEn',
  zh: 'langZh',
  fr: 'langFr',
}

export default function BookFilters({
  access,
  setAccess,
  sort,
  setSort,
  category,
  setCategory,
  categories = [],
  language,
  setLanguage,
  languages = [],
  onClear,
}) {
  const { t } = useLanguage()
  const { isAuthenticated } = useAuth()

  // The purchased shelf is per viewer, so it only exists once signed in.
  const pills = [
    ...(isAuthenticated ? [{ key: 'owned', label: t('purchased') }] : []),
    { key: '', label: t('filterAll') },
    { key: 'free', label: t('free') },
    { key: 'paid', label: t('paid') },
    { key: 'vip', label: 'VIP' },
  ]

  const selectClass =
    'px-3 py-2 text-sm rounded-xl border border-gray-200 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 text-gray-700'

  return (
    <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
      <div className="flex flex-wrap items-center gap-2">
        {pills.map((pill) => (
          <button
            key={pill.key || 'all'}
            type="button"
            onClick={() => setAccess(pill.key)}
            className={`px-3.5 py-1.5 text-sm font-medium rounded-full transition-colors ${
              access === pill.key
                ? 'bg-indigo-600 text-white shadow-sm'
                : 'bg-white text-gray-600 border border-gray-200 hover:border-indigo-300 hover:text-indigo-600'
            }`}
          >
            {pill.label}
          </button>
        ))}
      </div>

      <div className="flex flex-wrap items-center gap-2">
        <select value={category} onChange={(e) => setCategory(e.target.value)} className={selectClass} aria-label={t('bookCategories')}>
          <option value="">{t('bookCategories')}</option>
          {categories.map((cat) => (
            <option key={cat.id} value={slugOfName(cat.name)}>
              {cat.display_name || cat.name}
            </option>
          ))}
        </select>

        <select value={language} onChange={(e) => setLanguage(e.target.value)} className={selectClass} aria-label={t('filterLanguage')}>
          <option value="">{t('filterLanguage')} · {t('filterAll')}</option>
          {(languages.length ? languages : ['km', 'en']).map((code) => (
            <option key={code} value={code}>
              {t(LANGUAGE_LABELS[code] || code)}
            </option>
          ))}
        </select>

        <select value={sort} onChange={(e) => setSort(e.target.value)} className={selectClass} aria-label="sort">
          <option value="latest">{t('sortLatest')}</option>
          <option value="popular">{t('filterPopular')}</option>
          <option value="price_asc">{t('sortPriceLow')}</option>
          <option value="price_desc">{t('sortPriceHigh')}</option>
        </select>

        <button
          type="button"
          onClick={onClear}
          className="px-3 py-2 text-sm font-medium text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-xl transition-colors"
        >
          {t('clearAll')}
        </button>
      </div>
    </div>
  )
}