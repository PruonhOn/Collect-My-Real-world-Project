import { useEffect, useState } from 'react'
import { useLanguage } from '../context/LanguageContext'
import { formatPrice } from '../utils/format'

const methods = [
  { id: 'aba', nameKey: 'methodAba', short: 'ABA', color: 'bg-[#003a49]' },
  { id: 'acleda', nameKey: 'methodAcleda', short: 'ACLEDA', color: 'bg-[#c8102e]' },
  { id: 'wing', nameKey: 'methodWing', short: 'WING', color: 'bg-[#0066a8]' },
  { id: 'bakong', nameKey: 'methodBakong', short: 'BAKONG', color: 'bg-[#f59e0b]' },
  { id: 'stripe', nameKey: 'methodStripe', short: 'CARD', color: 'bg-[#635bff]' },
]

export default function PaymentModal({ book, onPay, onClose }) {
  const { t } = useLanguage()
  const [method, setMethod] = useState('aba')
  const [submitting, setSubmitting] = useState(false)

  const handlePay = async () => {
    setSubmitting(true)
    try {
      await onPay(method)
    } catch {
      /* parent handles toast/error; keep modal open */
    } finally {
      setSubmitting(false)
    }
  }

  useEffect(() => {
    const prev = document.body.style.overflow
    document.body.style.overflow = 'hidden'
    const onKey = (e) => {
      if (e.key === 'Escape') onClose()
    }
    window.addEventListener('keydown', onKey)
    return () => {
      document.body.style.overflow = prev
      window.removeEventListener('keydown', onKey)
    }
  }, [onClose])

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
      <div className="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto animate-scale-in">
        <div className="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
          <h2 className="text-lg font-bold text-gray-900">{t('paymentMethodModalTitle')}</h2>
          <button
            onClick={onClose}
            className="w-8 h-8 flex items-center justify-center text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-full transition-colors"
            aria-label={t('close')}
          >
            <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div className="p-6">
          <p className="text-sm text-gray-600">
            <span className="font-semibold text-gray-900">{book.title}</span>
          </p>
          <div className="mt-1 text-xl font-bold text-gray-900">
            {t('paymentAmount')}: {formatPrice(book.price)}
          </div>

          <h3 className="mt-6 mb-3 text-sm font-semibold text-gray-700">{t('choosePaymentMethod')}</h3>
          <div className="grid grid-cols-1 gap-3">
            {methods.map((m) => (
              <button
                key={m.id}
                type="button"
                onClick={() => setMethod(m.id)}
                className={`flex items-center gap-3 p-3 rounded-xl border-2 transition-colors ${
                  method === m.id
                    ? 'border-indigo-600 bg-indigo-50'
                    : 'border-gray-200 hover:border-gray-300'
                }`}
              >
                <span
                  className={`w-12 h-12 rounded-lg ${m.color} text-white flex items-center justify-center font-bold text-xs`}
                >
                  {m.short}
                </span>
                <span className="flex-1 font-semibold text-gray-900">{t(m.nameKey)}</span>
                <span
                  className={`w-4 h-4 rounded-full border-2 flex items-center justify-center ${
                    method === m.id ? 'border-indigo-600' : 'border-gray-300'
                  }`}
                >
                  {method === m.id && <span className="w-2 h-2 rounded-full bg-indigo-600" />}
                </span>
              </button>
            ))}
          </div>

          <p className="mt-4 text-sm text-gray-500 bg-gray-50 rounded-lg p-3">
            {t('paymentInstructions')}
          </p>

          <div className="mt-6 flex flex-col sm:flex-row gap-3">
            <button
              onClick={onClose}
              disabled={submitting}
              className="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-50 disabled:opacity-50"
            >
              {t('cancel')}
            </button>
            <button
              onClick={handlePay}
              disabled={submitting}
              className="flex-1 px-4 py-2.5 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700 disabled:opacity-50"
            >
              {submitting ? t('processingPayment') : t('iHavePaid')}
            </button>
          </div>
        </div>
      </div>
    </div>
  )
}