import { useEffect, useId, useRef } from 'react'
import { createPortal } from 'react-dom'
import { useLanguage } from '../../context/LanguageContext'
import { CloseIcon } from './ReaderIcons'

const FOCUSABLE =
  'a[href], button:not([disabled]), input:not([disabled]), textarea:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])'

/**
 * Accessible right-hand drawer on desktop and bottom sheet on phones. Used by
 * the contents, notes, search, settings and help panels so they all share the
 * same focus handling and motion.
 */
export default function ReaderDrawer({ open, title, subtitle, onClose, children, footer }) {
  const { t } = useLanguage()
  const panelRef = useRef(null)
  const closeRef = useRef(null)
  const restoreRef = useRef(null)
  const titleId = useId()

  useEffect(() => {
    if (!open) return undefined

    restoreRef.current = document.activeElement
    const timer = window.setTimeout(() => {
      const preferred = panelRef.current?.querySelector('[data-autofocus]')
      ;(preferred instanceof HTMLElement ? preferred : closeRef.current)?.focus()
    }, 40)

    const onKeyDown = (event) => {
      if (event.key === 'Escape') {
        event.stopPropagation()
        onClose()
        return
      }
      if (event.key !== 'Tab') return

      const panel = panelRef.current
      if (!panel) return
      const nodes = Array.from(panel.querySelectorAll(FOCUSABLE)).filter(
        (node) => node.offsetParent !== null,
      )
      if (!nodes.length) return
      const first = nodes[0]
      const last = nodes[nodes.length - 1]

      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault()
        last.focus()
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault()
        first.focus()
      }
    }

    document.addEventListener('keydown', onKeyDown, true)
    const previousOverflow = document.body.style.overflow
    document.body.style.overflow = 'hidden'

    return () => {
      window.clearTimeout(timer)
      document.removeEventListener('keydown', onKeyDown, true)
      document.body.style.overflow = previousOverflow
      if (restoreRef.current instanceof HTMLElement) restoreRef.current.focus()
    }
  }, [onClose, open])

  if (!open) return null

  return createPortal(
    <div className="rdr-drawer-layer">
      <button
        type="button"
        className="rdr-drawer-backdrop"
        onClick={onClose}
        aria-label={t('reader.closePanel')}
        tabIndex={-1}
      />
      <aside
        className="rdr-drawer"
        role="dialog"
        aria-modal="true"
        aria-labelledby={titleId}
        ref={panelRef}
      >
        <header className="rdr-drawer-head">
          <div>
            <h2 className="rdr-drawer-title" id={titleId}>
              {title}
            </h2>
            {subtitle ? <p className="rdr-drawer-sub">{subtitle}</p> : null}
          </div>
          <button
            type="button"
            className="rdr-icon-btn rdr-drawer-close"
            onClick={onClose}
            aria-label={t('reader.closePanel')}
            ref={closeRef}
          >
            <CloseIcon />
          </button>
        </header>

        <div className="rdr-drawer-body">{children}</div>
        {footer ? <footer className="rdr-drawer-foot">{footer}</footer> : null}
      </aside>
    </div>,
    document.body,
  )
}
