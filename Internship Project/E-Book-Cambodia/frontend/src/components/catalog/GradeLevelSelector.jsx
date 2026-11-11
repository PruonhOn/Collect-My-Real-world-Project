import { useLanguage } from '../../context/LanguageContext'
import {
  baseNameOf,
  gradeDisplayName,
  gradeKey,
  parseGradeName,
  slugOfName,
} from '../../utils/grades'

const isGradeSub = (cat) =>
  /[-–—]\s*(?:grade\s*\d+|kindergarten)\s*$/i.test(String(cat.name || ''))

export default function GradeLevelSelector({
  categories = [],
  category = '',
  grade = '',
  onSelectGrade,
}) {
  const { locale, t } = useLanguage()

  const mainCategories = categories.filter((cat) => !isGradeSub(cat))
  const gradeSubs = categories.filter(isGradeSub)

  let activeBaseName = ''
  if (category) {
    const main = mainCategories.find(
      (cat) =>
        slugOfName(cat.name) === String(category).toLowerCase() ||
        String(cat.id) === String(category)
    )
    if (main) activeBaseName = main.name
  }

  const levels = gradeSubs
    .filter((sub) => baseNameOf(sub) === activeBaseName)
    .map((sub) => {
      const num = parseGradeName(sub)
      return {
        id: String(sub.id),
        num,
        key: gradeKey(num),
        label: gradeDisplayName(locale, t, num),
        books: Number(sub.books_count || 0),
      }
    })
    .sort((a, b) => {
      const an = a.num === -1 ? 0 : a.num
      const bn = b.num === -1 ? 0 : b.num
      return an - bn
    })

  if (!activeBaseName || levels.length === 0) return null

  const baseCategory = mainCategories.find((cat) => cat.name === activeBaseName)
  const totalBooks =
    Number(baseCategory?.books_count || 0) + levels.reduce((sum, level) => sum + level.books, 0)

  const pick = (key) => {
    onSelectGrade(key)
    document.getElementById('library')?.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }

  return (
    <section className="rounded-3xl border border-indigo-100 bg-gradient-to-b from-indigo-50 to-white p-5 sm:p-7 shadow-sm">
      <div className="flex flex-wrap items-center justify-between gap-2 mb-5">
        <h2 className="text-base sm:text-lg font-bold text-gray-900">
          {baseCategory?.display_name || activeBaseName} — {t('chooseGrade')}
        </h2>
        <span className="inline-flex items-center gap-1.5 text-xs text-gray-500">
          <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122" />
          </svg>
          {t('browseByGrade')}
        </span>
      </div>

      <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-7 gap-2.5 sm:gap-3">
        <button
          type="button"
          onClick={() => pick('')}
          aria-pressed={grade === ''}
          className={`group flex flex-col items-center justify-center gap-1 rounded-2xl border px-2 py-4 text-center transition-all duration-200 active:scale-95 ${
            grade === ''
              ? 'bg-indigo-600 border-indigo-600 text-white shadow-lg shadow-indigo-600/25 ring-2 ring-indigo-200'
              : 'bg-white border-indigo-200 text-gray-800 hover:border-indigo-400 hover:shadow-md'
          }`}
        >
          <span className="flex items-center justify-center w-9 h-9 rounded-full bg-indigo-100 text-indigo-600 font-extrabold text-sm group-hover:bg-indigo-600 group-hover:text-white transition-colors">
            {locale === 'km' ? 'គ្រប់' : 'ALL'}
          </span>
          <span className="text-xs font-bold leading-tight">{t('gradeAll')}</span>
          <span className={`text-[10px] font-medium ${grade === '' ? 'text-indigo-100' : 'text-gray-400'}`}>
            {totalBooks} {t('statBooks')}
          </span>
        </button>

        {levels.map((level) => {
          const active = grade === level.key
          const badge =
            level.num === -1
              ? locale === 'km'
                ? 'មត្តេយ្យ'
                : 'KG'
              : String(level.num)
          return (
            <button
              key={level.id}
              type="button"
              onClick={() => pick(level.key)}
              aria-pressed={active}
              className={`group flex flex-col items-center justify-center gap-1 rounded-2xl border px-2 py-4 text-center transition-all duration-200 active:scale-95 ${
                active
                  ? 'bg-indigo-600 border-indigo-600 text-white shadow-lg shadow-indigo-600/25 ring-2 ring-indigo-200'
                  : 'bg-white border-indigo-200 text-gray-800 hover:border-indigo-400 hover:shadow-md'
              }`}
            >
              <span className="flex items-center justify-center w-9 h-9 rounded-full bg-indigo-100 text-indigo-600 font-extrabold text-sm group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                {badge}
              </span>
              <span className="text-[11px] font-bold leading-tight sm:text-xs">{level.label}</span>
              <span className={`text-[10px] font-medium ${active ? 'text-indigo-100' : 'text-gray-400'}`}>
                {level.books} {t('statBooks')}
              </span>
            </button>
          )
        })}
      </div>
    </section>
  )
}