import { useEffect, useState } from 'react'
import client from '../api/client'
import Spinner from '../components/Spinner'
import ErrorState from '../components/ErrorState'
import EmptyState from '../components/EmptyState'
import { useLanguage } from '../context/LanguageContext'

const statusColor = {
  completed: 'bg-emerald-100 text-emerald-700',
  pending: 'bg-amber-100 text-amber-700',
  failed: 'bg-red-100 text-red-700',
  cancelled: 'bg-gray-100 text-gray-600',
}

export default function Payments() {
  const { t } = useLanguage()
  const [payments, setPayments] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)

  useEffect(() => {
    client
      .get('/payments')
      .then(({ data }) => setPayments(data.data || []))
      .catch(() => setError(t('errLoadPayments')))
      .finally(() => setLoading(false))
  }, [t])

  const statusText = {
    completed: t('statusCompleted'),
    pending: t('statusPending'),
    failed: t('statusFailed'),
    cancelled: t('statusCancelled'),
  }

  const methodText = {
    aba: t('methodAba'),
    acleda: t('methodAcleda'),
    wing: t('methodWing'),
    bakong: t('methodBakong'),
  }

  if (loading) return <Spinner />
  if (error) return <ErrorState message={error} />

  return (
    <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <h1 className="text-3xl font-bold text-gray-900 mb-6">{t('paymentHistory')}</h1>

      {payments.length === 0 ? (
        <EmptyState
          title={t('noPaymentsYet')}
          message={t('noPaymentsMsg')}
        />
      ) : (
        <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{t('thReference')}</th>
                  <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{t('thAmount')}</th>
                  <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{t('thMethod')}</th>
                  <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{t('thStatus')}</th>
                  <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{t('thDate')}</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {payments.map((p) => (
                  <tr key={p.id} className="hover:bg-gray-50">
                    <td className="px-4 py-3 text-sm font-mono text-gray-700">{p.reference}</td>
                    <td className="px-4 py-3 text-sm font-semibold text-gray-900">
                      ${Number(p.amount).toFixed(2)}
                    </td>
                    <td className="px-4 py-3 text-sm text-gray-600 capitalize">{methodText[p.method] || p.method}</td>
                    <td className="px-4 py-3">
                      <span className={`px-2 py-1 text-xs font-semibold rounded-full ${statusColor[p.status] || statusColor.pending}`}>
                        {statusText[p.status] || p.status}
                      </span>
                    </td>
                    <td className="px-4 py-3 text-sm text-gray-500">
                      {new Date(p.created_at).toLocaleDateString()}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}
    </div>
  )
}
