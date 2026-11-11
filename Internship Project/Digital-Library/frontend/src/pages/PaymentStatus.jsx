import { Link, useSearchParams } from 'react-router-dom'
import { useLanguage } from '../context/LanguageContext'

export default function PaymentStatus() {
  const { t } = useLanguage()
  const [searchParams] = useSearchParams()
  const result = searchParams.get('result') || 'paid'

  const paid = result !== 'cancelled'

  return (
    <div className="min-h-[70vh] flex items-center justify-center py-12">
      <div className="w-full max-w-md text-center bg-white rounded-2xl shadow-lg p-10">
        <div className={`text-6xl mb-4 ${paid ? '' : 'opacity-80'}`}>{paid ? '🎉' : '🙁'}</div>
        <h1 className="text-2xl font-bold text-gray-900">{paid ? t('paymentPaidTitle') : t('paymentCancelledTitle')}</h1>
        <p className="mt-3 text-gray-600">{paid ? t('paymentPaidMsg') : t('paymentCancelledMsg')}</p>
        <div className="mt-8 flex flex-col sm:flex-row gap-3 justify-center">
          {paid ? (
            <Link
              to="/library"
              className="px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700"
            >
              {t('goToLibrary')}
            </Link>
          ) : (
            <Link
              to="/books"
              className="px-6 py-3 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700"
            >
              {t('backToBooks')}
            </Link>
          )}
          {!paid && (
            <Link
              to="/library"
              className="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-50"
            >
              {t('myLibrary')}
            </Link>
          )}
        </div>
      </div>
    </div>
  )
}