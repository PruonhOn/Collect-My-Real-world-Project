import { useState } from 'react'
import { useLanguage } from '../../context/LanguageContext'
import { HIGHLIGHT_COLORS } from '../../utils/readerAnnotations'
import { BookmarkIcon, CheckIcon, TrashIcon } from './ReaderIcons'

function colorValue(id) {
  return HIGHLIGHT_COLORS.find((color) => color.id === id)?.value || HIGHLIGHT_COLORS[0].value
}

export default function NotesPanel({
  bookmarks = [],
  annotations = [],
  page,
  isBookmarked,
  canBookmark,
  onToggleBookmark,
  onDeleteBookmark,
  onDeleteAnnotation,
  onSelectSection,
  onSelectPage,
}) {
  const { t, locale } = useLanguage()
  const [tab, setTab] = useState('marks')

  const noteCount = annotations.length

  const dateLabel = (value) => {
    const date = new Date(value)
    if (Number.isNaN(date.getTime())) return ''
    return date.toLocaleDateString(locale, { month: 'short', day: 'numeric' })
  }

  return (
    <div className="rdr-notes">
      <div className="rdr-tabs" role="tablist" aria-label={t('reader.notes')}>
        <button
          type="button"
          role="tab"
          aria-selected={tab === 'marks'}
          className={`rdr-tab${tab === 'marks' ? ' is-active' : ''}`}
          onClick={() => setTab('marks')}
        >
          {t('reader.bookmarks')}
          <span className="rdr-tab-count">{bookmarks.length}</span>
        </button>
        <button
          type="button"
          role="tab"
          aria-selected={tab === 'notes'}
          className={`rdr-tab${tab === 'notes' ? ' is-active' : ''}`}
          onClick={() => setTab('notes')}
        >
          {t('reader.highlights')}
          <span className="rdr-tab-count">{noteCount}</span>
        </button>
      </div>

      {tab === 'marks' ? (
        <div className="rdr-tab-panel">
          <button
            type="button"
            className={`rdr-wide-btn${isBookmarked ? ' is-active' : ''}`}
            onClick={onToggleBookmark}
            disabled={!canBookmark}
          >
            {isBookmarked ? <CheckIcon size={18} /> : <BookmarkIcon size={18} />}
            {isBookmarked
              ? t('reader.bookmarkedPage')
              : `${t('reader.bookmarkPage')} ${page}`}
          </button>
          {!canBookmark ? <p className="rdr-panel-note">{t('reader.signInBookmarks')}</p> : null}

          {bookmarks.length ? (
            <ul className="rdr-note-list">
              {bookmarks.map((bookmark) => (
                <li key={bookmark.id} className="rdr-note-card">
                  <button
                    type="button"
                    className="rdr-note-main"
                    onClick={() =>
                      onSelectPage
                        ? onSelectPage(bookmark.page)
                        : onSelectSection(Math.max(0, bookmark.page - 1))
                    }
                  >
                    <span className="rdr-note-meta">
                      {t('reader.page')} {bookmark.page}
                    </span>
                    <span className="rdr-note-text">
                      {bookmark.note || t('reader.bookmarkNoNote')}
                    </span>
                  </button>
                  <button
                    type="button"
                    className="rdr-note-delete"
                    onClick={() => onDeleteBookmark(bookmark)}
                    aria-label={t('reader.removeBookmark')}
                    title={t('reader.removeBookmark')}
                  >
                    <TrashIcon size={17} />
                  </button>
                </li>
              ))}
            </ul>
          ) : (
            <p className="rdr-empty">{t('reader.noBookmarks')}</p>
          )}
        </div>
      ) : (
        <div className="rdr-tab-panel">
          {annotations.length ? (
            <ul className="rdr-note-list">
              {annotations.map((annotation) => (
                <li key={annotation.id} className="rdr-note-card rdr-annotation-card">
                  <button
                    type="button"
                    className="rdr-note-main"
                    onClick={() => onSelectSection(annotation.section || 0)}
                  >
                    <span className="rdr-note-meta">
                      <span
                        className="rdr-color-dot"
                        style={{ background: colorValue(annotation.color) }}
                        aria-hidden="true"
                      />
                      {t('reader.page')} {annotation.page}
                      {annotation.createdAt ? ` · ${dateLabel(annotation.createdAt)}` : ''}
                    </span>
                    <span className="rdr-annotation-quote">“{annotation.quote}”</span>
                    {annotation.note ? (
                      <span className="rdr-note-text">{annotation.note}</span>
                    ) : null}
                  </button>
                  <button
                    type="button"
                    className="rdr-note-delete"
                    onClick={() => onDeleteAnnotation(annotation.id)}
                    aria-label={t('reader.deleteHighlight')}
                    title={t('reader.deleteHighlight')}
                  >
                    <TrashIcon size={17} />
                  </button>
                </li>
              ))}
            </ul>
          ) : (
            <p className="rdr-empty">{t('reader.noHighlights')}</p>
          )}
          <p className="rdr-panel-note">{t('reader.annotationsLocal')}</p>
        </div>
      )}
    </div>
  )
}
