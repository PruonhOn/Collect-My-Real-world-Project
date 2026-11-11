import { useEffect, useRef, useState } from 'react'
import { useLanguage } from '../../context/LanguageContext'
import { slugOfName } from '../../utils/grades'

export default function SearchBar({
  value = '',
  onChange,
  categories = [],
  category = '',
  onCategoryChange,
  compact = false,
}) {
  const { t } = useLanguage()
  const [prevValue, setPrevValue] = useState(value)
  const [local, setLocal] = useState(value)
  const timerRef = useRef(null)

  if (value !== prevValue) {
    setPrevValue(value)
    setLocal(value)
  }

  useEffect(() => {
    if (local !== value) {
      if (timerRef.current) window.clearTimeout(timerRef.current)
      timerRef.current = window.setTimeout(() => onChange(local), 350)
    }
    return () => {
      if (timerRef.current) window.clearTimeout(timerRef.current)
    }
  }, [local]) // eslint-disable-line react-hooks/exhaustive-deps

  const submit = () => {
    if (timerRef.current) window.clearTimeout(timerRef.current)
    onChange(local)
  }

  const selectClass = `bg-transparent text-sm text-gray-700 focus:outline-none cursor-pointer`

  return (
    <div id="catalog-search" className={compact ? '' : 'scroll-mt-24'}>
      <div
        className={`flex flex-col md:flex-row items-stretch md:items-center gap-2 ${
          compact ? 'p-1.5' : 'p-2'
        } bg-white rounded-2xl border border-gray-200/80 shadow-lg shadow-slate-200/40`}
      >
        <div className="flex flex-1 items-center gap-3 px-3 md:px-4">
          <svg className="w-5 h-5 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <input
            id="catalog-search-input"
            type="text"
            value={local}
            onChange={(e) => setLocal(e.target.value)}
            onKeyDown={(e) => e.key === 'Enter' && submit()}
            placeholder={t('searchPlaceholder')}
            className={`w-full bg-transparent focus:outline-none text-gray-800 placeholder-gray-400 ${
              compact ? 'py-2 text-sm' : 'py-2.5 md:py-3 text-base'
            }`}
          />
        </div>

        <div className="hidden md:block w-px bg-gray-200 h-8 self-center" aria-hidden="true" />

        <div className="flex items-center gap-2 px-3 md:px-4 border-t md:border-t-0 border-gray-100 pt-2 md:pt-0 md:pl-0">
          <svg className="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 6h16M4 10h16M4 14h9M4 18h9" />
          </svg>
          <select
            value={category}
            onChange={(e) => onCategoryChange(e.target.value)}
            className={selectClass}
            aria-label={t('bookCategories')}
          >
            <option value="">{t('bookCategories')}</option>
            {categories.map((cat) => (
              <option key={cat.id} value={slugOfName(cat.name)}>
                {cat.display_name || cat.name}
              </option>
            ))}
          </select>
        </div>

        <button
          type="button"
          onClick={submit}
          className={`inline-flex items-center justify-center gap-2 rounded-xl font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition-colors ${
            compact ? 'px-4 py-2 text-sm' : 'px-6 py-2.5 md:py-3 text-sm'
          }`}
        >
          <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          {t('searchButton')}
        </button>
      </div>
    </div>
  )
}