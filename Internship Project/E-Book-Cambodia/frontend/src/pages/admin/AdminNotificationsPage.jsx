import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useLanguage } from '../../context/LanguageContext'
import { useAdminNotifications } from '../../services/notifications'
import timeAgo from '../../utils/timeAgo'

const TYPE_META = {
  new_message: { icon: '💬', bg: 'bg-indigo-50' },
  user_registered: { icon: '👤', bg: 'bg-indigo-50' },
  payment: { icon: '💰', bg: 'bg-emerald-50' },
  book_uploaded: { icon: '📚', bg: 'bg-sky-50' },
  payment_issue: { icon: '⚠️', bg: 'bg-amber-50' },
  user_report: { icon: '🚩', bg: 'bg-rose-50' },
  system: { icon: '📢', bg: 'bg-gray-100' },
}

// A message can arrive through a member chat, the guest contact form, or the
// partnership enquiry form. Each lands somewhere different in the admin UI.
const MESSAGE_SOURCE_META = {
  chat: { title: 'newMessageNotification', hint: 'openConversation', to: (n) => `/admin/messages?conversation=${n.data?.conversation_id}`, icon: '💬', bg: 'bg-indigo-50' },
  contact: { title: 'newContactMessage', hint: 'viewRequest', to: () => '/admin/messages?tab=guests', icon: '📨', bg: 'bg-amber-50' },
  collaboration: { title: 'newPartnershipEnquiry', hint: 'viewRequest', to: () => '/admin/messages?tab=guests', icon: '🤝', bg: 'bg-emerald-50' },
}

export default function AdminNotificationsPage() {
  const { t } = useLanguage()
  const navigate = useNavigate()
  const { data, unreadCount, markOneRead, markAllRead } = useAdminNotifications()
  const [filter, setFilter] = useState('all')

  const handleClick = (n) => {
    markOneRead(n.id)
    if (n.type === 'book_uploaded') {
      navigate('/admin/book-reviews')
      return
    }
    if (n.type === 'new_message') {
      const source = MESSAGE_SOURCE_META[n.data?.source] || MESSAGE_SOURCE_META.chat
      // A chat notification without a conversation id would build "?conversation=null".
      if (n.data?.source === 'chat' && !n.data?.conversation_id) return
      navigate(source.to(n))
    }
  }

  const visible = (filter === 'unread' ? data.filter((n) => !n.read) : data)
  const tabs = [
    { key: 'all', label: t('notifications') },
    { key: 'unread', label: `${t('notifications')} · ${unreadCount}` },
  ]

  return (
    <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">{t('notifications')}</h1>
          <p className="text-sm text-gray-500 mt-1">
            {unreadCount > 0
              ? `${unreadCount} unread notification${unreadCount > 1 ? 's' : ''}`
              : 'You are all caught up'}
          </p>
        </div>
        <button
          type="button"
          onClick={markAllRead}
          disabled={unreadCount === 0}
          className="inline-flex items-center justify-center gap-2 h-10 px-4 text-sm font-medium bg-indigo-600 text-white rounded-xl shadow-sm shadow-indigo-600/20 transition-all duration-200 hover:bg-indigo-700 active:scale-95 disabled:opacity-40 disabled:pointer-events-none"
        >
          <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M3 19v-8.93a2 2 0 011.11-1.79l8-4a2 2 0 011.78 0l8 4A2 2 0 0121 10.07V19a2 2 0 01-2 2H5a2 2 0 01-2-2zM15 21v-8a1 1 0 00-1-1h-4a1 1 0 00-1 1v8" />
          </svg>
          {t('markAllRead')}
        </button>
      </div>

      {/* Filter tabs */}
      <div className="inline-flex items-center gap-1 p-1 bg-white rounded-xl shadow-sm mb-6">
        {tabs.map((tab) => (
          <button
            key={tab.key}
            type="button"
            onClick={() => setFilter(tab.key)}
            className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors ${
              filter === tab.key
                ? 'bg-indigo-600 text-white shadow-sm'
                : 'text-gray-600 hover:bg-gray-100 hover:text-indigo-700'
            }`}
          >
            {tab.label}
          </button>
        ))}
      </div>

      {/* Notification list */}
      {visible.length === 0 ? (
        <div className="bg-white rounded-2xl shadow-sm py-16 text-center">
          <div className="text-4xl mb-3">🔔</div>
          <p className="text-gray-500 text-sm">{t('noNotifications')}</p>
        </div>
      ) : (
        <ul className="space-y-3">
          {visible.map((n) => {
            const isBookUpload = n.type === 'book_uploaded'
            const isMessage = n.type === 'new_message'
            const source = isMessage ? (MESSAGE_SOURCE_META[n.data?.source] || MESSAGE_SOURCE_META.chat) : null
            const meta = source || TYPE_META[n.type] || TYPE_META.system
            const title = isBookUpload
              ? t('bookSubmittedForReview')
              : isMessage
                ? t(source.title)
                : n.title || t('notifications')
            const message = isBookUpload
              ? `${t('submittedByAuthor')} ${n.data?.author_name || ''} · ${n.data?.book_title || ''}`
              : isMessage
                ? `${n.data?.sender_name || ''} ${t('sentYouMessage')} ${n.data?.message_excerpt || ''}`
                : n.message
            return (
              <li key={n.id}>
                <button
                  type="button"
                  onClick={() => handleClick(n)}
                  className={`w-full text-left flex items-start gap-3 p-4 rounded-2xl shadow-sm border transition-all duration-200 hover:shadow-md active:scale-[0.99] ${
                    n.read ? 'bg-white border-gray-100' : 'bg-white border-indigo-100'
                  }`}
                >
                  <span className={`flex items-center justify-center w-11 h-11 rounded-xl shrink-0 ${meta.bg}`}>
                    {meta.icon}
                  </span>
                  <span className="flex-1 min-w-0">
                    <span className="flex items-center justify-between gap-2">
                      <span className={`text-sm font-semibold truncate ${n.read ? 'text-gray-700' : 'text-gray-900'}`}>
                        {title}
                      </span>
                      {!n.read && <span className="w-2 h-2 rounded-full bg-indigo-500 shrink-0" />}
                    </span>
                    <span className="block text-sm text-gray-500 mt-0.5">{message}</span>
                    {isMessage && (
                      <span className="block text-xs text-indigo-500 font-medium mt-1">{t(source.hint)}</span>
                    )}
                    {isBookUpload && (
                      <span className="block text-xs text-indigo-500 font-medium mt-1">{t('checkBooks')}</span>
                    )}
                    <span className="block text-xs text-gray-400 mt-1.5">{timeAgo(n.created_at)}</span>
                  </span>
                </button>
              </li>
            )
          })}
        </ul>
      )}
    </div>
  )
}
