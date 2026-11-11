import { useEffect, useMemo, useRef, useState } from 'react'
import { createPortal } from 'react-dom'
import { QRCodeSVG } from 'qrcode.react'
import { useLanguage } from '../../context/LanguageContext'
import RichText from './RichText'
import { CheckIcon, CloseIcon, CopyIcon, LinkIcon } from './ReaderIcons'

export default function ScanSheet({ open, onClose, bookId, page, section, title, onNotify }) {
  const { t } = useLanguage()
  const [largePrint, setLargePrint] = useState(false)
  const [copied, setCopied] = useState(false)
  const closeRef = useRef(null)
  const restoreRef = useRef(null)

  const url = useMemo(() => {
    if (typeof window === 'undefined') return ''
    const base = `${window.location.origin}/reader/${bookId}`
    return page > 1 ? `${base}?page=${page}` : base
  }, [bookId, page])

  useEffect(() => {
    if (!open) return undefined
    restoreRef.current = document.activeElement
    const timer = window.setTimeout(() => closeRef.current?.focus(), 40)
    const onKeyDown = (event) => {
      if (event.key === 'Escape') {
        event.stopPropagation()
        onClose()
      }
    }
    document.addEventListener('keydown', onKeyDown, true)
    const overflow = document.body.style.overflow
    document.body.style.overflow = 'hidden'
    return () => {
      window.clearTimeout(timer)
      document.removeEventListener('keydown', onKeyDown, true)
      document.body.style.overflow = overflow
      if (restoreRef.current instanceof HTMLElement) restoreRef.current.focus()
    }
  }, [onClose, open])

  const copy = async () => {
    try {
      await navigator.clipboard.writeText(url)
      setCopied(true)
      onNotify?.(t('reader.linkCopied'))
      window.setTimeout(() => setCopied(false), 1800)
    } catch {
      onNotify?.(t('reader.copyFailed'))
    }
  }

  if (!open) return null

  return createPortal(
    <div className="rdr-scan-layer">
      <button
        type="button"
        className="rdr-drawer-backdrop"
        onClick={onClose}
        aria-label={t('reader.closePanel')}
        tabIndex={-1}
      />
      <div className="rdr-scan" role="dialog" aria-modal="true" aria-label={t('reader.scan')}>
        <header className="rdr-drawer-head">
          <div>
            <h2 className="rdr-drawer-title">{t('reader.scan')}</h2>
            <p className="rdr-drawer-sub">{title}</p>
          </div>
          <button
            type="button"
            className="rdr-icon-btn"
            onClick={onClose}
            aria-label={t('reader.closePanel')}
            ref={closeRef}
          >
            <CloseIcon />
          </button>
        </header>

        <div className={`rdr-scan-body${largePrint ? ' is-large' : ''}`}>
          {largePrint ? (
            <div className="rdr-scan-preview" aria-label={t('reader.largePrint')}>
              {section ? (
                <RichText section={section} annotations={[]} />
              ) : (
                <p className="rdr-empty">{t('reader.noReadableSection')}</p>
              )}
            </div>
          ) : (
            <>
              <div className="rdr-qr-card">
                {url ? <QRCodeSVG value={url} size={196} marginSize={2} bgColor="#ffffff" fgColor="#111111" /> : null}
              </div>
              <p className="rdr-scan-caption">{t('reader.scanCaption')}</p>
              <div className="rdr-scan-url">
                <LinkIcon size={17} />
                <span>{url}</span>
              </div>
              <div className="rdr-scan-actions">
                <button type="button" className="rdr-wide-btn" onClick={copy}>
                  {copied ? <CheckIcon size={17} /> : <CopyIcon size={17} />}
                  {copied ? t('reader.linkCopied') : t('reader.copyLink')}
                </button>
                <button
                  type="button"
                  className="rdr-wide-btn"
                  onClick={() => setLargePrint(true)}
                >
                  {t('reader.largePrint')}
                </button>
              </div>
            </>
          )}

          {largePrint ? (
            <button
              type="button"
              className="rdr-wide-btn rdr-scan-back"
              onClick={() => setLargePrint(false)}
            >
              {t('reader.backToQr')}
            </button>
          ) : null}
        </div>
      </div>
    </div>,
    document.body,
  )
}
