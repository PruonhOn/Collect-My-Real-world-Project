import { useQuery } from '@tanstack/react-query'
import { getCategories } from '../../api'
import { Link } from 'react-router-dom'
import { useLanguage } from '../../context/LanguageContext'
import { categoryLink } from '../../utils/grades'

export default function CategoriesPage() {
  const { t, locale } = useLanguage()

  const { data, isLoading } = useQuery({
    queryKey: ['categories-list', locale],
    refetchInterval: 60000,
    queryFn: () => getCategories({ locale }),
  })

  const categories = data?.data?.categories || []

  if (isLoading) {
    return (
      <div className="max-w-7xl mx-auto px-4 py-12">
        <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
          {[...Array(8)].map((_, i) => (
            <div key={i} className="h-32 bg-gray-200 rounded-xl animate-pulse" />
          ))}
        </div>
      </div>
    )
  }

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
      <h1 className="text-3xl font-bold text-gray-900 mb-2">{t('categories')}</h1>
      <p className="text-gray-500 mb-8">{t('bookCategories')}</p>

      <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
        {categories.map((cat) => (
          <Link
            key={cat.id}
            to={categoryLink(categories, cat)}
            className="bg-white rounded-2xl p-6 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all group"
          >
            <div className="text-4xl mb-3">{cat.icon || '📚'}</div>
            <h2 className="font-semibold text-gray-900 group-hover:text-indigo-600">{cat.display_name || cat.name}</h2>
            {(cat.display_description || cat.description) && (
              <p className="mt-1 text-sm text-gray-500 line-clamp-2">{cat.display_description || cat.description}</p>
            )}
            {cat.books_count > 0 && (
              <span className="mt-3 inline-block text-xs text-indigo-600 font-medium">{cat.books_count} {t('books')}</span>
            )}
          </Link>
        ))}
      </div>
    </div>
  )
}