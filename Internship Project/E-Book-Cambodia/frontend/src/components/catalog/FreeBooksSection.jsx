import { useQuery } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import { getBooks } from '../../api'
import BookCard from '../BookCard'
import { useLanguage } from '../../context/LanguageContext'

// Shared by the home page and the books page, which use different container
// widths, so the caller decides.
export default function FreeBooksSection({
  freeBooksCount,
  containerClassName = 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8',
}) {
  const { t } = useLanguage()

  const { data, isLoading } = useQuery({
    queryKey: ['books', 'free-spotlight'],
    refetchInterval: 60000,
    queryFn: () => getBooks({ free: '1', per_page: 4 }),
  })
  const books = data?.data?.data || []

  if (!isLoading && books.length === 0) return null

  return (
    <section className={`${containerClassName} pt-14`}>
      <div className="rounded-3xl overflow-hidden bg-gradient-to-br from-emerald-600 to-teal-700 text-white shadow-xl shadow-emerald-600/10">
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 items-center p-6 sm:p-10 lg:p-12">
          <div>
            <span className="inline-flex items-center gap-2 text-xs font-bold bg-white/15 rounded-full px-3 py-1.5">
              🎁 {freeBooksCount ? `${freeBooksCount} ${t('freeBooks')}` : t('free')}
            </span>
            <h2 className="mt-4 text-2xl sm:text-3xl font-bold leading-snug">{t('freeStartTitle')}</h2>
            <p className="mt-3 text-emerald-50/90 leading-relaxed">{t('freeStartSub')}</p>
            <Link
              to="/books?type=free"
              className="mt-6 inline-flex items-center gap-2 px-6 py-3 bg-white text-emerald-700 font-semibold rounded-xl shadow-lg hover:bg-emerald-50 hover:-translate-y-0.5 transition-all"
            >
              <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
              </svg>
              {t('readFreeBooks')}
            </Link>
          </div>

          <div className="flex gap-4 overflow-x-auto scrollbar-hide snap-x snap-mandatory pb-2 lg:pb-0">
            {isLoading
              ? [...Array(4)].map((_, i) => (
                  <div key={i} className="shrink-0 w-[130px] sm:w-[150px] animate-pulse">
                    <div className="aspect-[3/4] bg-white/20 rounded-xl" />
                    <div className="h-3 bg-white/20 rounded mt-2 w-3/4" />
                  </div>
                ))
              : books.map((book) => (
                  <div key={book.id} className="shrink-0 w-[130px] sm:w-[150px] snap-start">
                    <BookCard book={book} />
                  </div>
                ))}
          </div>
        </div>
      </div>
    </section>
  )
}