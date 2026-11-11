import { useQuery } from '@tanstack/react-query'
import { getLibrary, coverUrl } from '../../api'
import { useLanguage } from '../../context/LanguageContext'
import { Link } from 'react-router-dom'

export default function MyLibraryPage() {
  const { t } = useLanguage()

  const { data, isLoading } = useQuery({
    queryKey: ['my-library'],
    queryFn: () => getLibrary(),
  })

  const res = data?.data
  const owned = res?.owned || []
  const shared = res?.shared || []
  const vip = res?.vip || []
  const items = [...owned, ...shared, ...vip]

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
      <h1 className="text-2xl font-bold text-gray-900 mb-6">{t('myLibrary')}</h1>

      {/* Stats */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div className="bg-white rounded-xl p-4 shadow-sm">
          <div className="text-2xl font-bold text-indigo-600">{owned.length}</div>
          <div className="text-sm text-gray-500">{t('myPurchases')}</div>
        </div>
        <div className="bg-white rounded-xl p-4 shadow-sm">
          <div className="text-2xl font-bold text-emerald-600">{shared.length}</div>
          <div className="text-sm text-gray-500">Shared</div>
        </div>
        <div className="bg-white rounded-xl p-4 shadow-sm">
          <div className="text-2xl font-bold text-amber-500">{vip.length}</div>
          <div className="text-sm text-gray-500">VIP</div>
        </div>
      </div>

      {isLoading ? (
        <div className="text-center py-10">{t('loading')}</div>
      ) : items.length === 0 ? (
        <div className="text-center py-16 bg-white rounded-2xl shadow-sm">
          <p className="text-gray-500 mb-4">{t('noBooks')}</p>
          <Link to="/books" className="text-indigo-600 font-medium">
            {t('allBooks')}
          </Link>
        </div>
      ) : (
        <div className="space-y-4">
          {items.map((item) => (
            <Link
              key={`${item.source}-${item.id}`}
              to={`/reader/${item.id}`}
              className="flex items-center gap-4 bg-white rounded-2xl shadow-sm p-4 hover:shadow-md transition-shadow"
            >
              {coverUrl(item) ? (
                <img src={coverUrl(item)} alt="" className="w-14 h-20 object-cover rounded-lg" />
              ) : (
                <div className="w-14 h-20 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600" />
              )}
              <div className="flex-1 min-w-0">
                <div className="font-semibold text-gray-900 truncate">{item.title}</div>
                <div className="text-xs text-gray-500 mt-0.5 truncate">{item.author}</div>
                {item.source === 'shared' && item.shared_by && (
                  <div className="text-xs text-emerald-600 mt-0.5">Shared by {item.shared_by}</div>
                )}
                {item.source === 'vip' && (
                  <div className="text-xs text-amber-600 mt-0.5">VIP · {t('vipBenefits')}</div>
                )}
                <div className="mt-2 flex items-center gap-2">
                  <div className="bg-gray-100 rounded-full h-1.5 flex-1">
                    <div
                      className="bg-indigo-600 h-1.5 rounded-full"
                      style={{ width: `${Math.min(item.progress || 0, 100)}%` }}
                    />
                  </div>
                  <span className="text-[10px] text-gray-400">{Math.round(item.progress || 0)}%</span>
                </div>
              </div>
              <span
                className={`text-xs font-medium px-2.5 py-1 rounded-full capitalize ${
                  item.source === 'vip'
                    ? 'bg-amber-50 text-amber-600'
                    : item.source === 'shared'
                      ? 'bg-emerald-50 text-emerald-600'
                      : 'bg-indigo-50 text-indigo-600'
                }`}
              >
                {item.source}
              </span>
            </Link>
          ))}
        </div>
      )}
    </div>
  )
}