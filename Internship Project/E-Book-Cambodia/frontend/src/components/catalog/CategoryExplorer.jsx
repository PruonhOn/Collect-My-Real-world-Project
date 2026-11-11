import { Link } from 'react-router-dom'
import { useLanguage } from '../../context/LanguageContext'
import { categoryLink } from '../../utils/grades'

const KEYWORD_EMOJIS = [
  ['literature', '📖'],
  ['education', '🎓'],
  ['learn', '📚'],
  ['history', '🏛️'],
  ['technology', '💻'],
  ['business', '💼'],
  ['health', '💊'],
  ['math', '🔢'],
  ['mathematics', '🔢'],
  ['science', '🔬'],
  ['art', '🎨'],
  ['language', '🗣️'],
]

const TINTS = [
  'bg-indigo-50 text-indigo-600 ring-indigo-100',
  'bg-emerald-50 text-emerald-600 ring-emerald-100',
  'bg-amber-50 text-amber-600 ring-amber-100',
  'bg-sky-50 text-sky-600 ring-sky-100',
  'bg-rose-50 text-rose-600 ring-rose-100',
  'bg-violet-50 text-violet-600 ring-violet-100',
  'bg-teal-50 text-teal-600 ring-teal-100',
]

function categoryEmoji(name = '') {
  const lower = name.toLowerCase()
  const found = KEYWORD_EMOJIS.find(([kw]) => lower.includes(kw))
  return found ? found[1] : '📚'
}

export default function CategoryExplorer({ categories = [], allCategories = [] }) {
  const { t } = useLanguage()
  const all = allCategories?.length ? allCategories : categories

  if (!categories || categories.length === 0) return null

  return (
    <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-12">
      <div className="text-center mb-8">
        <h2 className="text-2xl sm:text-3xl font-bold text-gray-900">{t('discoverPurpose')}</h2>
        <p className="mt-2 text-gray-500">{t('discoverPurposeSub')}</p>
      </div>

      <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4">
        {categories.map((cat, i) => {
          const tint = TINTS[i % TINTS.length]
          return (
            <Link
              key={cat.id}
              to={categoryLink(all, cat)}
              className="group flex items-center gap-3 sm:gap-4 bg-white rounded-2xl border border-gray-100 shadow-sm p-3.5 sm:p-5 hover:shadow-lg hover:-translate-y-1 hover:border-indigo-100 transition-all"
            >
              <span className={`flex items-center justify-center w-10 h-10 sm:w-12 sm:h-12 rounded-xl ring-1 shrink-0 text-xl sm:text-2xl ${tint}`}>
                {categoryEmoji(cat.name)}
              </span>
              <span className="min-w-0">
                <span className="block text-sm sm:text-base font-semibold text-gray-900 group-hover:text-indigo-600 transition-colors truncate">
                  {cat.display_name || cat.name}
                </span>
                <span className="block mt-0.5 text-[11px] sm:text-xs text-gray-500">
                  📚 {cat.books_count ?? 0} {t('statBooks')}
                </span>
              </span>
              <span className="ml-auto hidden sm:inline-flex text-gray-300 group-hover:text-indigo-500 group-hover:translate-x-0.5 transition-all" aria-hidden="true">
                →
              </span>
            </Link>
          )
        })}
      </div>
    </section>
  )
}