import { useEffect } from 'react'
import { useQuery } from '@tanstack/react-query'
import { QRCodeCanvas } from 'qrcode.react'
import { checkBakong } from '../../api'
import { useLanguage } from '../../context/LanguageContext'

/**
 * Shared Bakong KHQR payment modal, reused by the book purchase and the VIP
 * pages. Renders the QR image and polls the existing backend verification
 * (POST /payments/{id}/bakong/check) until the transaction is confirmed.
 * Activation itself always happens server-side; the UI only reflects it.
 *
 * result  - the checkout API response ({ payment, bakong: { qr, amount, ... } })
 * onPaid  - called once the backend confirms the payment
 * onClosed- called when the user dismisses the modal
 * footer  - optional extra content next to the cancel button (e.g. "Read now")
 */
export default function BakongPaymentModal({ result, onPaid, onClosed, footer }) {
  const { t } = useLanguage()

  const bakong = result?.bakong || {}
  const activated = !!result?.activated

  const checkBakongQuery = useQuery({
    queryKey: ['bakong-check', result?.payment?.id],
    queryFn: () => checkBakong(result.payment.id),
    enabled: !!result?.payment?.id && !activated,
    refetchInterval: (query) => (query.state.data?.data?.paid ? false : 4000),
    retry: 5,
  })

  const checkError = checkBakongQuery.isError
    ? (checkBakongQuery.error?.response?.data?.message || t('purchaseFailed'))
    : ''

  useEffect(() => {
    const check = checkBakongQuery.data?.data
    if (check?.paid && !activated) {
      onPaid?.(check)
    }
  }, [checkBakongQuery.data, activated, onPaid])

  return (
    <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
      <div className="bg-white rounded-2xl p-8 max-w-md w-full">
        <h2 className="text-xl font-bold text-gray-900 mb-4">
          {activated ? t('paymentSuccess') : t('completePayment')}
        </h2>
        {bakong.qr ? (
          <>
            <QRCodeCanvas
              value={bakong.qr}
              size={256}
              className="mx-auto rounded-xl"
              includeMargin
              level="M"
            />
            <p className="mt-4 text-sm text-gray-500 text-center">
              {bakong.amount} {bakong.currency} - {bakong.merchant_name}
            </p>
            <p className="mt-1 text-xs text-gray-400 text-center">
              Scan with the Bakong app to pay
            </p>
          </>
        ) : (
          <div className="bg-indigo-50 rounded-xl p-4 text-indigo-700 text-sm">
            {result?.message || t('demoNotice')}
          </div>
        )}

        {checkError && !activated && (
          <div className="mb-4 rounded-xl bg-red-50 p-3 text-red-700 text-sm">
            {checkError}
          </div>
        )}

        {activated ? (
          <button
            type="button"
            className="mt-5 w-full px-4 py-3 bg-emerald-500 text-white font-semibold rounded-xl flex items-center justify-center gap-2 cursor-default"
            aria-label={t('paymentSuccess')}
          >
            <svg className="w-5 h-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
              <path
                fillRule="evenodd"
                d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z"
                clipRule="evenodd"
              />
            </svg>
            {t('paymentSuccess')}
          </button>
        ) : (
          <div className="mt-5 flex items-center justify-center gap-3 text-gray-600">
            <svg
              className="w-5 h-5 text-indigo-500 animate-spin"
              viewBox="0 0 24 24"
              fill="none"
              aria-hidden="true"
            >
              <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
              <path
                className="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
              />
            </svg>
            <span className="text-sm font-medium">{t('waitingPayment')}</span>
          </div>
        )}

        <div className="mt-6 flex gap-3">
          <button
            onClick={onClosed}
            className="flex-1 px-4 py-2.5 border border-gray-200 rounded-xl text-gray-600 hover:bg-gray-50"
          >
            {t('cancel')}
          </button>
          {activated && footer}
        </div>
      </div>
    </div>
  )
}