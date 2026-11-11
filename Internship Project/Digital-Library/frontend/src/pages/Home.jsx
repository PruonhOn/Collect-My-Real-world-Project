import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import client from '../api/client'
import BookCard from '../components/BookCard'
import Spinner from '../components/Spinner'
import ErrorState from '../components/ErrorState'
import { useLanguage } from '../context/LanguageContext'

export default function Home() {
  const { t } = useLanguage()
  const [books, setBooks] = useState([])
  const [categories, setCategories] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)

  useEffect(() => {
    Promise.all([
      client.get('/books?per_page=8&sort=latest').catch(() => null),
      client.get('/categories').catch(() => null),
    ])
      .then(([b, c]) => {
        setBooks(b?.data?.data || [])
        setCategories(c?.data?.categories || [])
      })
      .catch(() => setError(t('errLoadContent')))
      .finally(() => setLoading(false))
  }, [t])

  if (loading) return <Spinner />

  return (
    <div>
      {/* Hero */}
      <section className="bg-gradient-to-r from-indigo-600 to-purple-600 text-white">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 md:py-24 text-center">
          <h1 className="text-3xl md:text-5xl font-bold">
            {t('homeHeroTitle')}
          </h1>
          <p className="mt-4 text-lg text-indigo-100 max-w-2xl mx-auto">
            {t('homeHeroSubtitle')}
          </p>
          <div className="mt-8 flex flex-col sm:flex-row gap-4 justify-center">
            <Link
              to="/books"
              className="px-6 py-3 bg-white text-indigo-700 font-semibold rounded-lg hover:bg-indigo-50"
            >
              {t('browseBooks')}
            </Link>
            <Link
              to="/register"
              className="px-6 py-3 bg-indigo-800 text-white font-semibold rounded-lg hover:bg-indigo-900"
            >
              {t('startReading')}
            </Link>
          </div>
        </div>
      </section>

      {/* Categories */}
      <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div className="flex items-center justify-between mb-6">
          <h2 className="text-2xl font-bold text-gray-900">{t('categoriesTitle')}</h2>
          <Link to="/categories" className="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
            {t('viewAll')} →
          </Link>
        </div>
        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
          {categories.map((cat) => (
            <Link
              key={cat.id}
              to={`/books?category=${cat.id}`}
              className="bg-white border border-gray-100 rounded-xl p-4 text-center hover:shadow-lg hover:-translate-y-1 transition-all duration-300"
            >
              <div className="text-3xl mb-2">{cat.books_count ? '📚' : '📁'}</div>
              <span className="text-sm font-semibold text-gray-800">{cat.name}</span>
              <span className="block text-xs text-gray-400 mt-1">
                {cat.books_count || 0} {t('booksCount')}
              </span>
            </Link>
          ))}
        </div>
      </section>

      {/* Featured books */}
      <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div className="flex items-center justify-between mb-6">
          <h2 className="text-2xl font-bold text-gray-900">{t('featuredBooks')}</h2>
          <Link to="/books" className="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
            {t('viewAll')} →
          </Link>
        </div>
        {error ? (
          <ErrorState message={error} />
        ) : (
          <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4 sm:gap-6">
            {books.map((book) => (
              <BookCard key={book.id} book={book} />
            ))}
          </div>
        )}
      </section>

      {/* CTA */}
      <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div className="bg-gradient-to-r from-emerald-600 to-teal-600 rounded-2xl p-8 md:p-12 text-center text-white">
          <h2 className="text-2xl md:text-3xl font-bold">{t('readyToRead')}</h2>
          <p className="mt-2 text-emerald-100">
            {t('ctaText')}
          </p>
          <Link
            to="/register"
            className="inline-block mt-6 px-8 py-3 bg-white text-emerald-700 font-semibold rounded-lg hover:bg-emerald-50"
          >
            {t('registerNow')}
          </Link>
        </div>
      </section>
    </div>
  )
}
