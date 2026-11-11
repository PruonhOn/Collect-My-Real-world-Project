import { Link } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { getEvents, eventCoverUrl } from '../../api'
import { useLanguage } from '../../context/LanguageContext'

const formatDateTime = (iso) => {
  if (!iso) return ''
  return new Date(iso).toLocaleString(undefined, {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

export default function EventsPage() {
  const { t } = useLanguage()

  const { data, isLoading } = useQuery({
    queryKey: ['events-all'],
    refetchInterval: 30000,
    queryFn: getEvents,
  })

  const events = data?.data?.events || []

  const FeaturedCard = ({ event }) => (
    <Link
      to={`/events/${event.id}`}
      className="group grid sm:grid-cols-2 rounded-2xl overflow-hidden border-2 border-amber-300 bg-white shadow-sm hover:shadow-lg transition-shadow"
    >
      <div className={`relative h-56 sm:h-full min-h-48 ${event.cover_image ? '' : 'bg-gradient-to-br from-sky-600 to-indigo-600'}`}>
        {event.cover_image ? (
          <img src={eventCoverUrl(event)} alt={event.title} className="absolute inset-0 w-full h-full object-cover" />
        ) : (
          <div className="absolute inset-0 flex items-center justify-center text-5xl">🗓️</div>
        )}
        <span className="absolute top-3 left-3 inline-block px-3 py-1 rounded-full bg-amber-400 text-amber-900 text-xs font-bold uppercase tracking-wide">
          ⭐ {t('featured')}
        </span>
      </div>
      <div className="p-6 flex flex-col justify-center">
        {event.category && (
          <span className="inline-block w-fit text-[11px] font-bold uppercase tracking-wide text-sky-600 bg-sky-50 px-2 py-0.5 rounded-full">
            {event.category}
          </span>
        )}
        <h2 className="mt-2 text-2xl font-bold text-gray-900 group-hover:text-indigo-700 transition-colors">{event.title}</h2>
        <p className="mt-2 text-sm text-gray-600 leading-relaxed line-clamp-3">{event.description}</p>
        <div className="mt-4 flex flex-wrap gap-x-4 gap-y-1 text-sm text-gray-500">
          <span>🗓️ {formatDateTime(event.start_date)}</span>
          {event.location && <span>📍 {event.location}</span>}
        </div>
        <div className="mt-4 flex items-center gap-3">
          <span className="inline-block px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">
            {event.fee_type === 'paid' ? `$${event.fee_amount}` : t('feeFree')}
          </span>
          <span className="text-xs text-gray-400">{t('viewDetails')}</span>
        </div>
      </div>
    </Link>
  )

  const EventCard = ({ event }) => (
    <Link
      to={`/events/${event.id}`}
      className="group flex flex-col bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-lg transition-shadow"
    >
      <div className={`relative h-40 ${event.cover_image ? '' : 'bg-gradient-to-br from-sky-500 to-indigo-600'}`}>
        {event.cover_image ? (
          <img src={eventCoverUrl(event)} alt={event.title} className="absolute inset-0 w-full h-full object-cover" />
        ) : (
          <div className="absolute inset-0 flex items-center justify-center text-4xl">📅</div>
        )}
        {event.category && (
          <span className="absolute top-3 left-3 inline-block text-[11px] font-bold uppercase tracking-wide text-sky-700 bg-white/90 px-2 py-0.5 rounded-full">
            {event.category}
          </span>
        )}
      </div>
      <div className="p-5 flex-1 flex flex-col">
        <h2 className="text-lg font-semibold text-gray-900 group-hover:text-indigo-700 transition-colors">{event.title}</h2>
        <p className="mt-2 text-sm text-gray-600 leading-relaxed line-clamp-2">{event.description}</p>
        <div className="mt-3 flex-1" />
        <div className="flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500">
          <span>🗓️ {formatDateTime(event.start_date)}</span>
          {event.location && <span>📍 {event.location}</span>}
        </div>
        <div className="mt-3 flex items-center justify-between">
          <span className="inline-block px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">
            {event.fee_type === 'paid' ? `$${event.fee_amount}` : t('feeFree')}
          </span>
          <span className="inline-flex items-center gap-1 text-sm font-medium text-indigo-600">
            {t('viewDetails')} →
          </span>
        </div>
      </div>
    </Link>
  )

  return (
    <div>
      <section className="bg-gradient-to-r from-sky-700 via-blue-600 to-indigo-600 text-white">
        <div className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-16 md:py-20 text-center">
          <div className="text-5xl mb-4">🗓️</div>
          <h1 className="text-4xl md:text-5xl font-bold leading-tight mb-4">{t('events')}</h1>
          <p className="text-lg text-sky-100 mb-8">{t('eventsHeroSubtitle')}</p>
          <Link
            to="/contact"
            className="inline-block px-6 py-3 bg-white text-sky-700 font-semibold rounded-xl hover:bg-sky-50 transition-colors"
          >
            {t('contact')}
          </Link>
        </div>
      </section>

      <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        {isLoading ? (
          <div className="text-center text-gray-500 py-12">{t('loading')}</div>
        ) : events.length === 0 ? (
          <div className="text-center text-gray-500 py-12">{t('eventsEmpty')}</div>
        ) : (
          <div className="space-y-8">
            {events.filter((e) => e.is_featured).map((event) => (
              <FeaturedCard key={event.id} event={event} />
            ))}
            <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
              {events.filter((e) => !e.is_featured).map((event) => (
                <EventCard key={event.id} event={event} />
              ))}
            </div>
          </div>
        )}
      </section>
    </div>
  )
}