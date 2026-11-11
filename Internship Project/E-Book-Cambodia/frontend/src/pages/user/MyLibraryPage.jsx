import { useQuery } from '@tanstack/react-query'
import { getLibrary, coverUrl } from '../../api'
import { useLanguage } from '../../context/LanguageContext'
import { useMembership } from '../../hooks/useMembership'
import MembershipBadge from '../../components/membership/MembershipBadge'
import VipExpiryNote from '../../components/membership/VipExpiryNote'
import VipUpgradeCard from '../../components/membership/VipUpgradeCard'
import CrownIcon from '../../components/membership/CrownIcon'
import { Link } from 'react-router-dom'

export default function MyLibraryPage() {
  const { t } = useLanguage()
  const { isVip } = useMembership()

  const { data, isLoading } = useQuery({
    queryKey: ['my-library'],
    refetchInterval: 15000,
    queryFn: () => getLibrary(),
  })

  const res = data?.data
  const owned = res?.owned || []
  const shared = res?.shared || []
  const vip = res?.vip || []
  const items = [...owned, ...shared, ...vip]

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
      <div className="flex flex-wrap items-center justify-between gap-3 mb-6">
        <h1 className="text-2xl font-bold text-gray-900">{t('myLibrary')}</h1>
        <MembershipBadge size="md" full />
      </div>

      {/* Membership strip: a quiet navy-and-gold line for VIP members, a short
          upgrade path for everyone else. */}
      {isVip ? (
        <div className="mb-8 flex flex-wrap items-center gap-x-3 gap-y-1 rounded-xl border border-amber-400/40 bg-gradient-to-br from-[#BF953F]/10 via-[#FCF6BA]/10 to-[#B38728]/10 px-4 py-3 backdrop-blur-md shadow-[0_0_15px_rgba(212,175,55,0.15)]">
          <span className="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-gradient-to-br from-[#BF953F] via-[#FCF6BA] to-[#B38728] text-white shrink-0 shadow-[0_0_10px_rgba(212,175,55,0.3)]">
            <CrownIcon className="w-3.5 h-3.5" />
          </span>
          <span className="text-xs font-bold text-amber-200 uppercase tracking-wide">
            {t('vipMember')}
          </span>
          <VipExpiryNote className="text-xs text-amber-100/90" />
        </div>
      ) : (
        <div className="mb-8 max-w-md">
          <VipUpgradeCard compact />
        </div>
      )}

      {/* Stats */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div className="bg-white rounded-xl p-4 shadow-sm">
          <div className="text-2xl font-bold text-indigo-600">{owned.length}</div>
          <div className="text-sm text-gray-500">{t('myPurchases')}</div>
        </div>
        <div className="bg-white rounded-xl p-4 shadow-sm">
          <div className="text-2xl font-bold text-emerald-600">{shared.length}</div>
          <div className="text-sm text-gray-500">{t('shared')}</div>
        </div>
        <div className="bg-white rounded-xl p-4 shadow-sm">
          <div className="text-2xl font-bold text-amber-500">{vip.length}</div>
          <div className="text-sm text-gray-500">{t('vip')}</div>
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
                <img src={coverUrl(item)} alt="" className="w-14 h-20 object-contain bg-white p-1.5 rounded-lg" />
              ) : (
                <div className="w-14 h-20 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600" />
              )}
              <div className="flex-1 min-w-0">
                <div className="font-semibold text-gray-900 truncate">{item.title}</div>
                <div className="text-xs text-gray-500 mt-0.5 truncate">{item.author}</div>
                {item.source === 'shared' && item.shared_by && (
                  <div className="text-xs text-emerald-600 mt-0.5">
                    {t('shared')} · {item.shared_by}
                  </div>
                )}
                {item.source === 'vip' && (
                  <div className="flex items-center gap-1 text-xs text-amber-300 mt-0.5">
                    <CrownIcon className="w-3 h-3" />
                    {t('vipIncluded')}
                  </div>
                )}
                <div className="mt-1 flex items-center gap-1 text-[11px] text-gray-400">
                  <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                  </svg>
                  {Number(item.view_count || 0).toLocaleString()} {t('views')}
                </div>
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
                    ? 'bg-gradient-to-br from-[#BF953F] via-[#FCF6BA] to-[#B38728] text-white shadow-[0_0_10px_rgba(212,175,55,0.3)]'
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