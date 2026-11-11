import { useQuery } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import { authorGetStats, authorGetBooks, coverUrl } from '../../api'
import BookStatusBadge from '../../components/BookStatusBadge'
import { useAuthor } from '../../context/AuthorContext'
import { useLanguage } from '../../context/LanguageContext'

export default function AuthorDashboardPage() {
  const { t } = useLanguage()
  const { author } = useAuthor()

  const { data: statsData, isLoading: statsLoading } = useQuery({
    queryKey: ['author-stats'],
    refetchInterval: 15000,
    queryFn: () => authorGetStats(),
  })

  const { data: booksData } = useQuery({
    queryKey: ['author-books', 'recent'],
    refetchInterval: 15000,
    queryFn: () => authorGetBooks({ per_page: 5 }),
  })

  const stats = statsData?.data?.stats
  const books = booksData?.data?.data || []

  const statCards = [
    { label: t('books'), value: stats?.books ?? 0, tint: 'bg-indigo-50 text-indigo-700', icon: '📚' },
    { label: t('publishedBooks'), value: stats?.published ?? 0, tint: 'bg-emerald-50 text-emerald-700', icon: '✅' },
    { label: t('status_pending'), value: stats?.pending ?? 0, tint: 'bg-amber-50 text-amber-700', icon: '⏳' },
    { label: t('status_approved'), value: stats?.approved ?? 0, tint: 'bg-blue-50 text-blue-700', icon: '👍' },
    { label: t('status_rejected'), value: stats?.rejected ?? 0, tint: 'bg-red-50 text-red-700', icon: '⚠️' },
    { label: t('draftBooks'), value: stats?.drafts ?? 0, tint: 'bg-gray-100 text-gray-700', icon: '📝' },
    { label: t('totalViews'), value: stats?.views ?? 0, tint: 'bg-sky-50 text-sky-700', icon: '👁️' },
    { label: t('copiesSold'), value: stats?.sold ?? 0, tint: 'bg-rose-50 text-rose-700', icon: '🛒' },
    {
      label: t('revenue'),
      value: `$${Number(stats?.revenue ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2 })}`,
      tint: 'bg-violet-50 text-violet-700',
      icon: '💰',
    },
  ]

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-gray-900">{t('authorDashboard')}</h1>
        <p className="text-gray-500 text-sm mt-1">
          {author?.name}
          {author?.email ? ` · ${author.email}` : ''}
        </p>
      </div>

      {statsLoading ? (
        <div className="p-8 text-center text-gray-500">{t('loading')}</div>
      ) : (
        <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
          {statCards.map((stat) => (
            <div key={stat.label} className={`rounded-2xl p-5 ${stat.tint}`}>
              <div className="text-2xl mb-2">{stat.icon}</div>
              <p className="text-2xl font-bold">{stat.value}</p>
              <p className="text-xs opacity-80">{stat.label}</p>
            </div>
          ))}
        </div>
      )}

      <div className="bg-white rounded-2xl shadow-sm overflow-hidden">
        <div className="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
          <h2 className="text-lg font-semibold text-gray-900">{t('myBooks')}</h2>
          <Link to="/author/books/new" className="px-4 py-2 bg-emerald-600 text-white rounded-xl font-medium hover:bg-emerald-700 text-sm">
            + {t('addBook')}
          </Link>
        </div>

        {books.length === 0 ? (
          <div className="p-10 text-center">
            <p className="text-gray-400 mb-3">{t('noAuthorBooks')}</p>
            <Link
              to="/author/books/new"
              className="inline-block px-5 py-2.5 bg-emerald-600 text-white rounded-xl font-medium hover:bg-emerald-700"
            >
              {t('createFirstBook')}
            </Link>
          </div>
        ) : (
          <div className="divide-y divide-gray-100">
            {books.map((book) => (
              <div key={book.id} className="px-6 py-4 flex items-center gap-4 hover:bg-gray-50">
                {book.cover_image ? (
                  <img
                    src={coverUrl(book)}
                    alt={book.title}
                    className="w-12 h-16 object-contain bg-white p-1.5 rounded-lg shrink-0"
                  />
                ) : (
                  <div className="w-12 h-16 rounded-lg bg-indigo-50 flex items-center justify-center text-xl shrink-0">
                    📖
                  </div>
                )}
                <div className="min-w-0 flex-1">
                  <p className="text-sm font-semibold text-gray-900 truncate">{book.title}</p>
                  <p className="text-xs text-gray-500">{book.category?.name || '—'}</p>
                </div>
                <div className="ml-auto flex shrink-0 items-center gap-3">
                  <span className="text-xs text-gray-500">
                    {Number(book.view_count || 0).toLocaleString()} {t('views')}
                  </span>
                  <BookStatusBadge book={book} />
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  )
}