import { useMemo, useState } from 'react'
import { useLanguage } from '../../context/LanguageContext'
import { searchSections } from '../../utils/readerContent'
import { SearchIcon } from './ReaderIcons'

export default function SearchPanel({ sections, onSelect }) {
  const { t } = useLanguage()
  const [query, setQuery] = useState('')
  const results = useMemo(() => searchSections(sections, query), [query, sections])

  const submit = (event) => {
    event.preventDefault()
    if (results[0]) onSelect(results[0].section)
  }

  return (
    <div className="rdr-search">
      <form className="rdr-search-form" onSubmit={submit} role="search">
        <SearchIcon size={19} />
        <input
          type="search"
          className="rdr-search-input"
          value={query}
          onChange={(event) => setQuery(event.target.value)}
          placeholder={t('reader.searchPlaceholder')}
          aria-label={t('reader.searchPlaceholder')}
          data-autofocus
        />
      </form>

      {query.trim() ? (
        <>
          <p className="rdr-result-count">
            {results.length
              ? `${results.length} ${t('reader.results')}`
              : t('reader.noResults')}
          </p>
          <ul className="rdr-result-list">
            {results.map((result, index) => (
              <li key={`${result.section}-${index}`}>
                <button type="button" className="rdr-result" onClick={() => onSelect(result.section)}>
                  <span className="rdr-result-title">
                    {result.title || `${t('reader.untitledSection')} ${result.section + 1}`}
                  </span>
                  <span className="rdr-result-page">
                    {t('reader.page')} {result.page}
                  </span>
                  <span className="rdr-result-snippet">{result.snippet}</span>
                </button>
              </li>
            ))}
          </ul>
        </>
      ) : (
        <p className="rdr-empty">{t('reader.searchHint')}</p>
      )}
    </div>
  )
}

