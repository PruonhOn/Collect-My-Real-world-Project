import { useEffect, useRef, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { useLanguage } from '../context/LanguageContext'
import { useNotifications } from '../services/notifications'
import timeAgo from '../utils/timeAgo'

const VISIBLE_COUNT = 6

const ACCENTS = {
  indigo: {
    button: 'text-gray-500 hover:text-indigo-600',
    dot: 'bg-indigo-500',
    hover: 'hover:bg-indigo-50/70',
    unread: 'bg-indigo-50/70',
    action: 'text-indigo-600 hover:text-indigo-700',
    footer: 'border-gray-100',
  },
  emerald: {
    button: 'text-gray-500 hover:text-emerald-600',
    dot: 'bg-emerald-500',
    hover: 'hover:bg-emerald-50/70',
    unread: 'bg-emerald-50/70',
    action: 'text-emerald-600 hover:text-emerald-700',
    footer: 'border-gray-100',
  },
}

// The review verdicts the author can be told about, mapped onto the status
// labels the rest of the author portal already shows.
const BOOK_ACTION_LABEL = {
  approved: 'status_approved',
  rejected: 'status_rejected',
  published: 'published',
  unpublished: 'unpublished',
}

const BOOK_ACTION_TONE = {
  approved: { icon: '✅', bg: 'bg-emerald-50' },
  rejected: { icon: '⚠️', bg: 'bg-amber-50' },
  published: { icon: '🚀', bg: 'bg-sky-50' },
  unpublished: { icon: '📥', bg: 'bg-gray-100' },
}

// A message reaches an admin through a member chat, the guest contact form or
// the partnership enquiry form, and each lands on a different tab.
const ADMIN_SOURCE = {
  chat: { title: 'newMessageNotification', hint: 'openConversation', icon: '💬', bg: 'bg-indigo-50' },
  contact: { title: 'newContactMessage', hint: 'viewRequest', icon: '📨', bg: 'bg-amber-50' },
  collaboration: { title: 'newPartnershipEnquiry', hint: 'viewRequest', icon: '🤝', bg: 'bg-emerald-50' },
}

const adminTarget = (n) => {
  if (n.type === 'book_uploaded') return '/admin/book-reviews'
  if (n.type === 'new_message' && n.data?.source === 'chat') {
    // Without a conversation id this would build "?conversation=null".
    return n.data?.conversation_id ? `/admin/messages?conversation=${n.data.conversation_id}` : '/admin/messages'
  }
  if (n.type === 'new_message') return '/admin/messages?tab=guests'
  return null
}

const memberTarget = (n) => {
  if (n.type !== 'new_message') return null
  return n.data?.conversation_id ? `/messages?conversation=${n.data.conversation_id}` : '/messages'
}

const authorTarget = (n) => (n.type === 'book_status_changed' ? '/author/books' : null)

/**
 * Turns one row of the feed into the copy and the destination for this portal.
 * Keeping it next to the component means the bell, and any future page, cannot
 * disagree about where a notification leads.
 */
function describe(n, t, scope) {
  if (scope === 'author') {
    const action = n.data?.action
    const tone = BOOK_ACTION_TONE[action] || { icon: '📚', bg: 'bg-gray-100' }
    return {
      icon: tone.icon,
      bg: tone.bg,
      title: `${t('bookStatusChanged')} · ${t(BOOK_ACTION_LABEL[action] || 'status_approved')}`,
      body: n.data?.review_note || n.data?.book_title || '',
      hint: t('checkBooks'),
      to: authorTarget(n),
    }
  }

  if (n.type === 'new_message') {
    const source = scope === 'admin' ? ADMIN_SOURCE[n.data?.source] || ADMIN_SOURCE.chat : null
    return {
      icon: source?.icon || '💬',
      bg: source?.bg || 'bg-indigo-50',
      title: t(source?.title || 'newMessageNotification'),
      body: `${n.data?.sender_name || t('supportTeam')} ${t('sentYouMessage')} ${n.data?.message_excerpt || ''}`.trim(),
      hint: t(source?.hint || 'openConversation'),
      to: scope === 'admin' ? adminTarget(n) : memberTarget(n),
    }
  }

  if (n.type === 'book_uploaded') {
    return {
      icon: '📚',
      bg: 'bg-sky-50',
      title: t('bookSubmittedForReview'),
      body: `${t('submittedByAuthor')} ${n.data?.author_name || ''} · ${n.data?.book_title || ''}`.trim(),
      hint: t('checkBooks'),
      to: adminTarget(n),
    }
  }

  return {
    icon: '📢',
    bg: 'bg-gray-100',
    title: n.title || t('notifications'),
    body: n.message || '',
    hint: null,
    to: null,
  }
}

/**
 * The top-right bell. One component serves all three portals: `scope` picks the
 * feed and the links, `accent` matches the nav it sits in.
 */
export default function NotificationBell({ scope, enabled = true, accent = 'indigo', viewAllTo = null }) {
  const { t } = useLanguage()
  const navigate = useNavigate()
  const [open, setOpen] = useState(false)
  const wrapperRef = useRef(null)
  const { data, unreadCount, markOneRead, markAllRead } = useNotifications(scope, enabled)
  const tone = ACCENTS[accent] || ACCENTS.indigo

  useEffect(() => {
    if (!open) return undefined
    const onPointerDown = (event) => {
      if (!wrapperRef.current?.contains(event.target)) setOpen(false)
    }
    const onKeyDown = (event) => {
      if (event.key === 'Escape') setOpen(false)
    }
    document.addEventListener('mousedown', onPointerDown)
    document.addEventListener('keydown', onKeyDown)
    return () => {
      document.removeEventListener('mousedown', onPointerDown)
      document.removeEventListener('keydown', onKeyDown)
    }
  }, [open])

  const visible = data.slice(0, VISIBLE_COUNT)

  const handleClick = (n) => {
    markOneRead(n.id)
    setOpen(false)
    const target = describe(n, t, scope).to
    if (target) navigate(target)
  }

  const badge =
    unreadCount > 0 && (
      <span className="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center leading-none">
        {unreadCount > 9 ? '9+' : unreadCount}
      </span>
    )

  return (
    <div className="relative" ref={wrapperRef}>
      <button
        type="button"
        onClick={() => setOpen((v) => !v)}
        aria-label={t('notifications')}
        aria-expanded={open}
        className={`relative p-2 rounded-lg transition-colors ${tone.button}`}
      >
        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth="1.8"
            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0a3 3 0 11-6 0m6 0H9"
          />
        </svg>
        {badge}
      </button>

      {open && (
        <>
          <div className="fixed inset-0 z-40" onClick={() => setOpen(false)} aria-hidden="true" />
          <div
            className="absolute right-0 mt-2 w-[min(22rem,calc(100vw-2rem))] bg-white/95 backdrop-blur-xl rounded-2xl shadow-xl border border-gray-100 z-50 origin-top-right dropdown-enter overflow-hidden"
            role="menu"
          >
            <div className="flex items-center justify-between gap-2 px-4 py-3 border-b border-gray-100">
              <span className="text-sm font-semibold text-gray-900">{t('notifications')}</span>
              {unreadCount > 0 && (
                <button
                  type="button"
                  onClick={markAllRead}
                  className={`text-xs font-medium ${tone.action} hover:underline`}
                >
                  {t('markAllRead')}
                </button>
              )}
            </div>

            <div className="max-h-96 overflow-y-auto">
              {visible.length === 0 ? (
                <p className="px-4 py-8 text-center text-sm text-gray-400">{t('noNotifications')}</p>
              ) : (
                <ul>
                  {visible.map((n) => {
                    const meta = describe(n, t, scope)
                    const actionable = !!meta.to
                    return (
                      <li key={n.id}>
                        <button
                          type="button"
                          onClick={() => handleClick(n)}
                          disabled={!actionable}
                          className={`w-full text-left flex items-start gap-3 px-4 py-3 transition-colors disabled:cursor-default ${tone.hover} ${
                            n.read ? '' : tone.unread
                          }`}
                        >
                          <span className={`flex items-center justify-center w-8 h-8 rounded-lg shrink-0 ${meta.bg}`}>
                            {meta.icon}
                          </span>
                          <span className="min-w-0 flex-1">
                            <span className="flex items-center gap-2">
                              <span
                                className={`text-sm truncate ${n.read ? 'font-medium text-gray-600' : 'font-semibold text-gray-900'}`}
                              >
                                {meta.title}
                              </span>
                              {!n.read && <span className={`w-2 h-2 rounded-full shrink-0 ${tone.dot}`} />}
                            </span>
                            {meta.body && <span className="block text-xs text-gray-500 mt-0.5 line-clamp-2">{meta.body}</span>}
                            <span className="block text-[11px] text-gray-400 mt-1">{timeAgo(n.created_at)}</span>
                          </span>
                        </button>
                      </li>
                    )
                  })}
                </ul>
              )}
            </div>

            {viewAllTo && (
              <div className={`px-4 py-2.5 border-t ${tone.footer}`}>
                <button
                  type="button"
                  onClick={() => {
                    setOpen(false)
                    navigate(viewAllTo)
                  }}
                  className={`w-full text-center text-sm font-medium ${tone.action} hover:underline`}
                >
                  {t('notifications')}
                </button>
              </div>
            )}
          </div>
        </>
      )}
    </div>
  )
}
