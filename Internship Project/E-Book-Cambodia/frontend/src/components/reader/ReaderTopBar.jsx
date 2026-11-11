import { useLanguage } from '../../context/LanguageContext'
import {
  CloseIcon,
  CompressIcon,
  ExpandIcon,
  NoteIcon,
  ScanIcon,
  SearchIcon,
  SettingsIcon,
  TocIcon,
} from './ReaderIcons'

function TopButton({ label, onClick, children, pressed }) {
  return (
    <button
      type="button"
      className="rdr-icon-btn"
      onClick={onClick}
      aria-label={label}
      title={label}
      aria-pressed={pressed}
    >
      {children}
    </button>
  )
}

/**
 * 56px reading header. It floats above the page (with reserved space below), so
 * hiding it never changes the text width and never forces a re-pagination.
 */
export default function ReaderTopBar({
  title,
  author,
  chapter,
  progress = 0,
  visible = true,
  isFullscreen = false,
  viewerCount,
  viewCountLabel,
  readerName,
  badge,
  onExit,
  onOpenToc,
  onOpenSearch,
  onOpenNotes,
  onOpenScan,
  onOpenSettings,
  onToggleFullscreen,
}) {
  const { t } = useLanguage()
  const initials = String(readerName || '')
    .trim()
    .split(/\s+/)
    .slice(0, 2)
    .map((part) => part[0])
    .join('')
    .toUpperCase()

  return (
    <header className={`rdr-top${visible ? '' : ' is-hidden'}`}>
      <div className="rdr-top-left">
        <TopButton label={t('reader.exit')} onClick={onExit}>
          <CloseIcon />
        </TopButton>

        <div className="rdr-heading">
          <div className="rdr-heading-line">
            <span className="rdr-book-title" title={title}>
              {title}
            </span>
            {badge}
          </div>
          <div className="rdr-heading-sub">
            {chapter ? <span className="rdr-chapter-label">{chapter}</span> : null}
            {author ? <span className="rdr-author">{author}</span> : null}
          </div>
        </div>
      </div>

      <div className="rdr-top-right">
        {readerName ? (
          <div className="rdr-reader-chip" title={readerName}>
            <span className="rdr-avatar">{initials || '·'}</span>
            <span className="rdr-reader-name">{readerName}</span>
          </div>
        ) : null}

        {Number.isFinite(Number(viewerCount)) ? (
          <span className="rdr-views" title={viewCountLabel}>
            {Number(viewerCount).toLocaleString()}
            <span className="rdr-views-label">{viewCountLabel}</span>
          </span>
        ) : null}

        <div className="rdr-top-actions">
          <TopButton label={t('reader.toc')} onClick={onOpenToc}>
            <TocIcon />
          </TopButton>
          <TopButton label={t('reader.search')} onClick={onOpenSearch}>
            <SearchIcon />
          </TopButton>
          <TopButton label={t('reader.notes')} onClick={onOpenNotes}>
            <NoteIcon />
          </TopButton>
          <TopButton label={t('reader.scan')} onClick={onOpenScan}>
            <ScanIcon />
          </TopButton>
          <TopButton label={t('reader.settings')} onClick={onOpenSettings}>
            <SettingsIcon />
          </TopButton>
          <TopButton
            label={isFullscreen ? t('reader.exitFullscreen') : t('reader.fullscreen')}
            onClick={onToggleFullscreen}
            pressed={isFullscreen}
          >
            {isFullscreen ? <CompressIcon /> : <ExpandIcon />}
          </TopButton>
        </div>
      </div>

      <div className="rdr-top-progress" aria-hidden="true">
        <span style={{ width: `${Math.min(100, Math.max(0, progress))}%` }} />
      </div>
    </header>
  )
}
