import { useQuery } from '@tanstack/react-query'
import { useSearchParams } from 'react-router-dom'
import { getBooks, getCategories } from '../../api'
import BookGrid from '../../components/BookGrid'
import { useLanguage } from '../../context/LanguageContext'

export default function BooksPage() {
  const { t } = useLanguage()
  const [searchParams, setSearchParams] = useSearchParams()

  const search = searchParams.get('search') || ''
  const type = searchParams.get('type') || ''
  const category = searchParams.get('category') || ''
  const sort = searchParams.get('sort') || 'latest'

  const { data, isLoading, isError, refetch } = useQuery({
    queryKey: ['books', { search, type, category, sort }],
    queryFn: () =>
      getBooks({
        search,
        category,
        free: type === 'free' ? '1' : undefined,
        sort,
        per_page: 12,
      }),
  })

  const { data: categoriesData } = useQuery({
    queryKey: ['categories-all'],
    queryFn: () => getCategories(),
  })

  const res = data?.data
  const books = res?.data || []
  const pagination = res
  const categories = categoriesData?.data?.categories || []

  const updateParam = (key, value) => {
    const params = new URLSearchParams(searchParams)
    if (value) {
      params.set(key, value)
    } else {
      params.delete(key)
    }
    setSearchParams(params)
  }

  const filterClass = (active) =>
    `px-4 py-2 text-sm rounded-lg font-medium ${
      active ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 border hover:border-indigo-300'
    }`

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div className="mb-8">
        <h1 className="text-3xl font-bold text-gray-900 mb-2">{t('allBooks')}</h1>
        <p className="text-gray-500">{books.length} {t('books')}</p>
      </div>

      {/* Filters */}
      <div className="flex flex-col md:flex-row gap-3 mb-8">
        <div className="flex-1">
          <input
            type="text"
            value={search}
            onChange={(e) => updateParam('search', e.target.value)}
            placeholder={t('search')}
            className="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500"
          />
        </div>
        <select
          value={category}
          onChange={(e) => updateParam('category', e.target.value)}
          className="px-4 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500"
        >
          <option value="">{t('bookCategories')}</option>
          {categories.map((cat) => (
            <option key={cat.id} value={cat.id}>
              {cat.name}
            </option>
          ))}
        </select>
        <select
          value={type}
          onChange={(e) => updateParam('type', e.target.value)}
          className="px-4 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500"
        >
          <option value="">{t('bookType')}</option>
          <option value="free">{t('free')}</option>
          <option value="paid">{t('paid')}</option>
        </select>
        <select
          value={sort}
          onChange={(e) => updateParam('sort', e.target.value)}
          className="px-4 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500"
        >
          <option value="latest">Latest</option>
          <option value="price_asc">Price: Low to High</option>
          <option value="price_desc">Price: High to Low</option>
        </select>
      </div>

      {isError ? (
        <div className="text-center py-10">
          <p className="text-red-500 mb-2">{t('error')}</p>
          <button onClick={() => refetch()} className="text-indigo-600 font-medium">
            {t('retry')}
          </button>
        </div>
      ) : (
        <BookGrid books={books} loading={isLoading} />
      )}

      {pagination && pagination.last_page > 1 && (
        <div className="mt-8 flex justify-center gap-2">
          <button
            disabled={pagination.current_page <= 1}
            onClick={() => updateParam('page', String(pagination.current_page - 1))}
            className="px-4 py-2 bg-white border rounded-lg disabled:opacity-50"
          >
            {t('previous')}
          </button>
          <span className="px-4 py-2 text-gray-600">
            {pagination.current_page} / {pagination.last_page}
          </span>
          <button
            disabled={pagination.current_page >= pagination.last_page}
            onClick={() => updateParam('page', String(pagination.current_page + 1))}
            className="px-4 py-2 bg-white border rounded-lg disabled:opacity-50"
          >
            {t('next')}
          </button>
        </div>
      )}
    </div>
  )
}