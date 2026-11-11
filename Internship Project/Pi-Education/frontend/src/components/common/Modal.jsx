import { useEffect } from 'react'
import { useI18n } from '../../i18n'

export default function Modal({ title, open, onClose, children, width = 'max-w-2xl' }) {
  const { t } = useI18n()

  useEffect(() => {
    if (!open) return
    const onKey = (e) => e.key === 'Escape' && onClose?.()
    window.addEventListener('keydown', onKey)
    document.body.style.overflow = 'hidden'
    return () => {
      window.removeEventListener('keydown', onKey)
      document.body.style.overflow = ''
    }
  }, [open, onClose])

  if (!open) return null

  return (
    <div
      className="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-slate-950/60 p-4 backdrop-blur-sm sm:py-12"
      onClick={(e) => e.target === e.currentTarget && onClose()}
    >
      <div
        className={`w-full ${width} animate-[modal-in_.2s_ease-out] rounded-2xl bg-white shadow-2xl`}
        role="dialog"
        aria-modal="true"
      >
        <div className="flex items-center justify-between border-b border-slate-100 px-6 py-4">
          <h2 className="text-lg font-bold text-slate-900">{title}</h2>
          <button
            type="button"
            onClick={onClose}
            aria-label="Close"
            className="rounded-xl p-1.5 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
          >
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round">
              <path d="M6 6l12 12M6 18L18 6" />
            </svg>
          </button>
        </div>
        <div className="max-h-[70vh] overflow-y-auto px-6 py-5">{children}</div>
        <div className="border-t border-slate-100 px-6 py-4 text-right">
          <button type="button" onClick={onClose} className="btn-secondary">
            {t('common.cancel')}
          </button>
        </div>
      </div>
    </div>
  )
}