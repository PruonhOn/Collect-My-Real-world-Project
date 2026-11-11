import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import client from '../api/client'
import Spinner from '../components/Spinner'
import ErrorState from '../components/ErrorState'
import EmptyState from '../components/EmptyState'
import { useLanguage } from '../context/LanguageContext'

function ProgressBar({ percent }) {
  const p = Math.min(100, Number(percent) || 0)
  return (
    <div className="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
      <div className="bg-indigo-600 h-2 rounded-full transition-all duration-500 ease-out" style={{ width: `${p}%` }} />
    </div>
  )
}

export default function MyLibrary() {
  const { t } = useLanguage()
  const [data, setData] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)

  useEffect(() => {
    client
      .get('/library')
      .then(({ data }) => setData(data))
      .catch(() => setError(t('errLoadLibrary')))
      .finally(() => setLoading(false))
  }, [t])

  if (loading) return <Spinner />
  if (error) return <ErrorState message={error} />
  if (!data) return null

  const allBooks = [...(data.owned || []), ...(data.shared || [])]

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div className="flex items-center justify-between mb-6">
        <div>
          <h1 className="text-3xl font-bold text-gray-900">{t('myLibraryTitle')}</h1>
          <p className="text-gray-500 mt-1">{data.count} {t('booksInLibrary')}</p>
        </div>
        <Link
          to="/books"
          className="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700"
        >
          {t('browseBooksBtn')}
        </Link>
      </div>

      {allBooks.length === 0 ? (
        <EmptyState
          title={t('emptyLibrary')}
          message={t('emptyLibraryMsg')}
          action={
            <Link to="/books" className="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm">
              {t('findBooks')}
            </Link>
          }
        />
      ) : (
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          {allBooks.map((item) => (
            <div key={`${item.source}-${item.id}`} className="bg-white rounded-xl border border-gray-100 overflow-hidden hover:shadow-lg hover:-translate-y-0.5 transition-all duration-300">
              <div className="flex gap-4 p-4">
                <Link to={`/books/${item.id}`} className="shrink-0">
                  {item.cover_image ? (
                    <img src={`${import.meta.env.VITE_API_URL?.replace('/api/v1', '') || ''}/storage/${item.cover_image}`} alt={item.title} className="w-20 h-28 object-cover rounded-lg" />
                  ) : (
                    <div className="w-20 h-28 bg-indigo-50 rounded-lg flex items-center justify-center text-3xl">📖</div>
                  )}
                </Link>
                <div className="flex-1 min-w-0">
                  <span className={`inline-block text-xs font-semibold px-2 py-0.5 rounded-full mb-1 ${
                    item.source === 'shared' ? 'bg-emerald-100 text-emerald-700' : 'bg-indigo-100 text-indigo-700'
                  }`}>
                    {item.source === 'shared' ? `${t('sharedBy')} ${item.shared_by || ''}` : t('owned')}
                  </span>
                  <Link to={`/books/${item.id}`} className="block font-semibold text-gray-900 line-clamp-2 hover:text-indigo-700">
                    {item.title}
                  </Link>
                  <p className="text-xs text-gray-500 mt-1">{item.author}</p>
                  <div className="mt-3">
                    <div className="flex justify-between text-xs text-gray-500 mb-1">
                      <span>{t('topicProgress')}</span>
                      <span>{Math.round(Number(item.progress) || 0)}%</span>
                    </div>
                    <ProgressBar percent={item.progress} />
                  </div>
                </div>
              </div>
              <div className="px-4 pb-4">
                <Link
                  to={`/reader/${item.id}`}
                  className="block text-center w-full py-2 bg-indigo-50 text-indigo-700 text-sm font-medium rounded-lg hover:bg-indigo-100"
                >
                  {t('continueReading')} →
                </Link>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  )
}
