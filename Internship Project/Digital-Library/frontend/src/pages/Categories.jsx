import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import client from '../api/client'
import Spinner from '../components/Spinner'
import ErrorState from '../components/ErrorState'
import { useLanguage } from '../context/LanguageContext'

export default function Categories() {
  const { t } = useLanguage()
  const [categories, setCategories] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)

  useEffect(() => {
    client
      .get('/categories')
      .then(({ data }) => setCategories(data.categories || []))
      .catch(() => setError(t('errLoadCategories')))
      .finally(() => setLoading(false))
  }, [t])

  if (loading) return <Spinner />
  if (error) return <ErrorState message={error} />

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <h1 className="text-3xl font-bold text-gray-900 mb-6">{t('categoriesPageTitle')}</h1>
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        {categories.map((cat) => (
          <Link
            key={cat.id}
            to={`/books?category=${cat.id}`}
            className="bg-white rounded-xl border border-gray-100 p-6 hover:shadow-lg hover:-translate-y-1 transition-all duration-300"
          >
            <div className="flex items-center justify-between mb-3">
              <span className="text-3xl">📚</span>
              <span className="bg-indigo-50 text-indigo-700 text-xs font-semibold px-3 py-1 rounded-full">
                {cat.books_count || 0} {t('booksCount')}
              </span>
            </div>
            <h2 className="text-lg font-bold text-gray-900">{cat.name}</h2>
            <p className="text-sm text-gray-500 mt-1">{cat.description || t('noDescription')}</p>
          </Link>
        ))}
      </div>
    </div>
  )
}
