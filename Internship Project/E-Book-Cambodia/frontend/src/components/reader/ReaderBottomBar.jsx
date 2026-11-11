import { useEffect, useRef, useState } from 'react'
import { useLanguage } from '../../context/LanguageContext'
import {
  BookmarkIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
  HelpIcon,
  SettingsIcon,
  SparkIcon,
  ZoomInIcon,
  ZoomOutIcon,
} from './ReaderIcons'

function BarButton({ label, onClick, children, disabled, pressed, className = '' }) {
  return (
    <button
      type="button"
      className={`rdr-bar-btn ${className}`.trim()}
      onClick={onClick}
      aria-label={label}
      title={label}
      disabled={disabled}
      aria-pressed={pressed}
    >
      {children}
    </button>
  )
}

/**
 * Floating navigation bar. The page counter opens a small jump field, which is
 * much easier to hit on a phone than a tiny inline input.
 */
export default function ReaderBottomBar({
  page,
  pageCount,
  pageLabel,
  progress = 0,
  canPrev,
  canNext,
  onPrev,
  onNext,
  onGoTo,
  zoom = 1,
  onZoomIn,
  onZoomOut,
  onResetZoom,
  isBookmarked,
  onToggleBookmark,
  aiOpen,
  onToggleAi,
  onOpenHelp,
  onOpenSettings,
}) {
  const { t } = useLanguage()
  const [jumpOpen, setJumpOpen] = useState(false)
  const [value, setValue] = useState(String(page))
  const inputRef = useRef(null)
  const wrapRef = useRef(null)

  useEffect(() => {
    if (!jumpOpen) return undefined
    const input = inputRef.current
    input?.focus()
    input?.select()

    const onClickAway = (event) => {
      if (!wrapRef.current?.contains(event.target)) setJumpOpen(false)
    }
    document.addEventListener('pointerdown', onClickAway)
    return () => document.removeEventListener('pointerdown', onClickAway)
  }, [jumpOpen])

  const submit = (event) => {
    event.preventDefault()
    const next = Number(value)
    if (Number.isFinite(next)) onGoTo(next)
    setJumpOpen(false)
  }

  const zoomPercent = Math.round(zoom * 100)

  return (
    <nav className="rdr-bottom" aria-label={t('reader.navigation')}>
      <div className="rdr-bottom-group">
        <BarButton label={t('reader.previous')} onClick={onPrev} disabled={!canPrev}>
          <ChevronLeftIcon />
        </BarButton>

        <div className="rdr-jump-wrap" ref={wrapRef}>
          <button
            type="button"
            className="rdr-page-pill"
            onClick={() => {
              setValue(String(page))
              setJumpOpen((open) => !open)
            }}
            aria-expanded={jumpOpen}
            aria-label={t('reader.jumpToPage')}
            title={t('reader.jumpToPage')}
          >
            <span className="rdr-page-current">{pageLabel}</span>
            <span className="rdr-page-total">/ {pageCount}</span>
          </button>

          {jumpOpen ? (
            <form className="rdr-jump" onSubmit={submit}>
              <label className="rdr-jump-label" htmlFor="rdr-jump-input">
                {t('reader.jumpToPage')}
              </label>
              <div className="rdr-jump-row">
                <input
                  id="rdr-jump-input"
                  ref={inputRef}
                  className="rdr-jump-input"
                  type="number"
                  min="1"
                  max={pageCount}
                  value={value}
                  onChange={(event) => setValue(event.target.value)}
                  onKeyDown={(event) => {
                    if (event.key === 'Escape') setJumpOpen(false)
                  }}
                />
                <button type="submit" className="rdr-jump-go">
                  {t('reader.go')}
                </button>
              </div>
            </form>
          ) : null}
        </div>

        <BarButton label={t('reader.next')} onClick={onNext} disabled={!canNext}>
          <ChevronRightIcon />
        </BarButton>
      </div>

      <div className="rdr-rail" title={`${Math.round(progress)}%`}>
        <input
          className="rdr-rail-input"
          type="range"
          min="1"
          max={Math.max(1, pageCount)}
          step="1"
          value={page}
          onChange={(event) => onGoTo(Number(event.target.value))}
          aria-label={t('reader.progress')}
          style={{ '--rdr-rail-fill': `${Math.min(100, Math.max(0, progress))}%` }}
        />
      </div>

      <div className="rdr-bottom-group rdr-zoom-group">
        <BarButton label={t('reader.zoomOut')} onClick={onZoomOut} disabled={zoom <= 0.5}>
          <ZoomOutIcon />
        </BarButton>
        <button
          type="button"
          className="rdr-zoom-pill"
          onClick={onResetZoom}
          title={t('reader.resetZoom')}
        >
          {zoomPercent}%
        </button>
        <BarButton label={t('reader.zoomIn')} onClick={onZoomIn} disabled={zoom >= 3}>
          <ZoomInIcon />
        </BarButton>
      </div>

      <div className="rdr-bottom-group">
        <BarButton
          label={isBookmarked ? t('reader.removeBookmark') : t('reader.addBookmark')}
          onClick={onToggleBookmark}
          pressed={isBookmarked}
          className={isBookmarked ? 'is-active' : ''}
        >
          <BookmarkIcon />
        </BarButton>
        <BarButton
          label={aiOpen ? t('ai.close') : t('ai.open')}
          onClick={onToggleAi}
          pressed={aiOpen}
          className={aiOpen ? 'is-active' : ''}
        >
          <SparkIcon />
        </BarButton>
        <BarButton label={t('reader.help')} onClick={onOpenHelp}>
          <HelpIcon />
        </BarButton>
        <BarButton
          label={t('reader.settings')}
          onClick={onOpenSettings}
          className="rdr-settings-mobile"
        >
          <SettingsIcon />
        </BarButton>
      </div>
    </nav>
  )
}
