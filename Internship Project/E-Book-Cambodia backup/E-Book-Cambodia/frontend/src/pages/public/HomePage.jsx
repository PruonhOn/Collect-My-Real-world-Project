import { useQuery } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import { getBooks, getCategories } from '../../api'
import BookGrid from '../../components/BookGrid'
import BookSlider from '../../components/BookSlider'
import CategorySlider from '../../components/CategorySlider'
import { useLanguage } from '../../context/LanguageContext'

function CategoryBooksSection({ category }) {
  const { t } = useLanguage()
  const { data, isLoading } = useQuery({
    queryKey: ['books', 'category', category.id],
    queryFn: () => getBooks({ category_id: category.id, per_page: 8 }),
  })
  const books = data?.data?.data || []
  if (!isLoading && books.length === 0) return null
  return (
    <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
      <div className="flex items-center justify-between mb-4">
        <h2 className="text-xl font-bold text-gray-900">{category.name}</h2>
        <Link
          to={`/categories/${category.id}`}
          className="text-indigo-600 text-sm font-medium hover:text-indigo-700"
        >
          {t('viewAll')} →
        </Link>
      </div>
      <BookSlider books={books} loading={isLoading} />
    </section>
  )
}

export default function HomePage() {
  const { t } = useLanguage()

  const { data: newBooksData, isLoading: loadingNew } = useQuery({
    queryKey: ['books', 'new'],
    queryFn: () => getBooks({ sort: 'latest', per_page: 8 }),
  })

  const { data: freeBooksData, isLoading: loadingFree } = useQuery({
    queryKey: ['books', 'free'],
    queryFn: () => getBooks({ free: '1', per_page: 8 }),
  })

  const { data: paidBooksData, isLoading: loadingPaid } = useQuery({
    queryKey: ['books', 'paid'],
    queryFn: () => getBooks({ free: '0', per_page: 8 }),
  })

  const { data: categoriesData } = useQuery({
    queryKey: ['categories'],
    queryFn: () => getCategories(),
  })

  const newBooks = newBooksData?.data?.data || []
  const freeBooks = freeBooksData?.data?.data || []
  const paidBooks = paidBooksData?.data?.data || []
  const categories = categoriesData?.data?.categories || []

  return (
    <div>
      {/* Hero Section */}
      <section className="bg-gradient-to-r from-indigo-700 via-indigo-600 to-purple-600 text-white">
        <div className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-20 md:py-28 text-center">
          <h1 className="text-4xl md:text-5xl font-bold leading-tight mb-4">
            {t('welcome')} <br />
            <span className="text-indigo-200">{t('appName')}</span>
          </h1>
          <p className="text-lg text-indigo-100 mb-8">{t('tagline')}</p>
          <div className="flex gap-4 justify-center flex-wrap">
            <Link
              to="/books"
              className="px-6 py-3 bg-white text-indigo-700 font-semibold rounded-xl hover:bg-indigo-50 transition-colors"
            >
              {t('allBooks')}
            </Link>
            <Link
              to="/categories"
              className="px-6 py-3 border-2 border-white/40 text-white font-semibold rounded-xl hover:bg-white/10 transition-colors"
            >
              {t('categories')}
            </Link>
          </div>
        </div>
      </section>

      {/* Categories */}
      <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div className="text-center mb-6">
          <h2 className="text-2xl font-bold text-gray-900">{t('categories')}</h2>
          <Link to="/categories" className="inline-block mt-1 text-indigo-600 text-sm font-medium hover:text-indigo-700">
            {t('viewAll')} →
          </Link>
        </div>
        <CategorySlider categories={categories} />
      </section>

      {/* Books by Category */}
      {categories.map((cat) => (
        <CategoryBooksSection key={cat.id} category={cat} />
      ))}

      {/* New Books */}
      <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div className="text-center mb-6">
          <h2 className="text-2xl font-bold text-gray-900">{t('newReleases')}</h2>
        </div>
        <BookGrid books={newBooks} loading={loadingNew} />
      </section>

      {/* Free Books */}
      <section className="bg-white py-12 mt-6">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-6">
            <h2 className="text-2xl font-bold text-gray-900">
              {t('freeBooks')}
              <span className="ml-2 text-xs bg-emerald-100 text-emerald-700 px-2 py-1 rounded-full align-middle">
                {t('free')}
              </span>
            </h2>
            <Link
              to="/books?type=free"
              className="inline-block mt-1 text-indigo-600 text-sm font-medium hover:text-indigo-700"
            >
              {t('viewAll')} →
            </Link>
          </div>
          <BookGrid books={freeBooks} loading={loadingFree} />
        </div>
      </section>

      {/* Paid Books */}
      <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div className="text-center mb-6">
          <h2 className="text-2xl font-bold text-gray-900">
              {t('paidBooks')}
              <span className="ml-2 text-xs bg-indigo-100 text-indigo-700 px-2 py-1 rounded-full align-middle">
                {t('paid')}
              </span>
            </h2>
            <Link
              to="/books?type=paid"
              className="inline-block mt-1 text-indigo-600 text-sm font-medium hover:text-indigo-700"
            >
              {t('viewAll')} →
            </Link>
          </div>
          <BookGrid books={paidBooks} loading={loadingPaid} />
      </section>
    </div>
  )
}