import { useEffect, useState } from 'react'
import client from '../../api/client'
import { useLanguage } from '../../context/LanguageContext'
import Spinner from '../../components/Spinner'
import ErrorState from '../../components/ErrorState'
import EmptyState from '../../components/EmptyState'
import Pagination from '../../components/Pagination'

export default function SharingKeysAdmin() {
  const { t } = useLanguage()
  const [keys, setKeys] = useState([])
  const [meta, setMeta] = useState({})
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const [page, setPage] = useState(1)

  useEffect(() => {
    setLoading(true)
    client
      .get(`/admin/sharing-keys?page=${page}`)
      .then(({ data }) => {
        setKeys(data.data || [])
        setMeta(data.meta || {})
      })
      .catch(() => setError(t('errLoadSharingKeys')))
      .finally(() => setLoading(false))
  }, [page, t])

  return (
    <div>
      <h1 className="text-2xl font-bold text-gray-900 mb-6">{t('sharingKeysTitle')}</h1>

      {error ? (
        <ErrorState message={error} />
      ) : loading ? (
        <Spinner />
      ) : keys.length === 0 ? (
        <EmptyState title={t('noSharingKeys')} />
      ) : (
        <>
          <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div className="overflow-x-auto">
              <table className="w-full">
                <thead className="bg-gray-50">
                  <tr>
                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{t('thCode')}</th>
                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{t('thOwner')}</th>
                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{t('thBook')}</th>
                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{t('thUsed')}</th>
                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{t('thStatus')}</th>
                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{t('thExpires')}</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-100">
                  {keys.map((key) => (
                    <tr key={key.id} className="hover:bg-gray-50">
                      <td className="px-4 py-3 font-mono font-semibold text-indigo-700 text-sm">{key.code}</td>
                      <td className="px-4 py-3 text-sm text-gray-800">{key.owner?.name}</td>
                      <td className="px-4 py-3 text-sm text-gray-600">{key.book?.title || '-'}</td>
                      <td className="px-4 py-3 text-sm text-gray-600">{key.used_count} / {key.max_uses}</td>
                      <td className="px-4 py-3">
                        <span className={`px-2 py-1 text-xs font-semibold rounded-full ${
                          key.status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500'
                        }`}>
                          {key.status === 'active' ? t('statusActive') : t('statusExpired')}
                        </span>
                      </td>
                      <td className="px-4 py-3 text-sm text-gray-500">
                        {key.expires_at ? new Date(key.expires_at).toLocaleDateString() : '-'}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
          <Pagination meta={meta} onPageChange={setPage} />
        </>
      )}
    </div>
  )
}
