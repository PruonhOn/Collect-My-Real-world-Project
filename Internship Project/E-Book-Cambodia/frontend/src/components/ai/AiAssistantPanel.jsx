import { useEffect, useRef, useState } from 'react'
import { getAiOptions, runAiAction } from '../../api'
import { useLanguage } from '../../context/LanguageContext'
import { usePageContext } from './usePageContext'

const MAX_QUESTION_CHARS = 500

const ACTION_ICONS = {
  translate: (
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <path d="M4 5h9M8.5 3v2c0 4-2 7-4.5 9" />
      <path d="M7 10c1.5 2.5 3.5 4.5 6 5.5" />
      <path d="M13 21l4.5-11L22 21M14.8 17.5h4.4" />
    </svg>
  ),
  search: (
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <circle cx="11" cy="11" r="7" />
      <path d="M21 21l-4.3-4.3" />
    </svg>
  ),
  explain: (
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <path d="M12 3l1.8 4.2L18 9l-4.2 1.8L12 15l-1.8-4.2L6 9l4.2-1.8z" />
      <path d="M18 15l.9 2.1L21 18l-2.1.9L18 21l-.9-2.1L15 18l2.1-.9z" />
    </svg>
  ),
  summarize: (
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <path d="M5 4h14M5 9h14M5 14h9M5 19h6" />
    </svg>
  ),
  ask: (
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
      <path d="M21 12a8 8 0 1 1-3.2-6.4" />
      <path d="M8.5 10.5h.01M12 10.5h.01M15.5 10.5h.01" />
      <path d="M8 14.5c1.2 1.2 2.5 1.8 4 1.8s2.8-.6 4-1.8" />
    </svg>
  ),
}

const ACTIONS = ['translate', 'search', 'explain', 'summarize', 'ask']

/** Actions that need something typed in before they can run. */
const QUESTION_ACTIONS = new Set(['search', 'ask'])

/**
 * Minimal Markdown renderer for assistant answers.
 *
 * Assistant output is generated text, not user-authored HTML, so the renderer
 * only ever produces React elements - it never sets innerHTML. That keeps a
 * prompt-injected answer from becoming an XSS vector.
 */
function renderAnswer(text) {
  const blocks = []
  const lines = String(text || '').split('\n')

  lines.forEach((line, index) => {
    const trimmed = line.trim()

    if (!trimmed) return

    const bullet = trimmed.match(/^[-*•]\s+(.*)$/)
    const ordered = trimmed.match(/^(\d+)[.)]\s+(.*)$/)
    const boldAll = trimmed.match(/^\*\*(.+)\*\*$/)

    if (bullet) {
      blocks.push(
        <li key={`b${index}`} className="ai-list-item">
          <span>{inline(bullet[1], `b${index}`)}</span>
        </li>
      )
      return
    }

    if (ordered) {
      blocks.push(
        <li key={`o${index}`} className="ai-list-item">
          <span className="ai-list-marker">{ordered[1]}.</span>
          <span>{inline(ordered[2], `o${index}`)}</span>
        </li>
      )
      return
    }

    if (boldAll) {
      blocks.push(
        <p key={`h${index}`} className="ai-answer-heading">
          {boldAll[1]}
        </p>
      )
      return
    }

    blocks.push(
      <p key={`p${index}`} className="ai-answer-paragraph">
        {inline(trimmed, `p${index}`)}
      </p>
    )
  })

  if (!blocks.length) {
    return <p className="ai-answer-paragraph ai-muted">{''}</p>
  }

  return blocks
}

/** Handle `**bold**` and `` `code` `` runs without ever using raw HTML. */
function inline(text, keyBase) {
  const parts = []
  const pattern = /(\*\*[^*]+\*\*|`[^`]+`)/g
  let lastIndex = 0
  let match
  let n = 0

  while ((match = pattern.exec(text)) !== null) {
    if (match.index > lastIndex) {
      parts.push(text.slice(lastIndex, match.index))
    }
    const token = match[0]
    if (token.startsWith('**')) {
      parts.push(<strong key={`${keyBase}-b${n}`}>{token.slice(2, -2)}</strong>)
    } else {
      parts.push(<code key={`${keyBase}-c${n}`} className="ai-code">{token.slice(1, -1)}</code>)
    }
    lastIndex = match.index + token.length
    n += 1
  }

  if (lastIndex < text.length) parts.push(text.slice(lastIndex))

  return parts
}

const IDLE = { page: null, status: 'idle', result: null, error: '' }

export default function AiAssistantPanel({
  bookId,
  pageNumber,
  pdfDoc,
  isPdf,
  pages,
  isOpen,
  onToggle,
  hideLauncher = false,
  initialQuestion = '',
  askNonce = 0,
}) {
  const { t, locale } = useLanguage()
  const [options, setOptions] = useState(null)
  const [action, setAction] = useState(null)
  const [query, setQuery] = useState('')
  // Null until the reader picks a language, so the panel follows the interface
  // language until then and remembers the choice afterwards.
  const [pickedLanguage, setPickedLanguage] = useState(null)
  const [state, setState] = useState(IDLE)
  const [isUnavailable, setIsUnavailable] = useState(false)
  const resultRef = useRef(null)

  // A selection made in the reader can open the assistant with the passage
  // already in the question box. The nonce allows the same passage to be sent
  // twice in a row.
  useEffect(() => {
    if (!isOpen || !initialQuestion) return
    setAction('ask')
    setQuery(initialQuestion.slice(0, MAX_QUESTION_CHARS))
    setState(IDLE)
    // IDLE is module-level and stable.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [askNonce, initialQuestion, isOpen])


  const { context, isPreparing } = usePageContext({ pdfDoc, isPdf, pages, pageNumber })

  useEffect(() => {
    let cancelled = false
    getAiOptions()
      .then((resp) => {
        if (cancelled) return
        setOptions(resp?.data?.options || null)
        setIsUnavailable(!resp?.data?.options?.enabled)
      })
      .catch(() => {
        if (!cancelled) setIsUnavailable(true)
      })
    return () => {
      cancelled = true
    }
  }, [])

  // Turning the page invalidates the previous answer, so it is only shown
  // while it still belongs to the page on screen.
  const view = state.page === pageNumber ? state : IDLE

  useEffect(() => {
    if (view.status === 'done') {
      resultRef.current?.scrollIntoView({ behavior: 'smooth', block: 'nearest' })
    }
  }, [view.status])

  // The backend owns the list of supported languages, so an unsupported one
  // falls back rather than being sent and rejected.
  const supportedLanguages = options?.languages ? Object.keys(options.languages) : ['en']
  const activeLanguage = pickedLanguage && supportedLanguages.includes(pickedLanguage)
    ? pickedLanguage
    : supportedLanguages.includes(locale) ? locale : supportedLanguages[0]

  const needsQuery = action ? QUESTION_ACTIONS.has(action) : false
  const canRun =
    !!action &&
    (!needsQuery || query.trim().length > 0) &&
    !isPreparing &&
    view.status !== 'loading' &&
    context?.source !== 'none'

  const reset = () => setState(IDLE)

  const run = async () => {
    if (!canRun) return

    setState({ page: pageNumber, status: 'loading', result: null, error: '' })

    try {
      const payload = {
        action,
        page: pageNumber,
        language: activeLanguage,
        text: context?.text || undefined,
        image: context?.image || undefined,
        query: needsQuery ? query.trim() : undefined,
      }
      const resp = await runAiAction(bookId, payload)
      setState({ page: pageNumber, status: 'done', result: resp?.data?.result || null, error: '' })
    } catch (err) {
      setState({
        page: pageNumber,
        status: 'error',
        result: null,
        error: err?.response?.data?.message || t('ai.error'),
      })
    }
  }

  if (isUnavailable) return null

  const actionLabel = (key) => t(`ai.actions.${key}`)
  const inputPlaceholder = action === 'search' ? t('ai.searchPlaceholder') : t('ai.askPlaceholder')
  const busyLabel = isPreparing ? t('ai.readingPage') : t('ai.working')

  return (
    <>
      {!hideLauncher ? (
        <button
          type="button"
          className="ai-launcher"
          onClick={onToggle}
          aria-label={isOpen ? t('ai.close') : t('ai.open')}
          title={isOpen ? t('ai.close') : t('ai.open')}
          aria-expanded={isOpen}
        >
          {isOpen ? (
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.4" strokeLinecap="round" aria-hidden="true">
              <path d="M6 6l12 12M18 6L6 18" />
            </svg>
          ) : (
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.4" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
              <path d="M12 3l1.9 4.4L18 9.3l-4.1 1.9L12 15.6l-1.9-4.4L6 9.3l4.1-1.9z" />
              <path d="M18 15l.9 2.1 2.1.9-2.1.9L18 21l-.9-2.1-2.1-.9 2.1-.9z" />
            </svg>
          )}
          <span className="ai-launcher-label">{t('ai.open')}</span>
        </button>
      ) : null}

      {isOpen && (
        <aside className="ai-panel" role="dialog" aria-label={t('ai.title')}>
          <header className="ai-head">
            <div className="ai-head-text">
              <h2 className="ai-title">{t('ai.title')}</h2>
              <p className="ai-subtitle">
                {t('ai.pageOf')} {pageNumber}
                {options?.driver === 'mock' ? ` · ${t('ai.demo')}` : ''}
              </p>
            </div>
            <button
              type="button"
              className="ai-icon-btn"
              onClick={onToggle}
              aria-label={t('ai.close')}
            >
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.4" strokeLinecap="round" aria-hidden="true">
                <path d="M6 6l12 12M18 6L6 18" />
              </svg>
            </button>
          </header>

          <div className="ai-actions" role="group">
            {ACTIONS.map((key) => (
              <button
                key={key}
                type="button"
                className={`ai-action${action === key ? ' is-active' : ''}`}
                onClick={() => {
                  setAction(key)
                  reset()
                }}
                aria-pressed={action === key}
                disabled={view.status === 'loading'}
              >
                {ACTION_ICONS[key]}
                <span>{actionLabel(key)}</span>
              </button>
            ))}
          </div>

          <div className="ai-controls">
            <label className="ai-field">
              <span className="ai-field-label">{t('ai.language')}</span>
              <select
                className="ai-select"
                value={activeLanguage}
                onChange={(e) => setPickedLanguage(e.target.value)}
                disabled={view.status === 'loading'}
              >
                {supportedLanguages.map((code) => (
                  <option key={code} value={code}>
                    {t(`ai.languages.${code}`)}
                  </option>
                ))}
              </select>
            </label>
          </div>

          {needsQuery && (
            <label className="ai-field">
              <span className="ai-field-label">{t('ai.askLabel')}</span>
              <textarea
                className="ai-textarea"
                rows={3}
                maxLength={MAX_QUESTION_CHARS}
                value={query}
                onChange={(e) => setQuery(e.target.value)}
                placeholder={inputPlaceholder}
                disabled={view.status === 'loading'}
              />
            </label>
          )}

          <button
            type="button"
            className="ai-run"
            onClick={run}
            disabled={!canRun}
          >
            {view.status === 'loading' ? busyLabel : t('ai.run')}
          </button>

          {context?.source === 'none' && !isPreparing && (
            <p className="ai-note ai-note--warn">{t('ai.noText')}</p>
          )}

          {view.status === 'loading' && (
            <div className="ai-state" role="status" aria-live="polite">
              <span className="ai-spinner" aria-hidden="true" />
              <span>{busyLabel}</span>
            </div>
          )}

          {view.status === 'error' && (
            <div className="ai-state ai-state--error" role="alert">
              <p>{view.error}</p>
              <button type="button" className="ai-retry" onClick={run}>
                {t('ai.retry')}
              </button>
            </div>
          )}

          <div ref={resultRef} className="ai-result" aria-live="polite">
            {view.status === 'idle' && !action && <p className="ai-note">{t('ai.empty')}</p>}

            {view.status === 'done' && view.result && (
              <>
                <div className="ai-result-meta">
                  {t('ai.fromPage')} {view.result.page}
                  {view.result.used_ocr ? ` · ${t('ai.ocrUsed')}` : ''}
                </div>
                <div className="ai-answer">{renderAnswer(view.result.result)}</div>
              </>
            )}
          </div>
        </aside>
      )}
    </>
  )
}

