import { useMemo } from 'react'
import { useLanguage } from '../../context/LanguageContext'
import { LockIcon } from './ReaderIcons'

function flatten(items, depth = 0, out = []) {
  for (const item of items || []) {
    out.push({ ...item, depth: item.depth || depth })
    flatten(item.children, depth + 1, out)
  }
  return out
}

/**
 * Table of contents. Uses the editor/PDF-authored TOC when present and falls
 * back to the headings detected in the text, so every book has a usable list.
 */
export default function TocPanel({ toc, sections, currentSection, locale, onSelect, onLocked }) {
  const { t } = useLanguage()

  const entries = useMemo(() => {
    const authored = flatten(toc)
    if (authored.length) {
      return authored.map((item) => ({
        id: `toc-${item.id}`,
        title: locale === 'en' && item.title_en ? item.title_en : item.title,
        page: item.page,
        depth: item.depth,
        locked: !!item.locked,
        target: Math.max(0, Math.min(sections.length - 1, Number(item.page) - 1)),
      }))
    }

    return sections.map((section, index) => ({
      id: `section-${index}`,
      title: section.title || `${t('reader.untitledSection')} ${index + 1}`,
      page: section.page,
      depth: 0,
      locked: false,
      target: index,
    }))
  }, [locale, sections, t, toc])

  const active = useMemo(() => {
    let match = null
    for (const entry of entries) {
      if (entry.target <= currentSection) match = entry
    }
    return match
  }, [currentSection, entries])

  if (!entries.length) {
    return <p className="rdr-empty">{t('reader.noContents')}</p>
  }

  return (
    <div className="rdr-toc">
      <ol className="rdr-toc-list">
        {entries.map((entry) => {
          const isActive = active?.id === entry.id
          return (
            <li
              key={entry.id}
              className="rdr-toc-item"
              style={{ '--rdr-toc-depth': Math.min(4, entry.depth || 0) }}
            >
              <button
                type="button"
                className={`rdr-toc-btn${isActive ? ' is-current' : ''}`}
                onClick={() => (entry.locked ? onLocked() : onSelect(entry.target))}
                aria-current={isActive ? 'location' : undefined}
              >
                <span className="rdr-toc-title">{entry.title}</span>
                <span className="rdr-toc-page">
                  {entry.locked ? (
                    <LockIcon size={15} aria-label={t('toc.lockedNotice')} />
                  ) : (
                    entry.page
                  )}
                </span>
              </button>
            </li>
          )
        })}
      </ol>

      <p className="rdr-panel-note">{t('reader.tocHint')}</p>
    </div>
  )
}
