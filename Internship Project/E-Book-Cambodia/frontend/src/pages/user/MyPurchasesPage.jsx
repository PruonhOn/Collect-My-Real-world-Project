import { useQuery } from '@tanstack/react-query'
import { getPayments } from '../../api'
import { useLanguage } from '../../context/LanguageContext'
import { Link } from 'react-router-dom'

const statusColor = (status) => {
  if (status === 'completed') return 'bg-emerald-100 text-emerald-700'
  if (status === 'pending') return 'bg-amber-100 text-amber-700'
  return 'bg-red-100 text-red-600'
}

export default function MyPurchasesPage() {
  const { t } = useLanguage()

  const { data, isLoading } = useQuery({
    queryKey: ['my-payments'],
    refetchInterval: 15000,
    queryFn: () => getPayments({ per_page: 15 }),
  })

  const res = data?.data
  const payments = res?.data || []

  if (isLoading) {
    return <div className="max-w-7xl mx-auto px-4 py-10 text-center">{t('loading')}</div>
  }

  return (
    <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
      <h1 className="text-2xl font-bold text-gray-900 mb-6">{t('myPurchases')}</h1>

      {payments.length === 0 ? (
        <div className="text-center py-16 bg-white rounded-2xl shadow-sm">
          <p className="text-gray-500 mb-4">{t('noBooks')}</p>
          <Link to="/books" className="text-indigo-600 font-medium">
            {t('allBooks')}
          </Link>
        </div>
      ) : (
        <div className="space-y-4">
          {payments.map((payment) => (
            <div key={payment.id} className="bg-white rounded-2xl shadow-sm p-5 flex items-center gap-4">
              <div className="w-12 h-12 rounded-xl bg-indigo-50 flex items-center justify-center">
                <span className="text-indigo-600 font-bold">${payment.amount}</span>
              </div>
              <div className="flex-1 min-w-0">
                <div className="font-semibold text-gray-900 capitalize">{payment.method}</div>
                <div className="text-sm text-gray-500 mt-1">
                  {new Date(payment.created_at).toLocaleDateString()}
                </div>
              </div>
              <div className="text-right">
                <span
                  className={`inline-block text-xs font-medium px-2 py-1 rounded-full capitalize ${statusColor(
                    payment.status
                  )}`}
                >
                  {payment.status}
                </span>
                {payment.status === 'completed' && (
                  <Link to="/my-library" className="block mt-2 text-sm text-indigo-600 font-medium">
                    {t('myLibrary')}
                  </Link>
                )}
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  )
}