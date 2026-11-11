import { useEffect, useRef } from 'react'
import BookCard from './BookCard'

export default function BookSlider({ books = [], loading = false }) {
  const trackRef = useRef(null)
  const pausedRef = useRef(false)

  useEffect(() => {
    if (!loading || books.length === 0) return undefined
    const timer = setInterval(() => {
      const track = trackRef.current
      if (!track || pausedRef.current) return
      if (track.scrollLeft + track.clientWidth >= track.scrollWidth - 8) {
        track.scrollTo({ left: 0, behavior: 'smooth' })
      } else {
        const card = track.querySelector('[data-slide]')
        const step = card ? card.offsetWidth + 16 : 200
        track.scrollBy({ left: step, behavior: 'smooth' })
      }
    }, 3000)
    return () => clearInterval(timer)
  }, [books, loading])

  const scroll = (dir) => {
    const track = trackRef.current
    if (!track) return
    const card = track.querySelector('[data-slide]')
    const step = card ? card.offsetWidth + 16 : 200
    track.scrollBy({ left: dir * step, behavior: 'smooth' })
  }

  if (loading) {
    return (
      <div className="flex gap-4 overflow-hidden">
        {[...Array(4)].map((_, i) => (
          <div key={i} className="shrink-0 w-[160px] animate-pulse">
            <div className="aspect-[3/4] bg-gray-200 rounded-xl" />
            <div className="h-4 bg-gray-200 rounded mt-3 w-3/4" />
            <div className="h-3 bg-gray-200 rounded mt-2 w-1/2" />
          </div>
        ))}
      </div>
    )
  }

  if (books.length === 0) return null

  return (
    <div className="relative group/slider">
      <button
        onClick={() => scroll(-1)}
        aria-label="Previous"
        className="hidden sm:flex absolute -left-3 md:-left-5 top-1/2 -translate-y-1/2 z-10 w-10 h-10 rounded-full bg-white shadow-md border border-gray-200 text-gray-600 items-center justify-center hover:bg-indigo-50 hover:text-indigo-600 transition-colors"
      >
        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 19l-7-7 7-7" />
        </svg>
      </button>

      <div
        ref={trackRef}
        onMouseEnter={() => (pausedRef.current = true)}
        onMouseLeave={() => (pausedRef.current = false)}
        className="flex gap-4 overflow-x-auto scroll-smooth snap-x snap-mandatory scrollbar-hide pb-2"
      >
        {books.map((book) => (
          <div key={book.id} data-slide className="snap-start shrink-0 w-[160px] sm:w-[180px]">
            <BookCard book={book} />
          </div>
        ))}
      </div>

      <button
        onClick={() => scroll(1)}
        aria-label="Next"
        className="hidden sm:flex absolute -right-3 md:-right-5 top-1/2 -translate-y-1/2 z-10 w-10 h-10 rounded-full bg-white shadow-md border border-gray-200 text-gray-600 items-center justify-center hover:bg-indigo-50 hover:text-indigo-600 transition-colors"
      >
        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5l7 7-7 7" />
        </svg>
      </button>
    </div>
  )
}
