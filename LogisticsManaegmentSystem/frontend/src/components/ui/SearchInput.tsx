import { Search, X } from 'lucide-react'
import { useEffect, useId, useRef, useState } from 'react'
import { cn } from '@/lib/cn'

/**
 * Debounced search box.
 *
 * Typing does not hit the API on every keystroke. `delay` should be slightly
 * longer than a fast typist's inter-key interval so that "cus" issues one
 * request, not three.
 */
export function SearchInput({
  value,
  onChange,
  placeholder = 'Search...',
  delay = 350,
  className,
  autoFocus = false,
}: {
  value: string
  onChange: (value: string) => void
  placeholder?: string
  delay?: number
  className?: string
  autoFocus?: boolean
}) {
  const [draft, setDraft] = useState(value)
  const inputId = useId()

  // `onChange` is usually an inline arrow, so its identity changes on every
  // parent render. Holding it in a ref keeps it out of the debounce effect's
  // dependencies: otherwise a parent re-render (a loading flag flipping, for
  // example) would clear the pending timer and the request could be starved
  // indefinitely.
  const onChangeRef = useRef(onChange)

  // Declared before the debounce effect so the ref is already current by the
  // time that effect runs. Written in an effect rather than during render.
  useEffect(() => {
    onChangeRef.current = onChange
  }, [onChange])

  // Adjust during render rather than in an effect, so a parent that clears the
  // filter (e.g. "Reset") is reflected in the input on the very same paint
  // instead of one render later.
  const [lastValue, setLastValue] = useState(value)
  if (value !== lastValue) {
    setLastValue(value)
    setDraft(value)
  }

  useEffect(() => {
    if (draft === value) return

    const timer = setTimeout(() => onChangeRef.current(draft), delay)
    return () => clearTimeout(timer)
  }, [draft, value, delay])

  return (
    <div className={cn('relative', className)}>
      <label htmlFor={inputId} className="sr-only">
        {placeholder}
      </label>

      <Search
        className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-ink-400"
        aria-hidden
      />

      <input
        id={inputId}
        type="search"
        value={draft}
        autoFocus={autoFocus}
        onChange={(event) => setDraft(event.target.value)}
        placeholder={placeholder}
        className={cn(
          'h-9.5 w-full rounded-md border border-ink-300 bg-white pr-9 pl-9 text-sm text-ink-900 shadow-xs transition-colors',
          'placeholder:text-ink-400',
          'focus:border-brand-500 focus:ring-2 focus:ring-brand-500/25 focus:outline-none',
          // Hide the browser's own clear button; we render our own.
          '[&::-webkit-search-cancel-button]:appearance-none',
        )}
      />

      {draft && (
        <button
          type="button"
          onClick={() => setDraft('')}
          aria-label="Clear search"
          className="absolute top-1/2 right-2.5 flex size-5 -translate-y-1/2 items-center justify-center rounded text-ink-400 transition-colors hover:bg-ink-100 hover:text-ink-700"
        >
          <X className="size-3.5" aria-hidden />
        </button>
      )}
    </div>
  )
}
