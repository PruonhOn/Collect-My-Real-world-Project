import { useLanguage } from '../context/LanguageContext'
import { useBroadcastMessages } from '../services/broadcasts'

const TONES = {
  info: 'bg-blue-600 text-white',
  success: 'bg-emerald-600 text-white',
  warning: 'bg-amber-500 text-white',
  danger: 'bg-rose-600 text-white',
}

/**
 * The banner style: a full-width strip that sits above the page content. It is
 * rendered inside each layout's shell so it pushes the page down rather than
 * covering it, and so members, authors and admins all see it.
 */
export default function BroadcastBanner() {
  const { t } = useLanguage()
  const { banners, dismiss } = useBroadcastMessages()

  if (banners.length === 0) {
    return null
  }

  return banners.map((message) => (
    <div
      key={message.id}
      role="status"
      className={`flex items-start gap-3 px-4 sm:px-6 py-3 ${TONES[message.tone] || TONES.info}`}
    >
      <span className="text-lg leading-none mt-0.5">📢</span>
      <div className="min-w-0 flex-1">
        {message.title && <p className="text-sm font-semibold">{message.title}</p>}
        <p className="text-sm opacity-95 whitespace-pre-line">{message.body}</p>
        {message.link_url && (
          <a
            href={message.link_url}
            target="_blank"
            rel="noopener noreferrer"
            className="inline-block mt-1 text-sm font-semibold underline underline-offset-2"
          >
            {message.link_label || t('learnMore')}
          </a>
        )}
      </div>
      <button
        type="button"
        onClick={() => dismiss(message.id)}
        aria-label={t('close')}
        className="shrink-0 p-1 -mt-0.5 rounded hover:bg-white/20 transition-colors"
      >
        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>
  ))
}