import { useCallback, useEffect, useState } from 'react'
import client from '../api/client'
import { useToast } from '../context/ToastContext'
import { useLanguage } from '../context/LanguageContext'
import Spinner from '../components/Spinner'
import ErrorState from '../components/ErrorState'
import EmptyState from '../components/EmptyState'

export default function Sharing() {
  const { t } = useLanguage()
  const [data, setData] = useState(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const [redeemCode, setRedeemCode] = useState('')
  const [redeeming, setRedeeming] = useState(false)
  const { toast } = useToast()

  const load = useCallback(() => {
    setLoading(true)
    client
      .get('/sharing')
      .then(({ data }) => setData(data))
      .catch(() => setError(t('errLoadSharing')))
      .finally(() => setLoading(false))
  }, [t])

  useEffect(() => {
    load()
  }, [load])

  const handleRedeem = async (e) => {
    e.preventDefault()
    setRedeeming(true)
    try {
      const { data } = await client.post('/redeem', { code: redeemCode })
      toast(data.message)
      setRedeemCode('')
      load()
    } catch (err) {
      toast(err.response?.data?.message || t('redemptionFailed'), 'error')
    } finally {
      setRedeeming(false)
    }
  }

  const revoke = async (sharedAccess) => {
    if (!window.confirm(t('revokeConfirm'))) return
    try {
      await client.delete(`/sharing/${sharedAccess.id}`)
      toast(t('accessRevoked'), 'info')
      load()
    } catch {
      toast(t('errRevokeAccess'), 'error')
    }
  }

  if (loading) return <Spinner />
  if (error) return <ErrorState message={error} />
  if (!data) return null

  const generated = data.generated || []
  const received = data.received || []

  return (
    <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <h1 className="text-3xl font-bold text-gray-900 mb-2">{t('sharingTitle')}</h1>
      <p className="text-gray-500 mb-8">{t('sharingSubtitle')}</p>

      {/* Redeem */}
      <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-8">
        <h2 className="font-semibold text-gray-900 mb-3">{t('redeemKey')}</h2>
        <form onSubmit={handleRedeem} className="flex flex-col sm:flex-row gap-3">
          <input
            value={redeemCode}
            onChange={(e) => setRedeemCode(e.target.value.toUpperCase())}
            placeholder={t('enterSharingCode')}
            className="flex-1 px-4 py-2.5 border border-gray-300 rounded-lg uppercase tracking-wider font-mono"
          />
          <button
            type="submit"
            disabled={redeeming || !redeemCode.trim()}
            className="px-6 py-2.5 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 disabled:opacity-50"
          >
            {redeeming ? t('processing') : t('redeem')}
          </button>
        </form>
        <p className="text-xs text-gray-400 mt-2">
          {t('redeemHint')}
        </p>
      </div>

      {/* Received */}
      <div className="mb-8">
        <h2 className="text-xl font-semibold text-gray-900 mb-4">{t('receivedAccess')}</h2>
        {received.length === 0 ? (
          <EmptyState title={t('noReceived')} message={t('noReceivedMsg')} />
        ) : (
          <div className="space-y-3">
            {received.map((item) => (
              <div key={item.id} className="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex items-center justify-between gap-3">
                <div className="min-w-0">
                  <p className="font-semibold text-gray-900 truncate">{item.book?.title}</p>
                  <p className="text-sm text-gray-500">
                    {t('sharedBy')} {item.sharing_key?.owner?.name || t('unknown')}
                  </p>
                </div>
                <div className="flex items-center gap-3 shrink-0">
                  <span className={`px-2 py-1 text-xs font-semibold rounded-full ${
                    item.status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500'
                  }`}>
                    {item.status === 'active' ? t('statusActive') : t('statusRevoked')}
                  </span>
                  {item.status === 'active' && (
                    <button onClick={() => revoke(item)} className="text-sm text-red-500 hover:text-red-700">
                      {t('revoke')}
                    </button>
                  )}
                </div>
              </div>
            ))}
          </div>
        )}
      </div>

      {/* Generated */}
      <div>
        <h2 className="text-xl font-semibold text-gray-900 mb-4">{t('myGeneratedKeys')}</h2>
        {generated.length === 0 ? (
          <EmptyState
            title={t('noKeysGenerated')}
            message={t('noKeysGeneratedMsg')}
          />
        ) : (
          <div className="space-y-3">
            {generated.map((key) => (
              <div key={key.id} className="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                <div className="flex items-center justify-between gap-3">
                  <div className="min-w-0">
                    <p className="font-semibold text-gray-900 truncate">{key.book?.title}</p>
                    <p className="text-sm text-gray-500">
                      {t('usedCount')} {key.used_count} / {key.max_uses} {t('people')}
                    </p>
                  </div>
                  <div className="flex items-center gap-2 shrink-0">
                    <span className={`px-2 py-1 text-xs font-semibold rounded-full ${
                      key.status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500'
                    }`}>
                      {key.status === 'active' ? t('statusActive') : t('statusExpired')}
                    </span>
                    {key.status === 'active' && (
                      <button
                        onClick={() => { navigator.clipboard.writeText(key.code); toast(t('codeCopied')) }}
                        className="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200"
                        title={t('copyCode')}
                      >
                        {t('copy')}
                      </button>
                    )}
                  </div>
                </div>
                <div className="mt-3 flex items-center gap-3 bg-gray-50 rounded-lg px-3 py-2">
                  <code className="flex-1 font-mono font-semibold tracking-widest text-indigo-700 text-sm select-all">
                    {key.code}
                  </code>
                  <span className="text-xs text-gray-500">
                    {key.expires_at ? `${t('expires')} ${new Date(key.expires_at).toLocaleDateString()}` : t('noExpiry')}
                  </span>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  )
}
