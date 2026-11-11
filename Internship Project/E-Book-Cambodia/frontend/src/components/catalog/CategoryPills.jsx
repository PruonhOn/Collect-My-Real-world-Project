import { useLanguage } from '../../context/LanguageContext'
import {
  baseNameOf,
  gradeKey,
  gradeKeyFromName,
  gradeNavLabel,
  isGradeSub,
  parseGradeName,
  slugOfName,
} from '../../utils/grades'

export default function CategoryPills({
  categories = [],
  category = '',
  grade = '',
  onSelectCategory,
  onSelectGrade,
}) {
  const { locale, t } = useLanguage()

  const mainCategories = categories.filter((cat) => !isGradeSub(cat))
  const gradeSubs = categories.filter(isGradeSub)

  const gradeGroups = {}
  gradeSubs.forEach((sub) => {
    const base = baseNameOf(sub)
    if (!gradeGroups[base]) gradeGroups[base] = []
    const num = parseGradeName(sub)
    gradeGroups[base].push({
      id: String(sub.id),
      num,
      key: gradeKey(num),
      label: gradeNavLabel(locale, t, base, num),
    })
  })
  Object.values(gradeGroups).forEach((levels) =>
    levels.sort((a, b) => (a.num === -1 ? 0 : a.num) - (b.num === -1 ? 0 : b.num))
  )

  let activeBaseName = ''
  if (category) {
    const main = mainCategories.find(
      (cat) =>
        slugOfName(cat.name) === String(category).toLowerCase() ||
        String(cat.id) === String(category)
    )
    if (main) activeBaseName = main.name
  }
  if (!activeBaseName && grade) {
    gradeSubs.forEach((sub) => {
      if (gradeKeyFromName(sub) === grade) activeBaseName = baseNameOf(sub)
    })
  }

  const activeGrades = activeBaseName ? gradeGroups[activeBaseName] || [] : []
  const showGradeRow = activeGrades.length > 0

  const selectCategory = (value) => {
    onSelectCategory(value || '')
    onSelectGrade('')
  }

  return (
    <div className="sticky top-16 lg:top-[7rem] z-30 bg-white/85 backdrop-blur-xl border-b border-gray-100/80">
      <div
        className="flex items-center gap-2 overflow-x-auto py-2 px-4 sm:px-6 lg:px-8 category-pills-scroll"
        role="tablist"
        aria-label={t('bookCategories')}
      >
        <button
          type="button"
          role="tab"
          aria-selected={category === ''}
          onClick={() => selectCategory('')}
          className={`shrink-0 whitespace-nowrap px-4 py-1.5 text-sm font-medium rounded-full transition-all duration-200 active:scale-95 ${
            category === ''
              ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30'
              : 'bg-white text-gray-600 border border-gray-200 hover:border-indigo-300 hover:text-indigo-600'
          }`}
        >
          {t('filterAll')}
        </button>

        {[...mainCategories]
          .sort((a, b) => String(a.name).localeCompare(String(b.name)))
          .map((cat) => {
            const key = slugOfName(cat.name)
            const grouped = gradeGroups[cat.name]?.length > 0
            const active = category === key || (grouped && activeBaseName === cat.name)
            return (
              <button
                key={String(cat.id)}
                type="button"
                role="tab"
                aria-selected={active}
                onClick={() => selectCategory(key)}
                className={`shrink-0 whitespace-nowrap px-4 py-1.5 text-sm font-medium rounded-full transition-all duration-200 active:scale-95 ${
                  active
                    ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30'
                    : 'bg-white text-gray-600 border border-gray-200 hover:border-indigo-300 hover:text-indigo-600'
                }`}
              >
                {cat.display_name || cat.name}
              </button>
            )
          })}
      </div>

      {showGradeRow && (
        <div
          className="flex items-center gap-2 overflow-x-auto border-t border-gray-100/80 bg-indigo-50/70 py-2 px-4 sm:px-6 lg:px-8 category-pills-scroll"
          role="tablist"
          aria-label={t('browseByGrade')}
        >
          <button
            type="button"
            role="tab"
            aria-selected={grade === ''}
            onClick={() => onSelectGrade('')}
            className={`shrink-0 whitespace-nowrap px-3 py-1 text-xs font-medium rounded-full transition-all duration-200 active:scale-95 ${
              grade === ''
                ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30'
                : 'bg-indigo-50 text-indigo-700 border border-indigo-100 hover:border-indigo-300 hover:bg-indigo-100'
            }`}
          >
            {t('gradeAll')}
          </button>
          {activeGrades.map((level) => (
            <button
              key={level.id}
              type="button"
              role="tab"
              aria-selected={grade === level.key}
              onClick={() => onSelectGrade(level.key)}
              className={`shrink-0 whitespace-nowrap px-3 py-1 text-xs font-medium rounded-full transition-all duration-200 active:scale-95 ${
                grade === level.key
                  ? 'bg-indigo-600 text-white shadow-sm shadow-indigo-600/30'
                  : 'bg-indigo-50 text-indigo-700 border border-indigo-100 hover:border-indigo-300 hover:bg-indigo-100'
              }`}
            >
              {level.label}
            </button>
          ))}
        </div>
      )}

      <style>{`
        .category-pills-scroll {
          scrollbar-width: none;
          -ms-overflow-style: none;
        }
        .category-pills-scroll::-webkit-scrollbar {
          display: none;
        }
      `}</style>
    </div>
  )
}