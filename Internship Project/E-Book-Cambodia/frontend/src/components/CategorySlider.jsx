import { useEffect, useRef } from 'react'
import { Link } from 'react-router-dom'

const gradients = [
  'from-indigo-500 to-purple-500',
  'from-emerald-500 to-teal-500',
  'from-rose-500 to-pink-500',
  'from-amber-500 to-orange-500',
  'from-sky-500 to-blue-500',
  'from-fuchsia-500 to-purple-600',
]

const keywordEmojis = [
  ['literature', '📖'],
  ['education', '🎓'],
  ['learn', '📚'],
  ['history', '🏛️'],
  ['technology', '💻'],
  ['business', '💼'],
  ['health', '💊'],
  ['math', '🔢'],
  ['science', '🔬'],
  ['art', '🎨'],
  ['music', '🎵'],
  ['portfolio', '📁'],
]

function categoryEmoji(name = '') {
  const lower = name.toLowerCase()
  const found = keywordEmojis.find(([kw]) => lower.includes(kw))
  return found ? found[1] : '📚'
}

export default function CategorySlider({ categories }) {
  const trackRef = useRef(null)
  const pausedRef = useRef(false)

  useEffect(() => {
    if (!categories || categories.length === 0) return undefined

    const timer = setInterval(() => {
      const track = trackRef.current
      if (!track || pausedRef.current) return

      if (track.scrollLeft + track.clientWidth >= track.scrollWidth - 8) {
        track.scrollTo({ left: 0, behavior: 'smooth' })
      } else {
        const card = track.querySelector('[data-slide]')
        const step = card ? card.offsetWidth + 16 : 220
        track.scrollBy({ left: step, behavior: 'smooth' })
      }
    }, 3000)

    return () => clearInterval(timer)
  }, [categories])

  const scroll = (dir) => {
    const track = trackRef.current
    if (!track) return
    const card = track.querySelector('[data-slide]')
    const step = card ? card.offsetWidth + 16 : 220
    track.scrollBy({ left: dir * step, behavior: 'smooth' })
  }

  if (!categories || categories.length === 0) {
    return (
      <div className="text-center py-10 text-gray-400">
        <p>No categories available yet.</p>
      </div>
    )
  }

  return (
    <div className="relative">
      <button
        onClick={() => scroll(-1)}
        aria-label="Previous categories"
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
        {categories.map((cat, i) => (
          <Link
            key={cat.id}
            data-slide
            to={`/categories/${cat.id}`}
            className={`snap-start shrink-0 w-[220px] sm:w-[240px] bg-gradient-to-br ${gradients[i % gradients.length]} rounded-2xl p-5 text-white shadow-lg hover:shadow-xl hover:-translate-y-1 transition-all group relative overflow-hidden`}
          >
            <div className="absolute -right-6 -top-6 w-24 h-24 bg-white/10 rounded-full" />
            <div className="absolute -right-1 -top-1 w-10 h-10 bg-white/10 rounded-full" />

            <div className="text-4xl mb-3 drop-shadow-sm">{categoryEmoji(cat.name)}</div>
            <h3 className="text-lg font-bold leading-snug">{cat.display_name || cat.name}</h3>
            {(cat.display_description || cat.description) && (
              <p className="text-xs text-white/80 mt-1.5 leading-relaxed line-clamp-2">
                {cat.display_description || cat.description}
              </p>
            )}
            <div className="mt-4 flex items-center justify-between">
              <span className="inline-flex items-center gap-1.5 text-xs font-semibold bg-white/20 rounded-full px-3 py-1">
                📚 {cat.books_count ?? 0} books
              </span>
              <span className="text-white text-lg transition-transform group-hover:translate-x-1">→</span>
            </div>
          </Link>
        ))}
      </div>

      <button
        onClick={() => scroll(1)}
        aria-label="Next categories"
        className="hidden sm:flex absolute -right-3 md:-right-5 top-1/2 -translate-y-1/2 z-10 w-10 h-10 rounded-full bg-white shadow-md border border-gray-200 text-gray-600 items-center justify-center hover:bg-indigo-50 hover:text-indigo-600 transition-colors"
      >
        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 5l7 7-7 7" />
        </svg>
      </button>
    </div>
  )
}