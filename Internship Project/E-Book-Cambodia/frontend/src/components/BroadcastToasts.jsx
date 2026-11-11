import { useLanguage } from '../context/LanguageContext'
import { useBroadcastMessages } from '../services/broadcasts'

const MAX_VISIBLE = 3

const TONES = {
  info: { bar: 'bg-blue-600', icon: 'bg-blue-50 text-blue-600' },
  success: { bar: 'bg-emerald-600', icon: 'bg-emerald-50 text-emerald-600' },
  warning: { bar: 'bg-amber-500', icon: 'bg-amber-50 text-amber-600' },
  danger: { bar: 'bg-rose-600', icon: 'bg-rose-50 text-rose-600' },
}

const TOAST_ANIMATIONS = `
  .toast-enter { animation: toastIn .2s ease-out; }
  @keyframes toastIn { from { opacity: 0; transform: translateX(12px); } to { opacity: 1; transform: translateX(0); } }
  @media (prefers-reduced-motion: reduce) { .toast-enter { animation: none; } }
`

/**
 * The popup style, docked in the top-right corner.
 *
 * Mounted once for the whole app rather than per layout, so members browsing
 * the site, authors in their dashboard and admins in the admin panel all get
 * the same announcement in the same place. Fixed positioning means it never
 * affects page layout, so it is safe to sit above every screen.
 */
export default function BroadcastToasts() {
  const { t } = useLanguage()
  const { popups, dismiss } = useBroadcastMessages()

  if (popups.length === 0) {
    return null
  }

  return (
    <>
      <style>{TOAST_ANIMATIONS}</style>
      <div
        className="fixed top-4 right-4 z-[70] flex flex-col gap-3 w-[min(22rem,calc(100vw-2rem))] pointer-events-none"
        aria-live="polite"
      >
        {popups.slice(0, MAX_VISIBLE).map((message) => {
          const tone = TONES[message.tone] || TONES.info
          return (
            <div
              key={message.id}
              role="status"
              className="pointer-events-auto relative overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-gray-900/5 toast-enter"
            >
              <span className={`absolute inset-x-0 top-0 h-1 ${tone.bar}`} />
              <div className="flex items-start gap-3 p-4 pt-5">
                <span
                  className={`flex items-center justify-center w-9 h-9 rounded-xl shrink-0 text-base ${tone.icon}`}
                >
                  📢
                </span>
                <div className="min-w-0 flex-1">
                  {message.title && <p className="text-sm font-semibold text-gray-900">{message.title}</p>}
                  <p className="text-sm text-gray-600 whitespace-pre-line">{message.body}</p>
                  {message.link_url && (
                    <a
                      href={message.link_url}
                      target="_blank"
                      rel="noopener noreferrer"
                      className="inline-block mt-1.5 text-sm font-semibold text-indigo-600 hover:text-indigo-700"
                    >
                      {message.link_label || t('learnMore')}
                    </a>
                  )}
                </div>
                <button
                  type="button"
                  onClick={() => dismiss(message.id)}
                  aria-label={t('close')}
                  className="shrink-0 p-1 -mt-0.5 rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors"
                >
                  <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>
            </div>
          )
        })}
      </div>
    </>
  )
}