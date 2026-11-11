import { useEffect, useState } from 'react'
import client from '../../api/client'
import { useLanguage } from '../../context/LanguageContext'
import { useToast } from '../../context/ToastContext'
import Spinner from '../../components/Spinner'
import ErrorState from '../../components/ErrorState'
import EmptyState from '../../components/EmptyState'
import Pagination from '../../components/Pagination'

const statusColor = {
  completed: 'bg-emerald-100 text-emerald-700',
  pending: 'bg-amber-100 text-amber-700',
  failed: 'bg-red-100 text-red-700',
  cancelled: 'bg-gray-100 text-gray-600',
}

export default function PaymentsAdmin() {
  const { t } = useLanguage()
  const { toast } = useToast()
  const [payments, setPayments] = useState([])
  const [meta, setMeta] = useState({})
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const [page, setPage] = useState(1)
  const [approvingId, setApprovingId] = useState(null)

  const statusText = {
    completed: t('statusCompleted'),
    pending: t('statusPending'),
    failed: t('statusFailed'),
    cancelled: t('statusCancelled'),
  }

  useEffect(() => {
    setLoading(true)
    client
      .get(`/admin/payments?page=${page}`)
      .then(({ data }) => {
        setPayments(data.data || [])
        setMeta(data.meta || {})
      })
      .catch(() => setError(t('errLoadPaymentsAdmin')))
      .finally(() => setLoading(false))
  }, [page, t])

  const approve = async (payment) => {
    setApprovingId(payment.id)
    try {
      const { data } = await client.post(`/admin/payments/${payment.id}/approve`)
      toast(data.message, 'success')
      setPayments((prev) =>
        prev.map((p) => (p.id === payment.id ? { ...p, status: 'completed' } : p))
      )
    } catch (err) {
      toast(err.response?.data?.message || t('errApprove'), 'error')
    } finally {
      setApprovingId(null)
    }
  }

  return (
    <div>
      <h1 className="text-2xl font-bold text-gray-900 mb-6">{t('paymentsAdminTitle')}</h1>

      {error ? (
        <ErrorState message={error} />
      ) : loading ? (
        <Spinner />
      ) : payments.length === 0 ? (
        <EmptyState title={t('noPaymentsAdmin')} />
      ) : (
        <>
          <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div className="overflow-x-auto">
              <table className="w-full">
                <thead className="bg-gray-50">
                  <tr>
                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{t('thReference')}</th>
                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{t('thUser')}</th>
                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{t('thBook')}</th>
                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{t('thAmount')}</th>
                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{t('thStatus')}</th>
                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{t('thDate')}</th>
                    <th className="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">{t('thActions')}</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-100">
                  {payments.map((p) => (
                    <tr key={p.id} className="hover:bg-gray-50">
                      <td className="px-4 py-3 text-sm font-mono text-gray-700">{p.reference}</td>
                      <td className="px-4 py-3 text-sm text-gray-800">{p.user?.name}</td>
                      <td className="px-4 py-3 text-sm text-gray-600">{p.purchase?.book?.title || '-'}</td>
                      <td className="px-4 py-3 text-sm font-semibold">${Number(p.amount).toFixed(2)}</td>
                      <td className="px-4 py-3">
                        <span className={`px-2 py-1 text-xs font-semibold rounded-full ${statusColor[p.status] || statusColor.pending}`}>
                          {statusText[p.status] || p.status}
                        </span>
                      </td>
                      <td className="px-4 py-3 text-sm text-gray-500">
                        {new Date(p.created_at).toLocaleString()}
                      </td>
                      <td className="px-4 py-3">
                        {p.status === 'pending' ? (
                          <button
                            onClick={() => approve(p)}
                            disabled={approvingId === p.id}
                            className="px-3 py-1.5 bg-emerald-600 text-white text-xs font-semibold rounded-lg hover:bg-emerald-700 disabled:opacity-50"
                          >
                            {approvingId === p.id ? t('processing') : t('approve')}
                          </button>
                        ) : (
                          <span className="text-xs text-gray-400">—</span>
                        )}
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
