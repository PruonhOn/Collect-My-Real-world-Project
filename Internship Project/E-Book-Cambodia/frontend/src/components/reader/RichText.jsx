import { useMemo } from 'react'
import katex from 'katex'
import { tokenizeCitations } from '../../utils/citations'

/**
 * KaTeX output is generated from escaped LaTeX with `trust: false`, so the only
 * raw HTML in the reader comes from the maths renderer - never from book text.
 */
function renderMath(tex, displayMode) {
  try {
    return katex.renderToString(tex, {
      displayMode,
      throwOnError: false,
      strict: 'ignore',
      trust: false,
      output: 'html',
    })
  } catch {
    return null
  }
}

function MathSpan({ tex, display = false }) {
  const html = useMemo(() => renderMath(tex, display), [display, tex])

  if (!html) {
    return <span className={display ? 'rdr-math-block' : 'rdr-math'}>{tex}</span>
  }

  return (
    <span
      className={display ? 'rdr-math-block' : 'rdr-math'}
      // eslint-disable-next-line react/no-danger
      dangerouslySetInnerHTML={{ __html: html }}
    />
  )
}

/** Split text at highlight quotes and mark the matching runs. */
function highlightRuns(text, annotations) {
  if (!annotations?.length) return [{ text, annotation: null }]

  const ranges = []
  for (const annotation of annotations) {
    const quote = annotation.quote
    if (!quote) continue
    let from = 0
    while (from <= text.length) {
      const at = text.indexOf(quote, from)
      if (at === -1) break
      ranges.push({ start: at, end: at + quote.length, annotation })
      from = at + quote.length
    }
  }

  if (!ranges.length) return [{ text, annotation: null }]
  ranges.sort((a, b) => a.start - b.start || b.end - a.end)

  const runs = []
  let cursor = 0
  for (const range of ranges) {
    if (range.start < cursor) continue
    if (range.start > cursor) runs.push({ text: text.slice(cursor, range.start), annotation: null })
    runs.push({
      text: text.slice(range.start, range.end),
      annotation: range.annotation,
    })
    cursor = range.end
  }
  if (cursor < text.length) runs.push({ text: text.slice(cursor), annotation: null })
  return runs
}

/** Parse `$inline maths$` and `**bold**` / `*italic*` without raw HTML. */
function renderRichText(text, keyBase) {
  const parts = []
  const pattern = /(\$[^$\n]+\$|\*\*[^*]+\*\*|\*[^*\n]+\*)/g
  let last = 0
  let match
  let serial = 0

  while ((match = pattern.exec(text)) !== null) {
    if (match.index > last) parts.push(text.slice(last, match.index))
    const token = match[0]

    if (token.startsWith('$')) {
      parts.push(<MathSpan key={`${keyBase}-m${serial}`} tex={token.slice(1, -1)} />)
    } else if (token.startsWith('**')) {
      parts.push(
        <strong key={`${keyBase}-b${serial}`}>
          {renderRichText(token.slice(2, -2), `${keyBase}-b${serial}`)}
        </strong>,
      )
    } else {
      parts.push(
        <em key={`${keyBase}-i${serial}`}>
          {renderRichText(token.slice(1, -1), `${keyBase}-i${serial}`)}
        </em>,
      )
    }

    last = match.index + token.length
    serial += 1
  }

  if (last < text.length) parts.push(text.slice(last))
  return parts
}

function InlineText({ text, annotations, onCite, keyBase }) {
  const tokens = useMemo(() => tokenizeCitations(text), [text])

  return tokens.map((token, tokenIndex) => {
    if (token.type === 'cite') {
      const label = token.ids.join(',')
      return (
        <sup className="rdr-cite" key={`${keyBase}-c${tokenIndex}`}>
          <button
            type="button"
            className="rdr-cite-btn"
            onClick={(event) => onCite?.(token.ids, event.currentTarget)}
            aria-label={`Source ${label}`}
            title={`Source ${label}`}
          >
            {label}
          </button>
        </sup>
      )
    }

    const runs = highlightRuns(token.value, annotations)
    return runs.map((run, runIndex) => {
      const content = renderRichText(run.text, `${keyBase}-t${tokenIndex}-${runIndex}`)
      if (!run.annotation) {
        return <span key={`${keyBase}-t${tokenIndex}-${runIndex}`}>{content}</span>
      }
      return (
        <mark
          key={`${keyBase}-t${tokenIndex}-${runIndex}`}
          className={`rdr-highlight rdr-highlight-${run.annotation.color || 'yellow'}`}
          data-annotation-id={run.annotation.id}
          title={run.annotation.note || undefined}
        >
          {content}
        </mark>
      )
    })
  })
}

function Block({ block, annotations, onCite, index }) {
  const keyBase = `blk${index}`

  if (block.type === 'heading') {
    const Tag = `h${Math.min(4, Math.max(2, block.level))}`
    return (
      <Tag className="rdr-subheading" data-rdr-block>
        <InlineText text={block.text} annotations={annotations} onCite={onCite} keyBase={keyBase} />
      </Tag>
    )
  }

  if (block.type === 'ul' || block.type === 'ol') {
    const Tag = block.type === 'ol' ? 'ol' : 'ul'
    return (
      <Tag className="rdr-list" data-rdr-block start={block.start}>
        {block.items.map((item, itemIndex) => (
          <li key={`${keyBase}-i${itemIndex}`}>
            <InlineText
              text={item}
              annotations={annotations}
              onCite={onCite}
              keyBase={`${keyBase}-i${itemIndex}`}
            />
          </li>
        ))}
      </Tag>
    )
  }

  if (block.type === 'quote') {
    return (
      <blockquote className="rdr-quote" data-rdr-block>
        <InlineText text={block.text} annotations={annotations} onCite={onCite} keyBase={keyBase} />
      </blockquote>
    )
  }

  if (block.type === 'math') {
    return (
      <div className="rdr-math-row" data-rdr-block>
        <MathSpan tex={block.text} display />
      </div>
    )
  }

  if (block.type === 'hr') {
    return <hr className="rdr-rule" data-rdr-block />
  }

  return (
    <p className="rdr-paragraph" data-rdr-block>
      <InlineText text={block.text} annotations={annotations} onCite={onCite} keyBase={keyBase} />
    </p>
  )
}

export default function RichText({ section, annotations = [], onCite }) {
  return (
    <section className="rdr-section" data-section-index={section.index}>
      {section.title && (
        <h2 className="rdr-chapter-title" data-rdr-block>
          {section.title}
        </h2>
      )}
      {section.blocks.map((block, index) => (
        <Block
          key={`${section.index}-${index}`}
          block={block}
          index={index}
          annotations={annotations}
          onCite={onCite}
        />
      ))}
    </section>
  )
}
