import { useEffect, useState } from 'react'
import { useSearchParams } from 'react-router-dom'
import client from '../api/client'
import BookCard from '../components/BookCard'
import Spinner from '../components/Spinner'
import ErrorState from '../components/ErrorState'
import EmptyState from '../components/EmptyState'
import Pagination from '../components/Pagination'
import { useLanguage } from '../context/LanguageContext'

export default function Books() {
  const { t } = useLanguage()
  const [searchParams, setSearchParams] = useSearchParams()
  const [books, setBooks] = useState([])
  const [categories, setCategories] = useState([])
  const [meta, setMeta] = useState({})
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const [reloadKey, setReloadKey] = useState(0)

  const search = searchParams.get('search') || ''
  const category = searchParams.get('category') || ''
  const sort = searchParams.get('sort') || 'latest'
  const page = Number(searchParams.get('page') || 1)

  useEffect(() => {
    const params = new URLSearchParams()
    if (search) params.set('search', search)
    if (category) params.set('category', category)
    if (sort) params.set('sort', sort)
    params.set('page', page)
    params.set('per_page', 12)
    setLoading(true)
    setError(null)
    client
      .get(`/books?${params.toString()}`)
      .then(({ data }) => {
        setBooks(data.data || [])
        setMeta(data.meta || {})
      })
      .catch(() => setError(t('errLoadBooks')))
      .finally(() => setLoading(false))
  }, [search, category, sort, page, reloadKey, t])

  useEffect(() => {
    client.get('/categories').then(({ data }) => setCategories(data.categories || []))
  }, [])

  const updateParam = (key, value) => {
    const next = new URLSearchParams(searchParams)
    if (value) next.set(key, value)
    else next.delete(key)
    if (key !== 'page') next.delete('page')
    setSearchParams(next)
  }

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <h1 className="text-3xl font-bold text-gray-900 mb-2">{t('booksTitle')}</h1>
      <p className="text-gray-500 mb-6">{t('booksSubtitle')}</p>

      {/* Filters */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-8 flex flex-col md:flex-row gap-3 md:items-center">
        <div className="flex-1">
          <div className="relative">
            <input
              type="text"
              value={search}
              onChange={(e) => {
                updateParam('search', e.target.value)
              }}
              placeholder={t('searchPlaceholder')}
              className="w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
            />
            <svg className="w-5 h-5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>
        </div>
        <select
          value={category}
          onChange={(e) => updateParam('category', e.target.value)}
          className="px-3 py-2 border border-gray-300 rounded-lg bg-white"
        >
          <option value="">{t('allCategories')}</option>
          {categories.map((c) => (
            <option key={c.id} value={c.id}>{c.name}</option>
          ))}
        </select>
        <select
          value={sort}
          onChange={(e) => updateParam('sort', e.target.value)}
          className="px-3 py-2 border border-gray-300 rounded-lg bg-white"
        >
          <option value="latest">{t('latest')}</option>
          <option value="price_asc">{t('priceAsc')}</option>
          <option value="price_desc">{t('priceDesc')}</option>
        </select>
      </div>

      {error ? (
        <ErrorState message={error} onRetry={() => setReloadKey((k) => k + 1)} />
      ) : loading ? (
        <Spinner />
      ) : books.length === 0 ? (
        <EmptyState
          title={t('noBooksFound')}
          message={t('noBooksMsg')}
        />
      ) : (
        <>
          <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4 sm:gap-6">
            {books.map((book) => (
              <BookCard key={book.id} book={book} />
            ))}
          </div>

          <Pagination meta={meta} onPageChange={(p) => updateParam('page', p)} />
        </>
      )}
    </div>
  )
}
