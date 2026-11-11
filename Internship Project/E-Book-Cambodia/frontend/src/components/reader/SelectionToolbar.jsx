import { useEffect, useRef, useState } from 'react'
import { createPortal } from 'react-dom'
import { useLanguage } from '../../context/LanguageContext'
import { HIGHLIGHT_COLORS } from '../../utils/readerAnnotations'
import { CopyIcon, NoteIcon, SparkIcon } from './ReaderIcons'

export default function SelectionToolbar({
  selection,
  onClose,
  onHighlight,
  onCopy,
  onAsk,
  onNote,
}) {
  const { t } = useLanguage()
  const [noteOpen, setNoteOpen] = useState(false)
  const [note, setNote] = useState('')
  const noteRef = useRef(null)
  const barRef = useRef(null)

  useEffect(() => {
    if (noteOpen) noteRef.current?.focus()
  }, [noteOpen])

  useEffect(() => {
    const onKeyDown = (event) => {
      if (event.key === 'Escape') onClose()
    }
    const onPointerDown = (event) => {
      if (!barRef.current?.contains(event.target)) onClose()
    }
    document.addEventListener('keydown', onKeyDown)
    const timer = window.setTimeout(
      () => document.addEventListener('pointerdown', onPointerDown),
      60,
    )
    return () => {
      window.clearTimeout(timer)
      document.removeEventListener('keydown', onKeyDown)
      document.removeEventListener('pointerdown', onPointerDown)
    }
  }, [onClose])

  if (!selection) return null

  const { rect } = selection
  const width = typeof window !== 'undefined' ? window.innerWidth : 1024
  const height = typeof window !== 'undefined' ? window.innerHeight : 768
  const center = Math.min(Math.max(rect.left + rect.width / 2, 130), width - 130)
  const below = rect.top < 130
  const top = below ? Math.min(height - 70, rect.bottom + 10) : Math.max(10, rect.top - 10)

  const saveNote = (event) => {
    event.preventDefault()
    onNote(note.trim())
  }

  return createPortal(
    <div
      ref={barRef}
      className={`rdr-select-bar${below ? ' is-below' : ''}`}
      style={{ left: center, top }}
      role="toolbar"
      aria-label={t('reader.selectionTools')}
    >
      {noteOpen ? (
        <form className="rdr-note-form" onSubmit={saveNote}>
          <label className="rdr-sr-only" htmlFor="rdr-note-input">
            {t('reader.addNote')}
          </label>
          <textarea
            id="rdr-note-input"
            ref={noteRef}
            className="rdr-note-input"
            rows={3}
            maxLength={4000}
            value={note}
            onChange={(event) => setNote(event.target.value)}
            placeholder={t('reader.notePlaceholder')}
          />
          <div className="rdr-note-actions">
            <button type="button" className="rdr-quiet-btn" onClick={() => setNoteOpen(false)}>
              {t('reader.cancel')}
            </button>
            <button type="submit" className="rdr-primary-btn">
              {t('reader.saveNote')}
            </button>
          </div>
        </form>
      ) : (
        <>
          <div className="rdr-color-row" role="group" aria-label={t('reader.highlightColor')}>
            {HIGHLIGHT_COLORS.map((color) => (
              <button
                key={color.id}
                type="button"
                className="rdr-color-btn"
                style={{ '--rdr-swatch': color.value }}
                onClick={() => onHighlight(color.id)}
                aria-label={color.label}
                title={color.label}
              />
            ))}
          </div>
          <span className="rdr-select-divider" aria-hidden="true" />
          <button
            type="button"
            className="rdr-select-btn"
            onClick={() => setNoteOpen(true)}
            aria-label={t('reader.addNote')}
            title={t('reader.addNote')}
          >
            <NoteIcon size={18} />
          </button>
          <button
            type="button"
            className="rdr-select-btn"
            onClick={onCopy}
            aria-label={t('reader.copy')}
            title={t('reader.copy')}
          >
            <CopyIcon size={18} />
          </button>
          <button
            type="button"
            className="rdr-select-btn"
            onClick={onAsk}
            aria-label={t('reader.askSelection')}
            title={t('reader.askSelection')}
          >
            <SparkIcon size={18} />
          </button>
        </>
      )}
    </div>,
    document.body,
  )
}
