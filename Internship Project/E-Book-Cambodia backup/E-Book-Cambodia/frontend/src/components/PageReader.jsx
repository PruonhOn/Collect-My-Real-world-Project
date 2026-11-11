import { useRef, useState } from 'react'

export default function PageReader({ pages, showButtons = true, onChange, onReachEnd }) {
  const [index, setIndex] = useState(0)
  const touchStart = useRef(null)
  const total = pages.length

  const goTo = (target) => {
    if (target < 0 || target >= total) return
    setIndex(target)
    onChange?.(target)
  }

  const next = () => {
    if (index < total - 1) {
      goTo(index + 1)
    } else if (onReachEnd) {
      onReachEnd()
    } else {
      goTo(0)
    }
  }

  const prev = () => {
    if (index > 0) {
      goTo(index - 1)
    } else if (onReachEnd) {
      onReachEnd()
    }
  }

  const handleClick = (e) => {
    const rect = e.currentTarget.getBoundingClientRect()
    const x = e.clientX - rect.left
    if (x > rect.width / 2) {
      next()
    } else {
      prev()
    }
  }

  const handleTouchStart = (e) => {
    touchStart.current = e.touches[0].clientX
  }

  const handleTouchEnd = (e) => {
    if (touchStart.current === null) return
    const delta = e.changedTouches[0].clientX - touchStart.current
    touchStart.current = null
    if (delta < -40) {
      next()
    } else if (delta > 40) {
      prev()
    }
  }

  if (total === 0) return null

  const lastPage = index === total - 1

  return (
    <div>
      <div className="relative">
        {showButtons && index > 0 && (
          <button
            onClick={prev}
            aria-label="Previous page"
            className="absolute -left-4 md:-left-14 top-1/2 -translate-y-1/2 z-20 w-10 h-10 md:w-12 md:h-12 rounded-full bg-white shadow-md border border-gray-200 text-gray-600 flex items-center justify-center hover:bg-indigo-50 hover:text-indigo-600 transition-colors"
          >
            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 19l-7-7 7-7" />
            </svg>
          </button>
        )}

        <div
          role="button"
          tabIndex={0}
          onClick={handleClick}
          onKeyDown={(e) => {
            if (e.key === 'ArrowRight') next()
            if (e.key === 'ArrowLeft') prev()
          }}
          onTouchStart={handleTouchStart}
          onTouchEnd={handleTouchEnd}
          className="relative min-h-[380px] overflow-hidden rounded-2xl shadow-inner cursor-pointer select-none"
        >
          <PageTrack pages={pages} index={index} lastPage={lastPage} onReachEndHint={onReachEnd ? 'Tap to continue' : 'Tap to go back to the first page'} />

          <span className="absolute bottom-5 right-6 z-10 text-xs text-gray-400 bg-white/70 rounded-full px-2 py-0.5">
            {index + 1} / {total}
          </span>
        </div>

        {showButtons && !lastPage && (
          <button
            onClick={next}
            aria-label="Next page"
            className="absolute -right-4 md:-right-14 top-1/2 -translate-y-1/2 z-20 w-10 h-10 md:w-12 md:h-12 rounded-full bg-white shadow-md border border-gray-200 text-gray-600 flex items-center justify-center hover:bg-indigo-50 hover:text-indigo-600 transition-colors"
          >
            <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5l7 7-7 7" />
            </svg>
          </button>
        )}
      </div>

      <div className="mt-4 flex items-center justify-center gap-2">
        {Array.from({ length: total }).map((_, i) => (
          <button
            key={i}
            aria-label={`Go to page ${i + 1}`}
            onClick={() => goTo(i)}
            className={`w-2 h-2 rounded-full transition-all duration-300 ${
              i === index ? 'bg-indigo-600 w-5' : 'bg-gray-300 hover:bg-gray-400'
            }`}
          />
        ))}
      </div>

      <p className="text-center text-xs text-gray-400 mt-2">
        Tap right side, swipe, or use the arrow buttons to slide to the next page
      </p>
    </div>
  )
}

function PageTrack({ pages, index, lastPage, onReachEndHint }) {
  return (
    <div
      className="flex flex-nowrap items-stretch transition-transform duration-500 ease-in-out will-change-transform"
      style={{ transform: `translateX(-${index * 100}%)` }}
    >
      {pages.map((page, i) => (
        <div
          key={i}
          className="w-full shrink-0 bg-gradient-to-br from-amber-50 to-orange-50 p-8 md:p-10"
        >
          <div className="h-full flex flex-col">
            <p className="text-gray-800 leading-relaxed whitespace-pre-wrap flex-1">{page}</p>

            {i === index && lastPage && (
              <div className="mt-8 pt-6 border-t border-dashed border-amber-200 text-center">
                <p className="text-sm text-gray-400">{onReachEndHint}</p>
              </div>
            )}
          </div>
        </div>
      ))}
    </div>
  )
}
