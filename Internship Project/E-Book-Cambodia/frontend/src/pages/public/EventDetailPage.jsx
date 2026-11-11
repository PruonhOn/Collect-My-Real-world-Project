import { useState } from 'react'
import { Link, useNavigate, useParams } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { getEvent, registerForEvent, eventCoverUrl } from '../../api'
import { useLanguage } from '../../context/LanguageContext'
import { useAuth } from '../../context/AuthContext'

const formatDateTime = (iso) => {
  if (!iso) return '—'
  return new Date(iso).toLocaleString(undefined, {
    weekday: 'short',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

export default function EventDetailPage() {
  const { id } = useParams()
  const { t } = useLanguage()
  const { user } = useAuth()
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const [msg, setMsg] = useState({ type: '', text: '' })

  const { data, isLoading, isError } = useQuery({
    queryKey: ['event', id],
    refetchInterval: 30000,
    queryFn: () => getEvent(id),
  })

  const event = data?.data?.event

  const registerMutation = useMutation({
    mutationFn: () => registerForEvent(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['event', id] })
      queryClient.invalidateQueries({ queryKey: ['events-all'] })
      setMsg({ type: 'success', text: t('registrationSuccessful') })
    },
    onError: (err) => {
      const message =
        err.response?.data?.message || err.response?.data?.error || t('registrationError')
      setMsg({ type: 'error', text: message })
    },
  })

  const handleRegister = () => {
    if (!user) {
      navigate('/login')
      return
    }
    setMsg({ type: '', text: '' })
    registerMutation.mutate()
  }

  if (isLoading) {
    return <div className="py-24 text-center text-gray-500">{t('loading')}</div>
  }

  if (isError || !event) {
    return (
      <div className="py-24 text-center">
        <div className="text-5xl mb-4">🗓️</div>
        <p className="text-gray-500">{t('notFound')}</p>
        <Link to="/events" className="mt-4 inline-block text-indigo-600 hover:text-indigo-800">
          {t('backToEvents')}
        </Link>
      </div>
    )
  }

  const cover = eventCoverUrl(event)

  const showRegistrationButton =
    event.status === 'published' && !event.is_registered && !event.isFull

  return (
    <div>
      <section className={`bg-gradient-to-r ${cover ? 'from-sky-800 via-blue-700 to-indigo-700' : 'from-sky-700 via-blue-600 to-indigo-600'} text-white`}>
        <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
          <Link to="/events" className="text-sky-200 hover:text-white text-sm font-medium">
            ← {t('backToEvents')}
          </Link>
          <div className="mt-6 flex flex-wrap items-center gap-2">
            {event.category && (
              <span className="inline-block text-[11px] font-bold uppercase tracking-wide text-sky-100 bg-white/15 px-3 py-1 rounded-full">
                {event.category}
              </span>
            )}
            {event.is_featured && (
              <span className="inline-block text-[11px] font-bold uppercase tracking-wide text-amber-900 bg-amber-400 px-3 py-1 rounded-full">
                ⭐ {t('featured')}
              </span>
            )}
            {event.status === 'cancelled' && (
              <span className="inline-block text-[11px] font-bold uppercase tracking-wide bg-red-500 px-3 py-1 rounded-full">
                {t('cancelled')}
              </span>
            )}
            {event.status === 'completed' && (
              <span className="inline-block text-[11px] font-bold uppercase tracking-wide bg-indigo-500 px-3 py-1 rounded-full">
                {t('completed')}
              </span>
            )}
          </div>
          <h1 className="mt-3 text-3xl md:text-4xl font-bold leading-tight">{event.title}</h1>
          <div className="mt-4 flex flex-wrap gap-x-6 gap-y-2 text-sky-100 text-sm">
            <span>🗓️ {formatDateTime(event.start_date)}</span>
            {event.end_date && <span> → {formatDateTime(event.end_date)}</span>}
            {event.location && <span>📍 {event.location}</span>}
          </div>
        </div>
      </section>

      <section className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        {event.status === 'cancelled' && (
          <div className="mb-6 p-4 rounded-xl bg-red-50 text-red-700 text-sm font-medium">
            ⛔ {t('notOpenForRegistration')}
          </div>
        )}
        {event.status === 'completed' && (
          <div className="mb-6 p-4 rounded-xl bg-indigo-50 text-indigo-700 text-sm font-medium">
            ✔ {t('completed')}
          </div>
        )}

        <div className="grid lg:grid-cols-3 gap-8">
          <div className="lg:col-span-2 space-y-6">
            {cover && (
              <img src={cover} alt={event.title} className="w-full max-h-80 object-cover rounded-2xl shadow-sm" />
            )}

            <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
              <h2 className="text-lg font-semibold text-gray-900 mb-3">{t('eventDescription')}</h2>
              <p className="text-gray-700 leading-relaxed whitespace-pre-line">{event.description}</p>
            </div>

            <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 grid sm:grid-cols-2 gap-4">
              {[
                [t('eventOrganizer'), event.organizer],
                [t('eventSpeaker'), event.speaker],
                [t('eventContactPhone'), event.contact_phone],
                [t('eventContactEmail'), event.contact_email],
                [t('eventMaxParticipants'), event.max_participants != null ? `${event.max_participants} ${t('participants')}` : null],
              ]
                .filter(([, v]) => v)
                .map(([label, value]) => (
                  <div key={label}>
                    <p className="text-xs text-gray-400">{label}</p>
                    <p className="font-medium text-gray-800">{value}</p>
                  </div>
                ))}
              {event.social_links?.length > 0 && (
                <div className="sm:col-span-2">
                  <p className="text-xs text-gray-400">{t('eventSocialLinks')}</p>
                  <div className="flex flex-wrap gap-2 mt-1">
                    {event.social_links.map((link) => (
                      <a
                        key={link}
                        href={link}
                        target="_blank"
                        rel="noreferrer"
                        className="text-sm text-indigo-600 hover:text-indigo-800 break-all"
                      >
                        🔗 {link}
                      </a>
                    ))}
                  </div>
                </div>
              )}
            </div>
          </div>

          <div className="space-y-6">
            <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
              <p className="text-xs text-gray-400 uppercase tracking-wide">{t('eventFee')}</p>
              <p className="mt-1 text-2xl font-bold text-gray-900">
                {event.fee_type === 'paid' ? `$${event.fee_amount}` : t('feeFree')}
              </p>
              {event.max_participants != null && event.status === 'published' && (
                <p className="mt-3 text-sm text-gray-500">
                  {event.spots_left > 0 ? (
                    <>
                      <span className="text-emerald-600 font-semibold">{event.spots_left} {t('spotsLeft')}</span>
                      {' · '}{event.max_participants} {t('participants')}
                    </>
                  ) : (
                    <span className="text-red-600 font-semibold">{t('eventFull')}</span>
                  )}
                </p>
              )}
            </div>

            {event.status === 'published' && event.registration_link && (
              <a
                href={event.registration_link}
                target="_blank"
                rel="noreferrer"
                className="block text-center px-6 py-3 rounded-xl bg-gray-900 text-white font-semibold hover:bg-gray-800 transition-colors"
              >
                ↗ {t('registrationViaLink')}
              </a>
            )}

            {event.status === 'published' && (
              <div>
                {msg.text && (
                  <div
                    className={`mb-3 p-3 rounded-xl text-sm ${
                      msg.type === 'success' ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700'
                    }`}
                  >
                    {msg.text}
                  </div>
                )}
                {event.is_registered ? (
                  <div className="text-center px-6 py-3 rounded-xl bg-emerald-100 text-emerald-700 font-semibold">
                    ✓ {t('alreadyRegistered')}
                  </div>
                ) : showRegistrationButton ? (
                  <button
                    onClick={handleRegister}
                    disabled={registerMutation.isPending}
                    className="w-full text-center px-6 py-3 rounded-xl bg-indigo-600 text-white font-semibold hover:bg-indigo-700 disabled:opacity-60 transition-colors"
                  >
                    {user ? t('registerForEvent') : t('register')}
                  </button>
                ) : event.isFull && !event.is_registered ? (
                  <div className="text-center px-6 py-3 rounded-xl bg-red-50 text-red-700 font-semibold">
                    {t('eventFull')}
                  </div>
                ) : null}
              </div>
            )}

            <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
              <p className="text-xs text-gray-400 uppercase tracking-wide">{t('organizedBy')}</p>
              <p className="mt-1 font-semibold text-gray-800">{event.organizer || t('eventOrganizer')}</p>
              {event.contact_email && (
                <a href={`mailto:${event.contact_email}`} className="mt-2 block text-sm text-indigo-600 hover:text-indigo-800">
                  {event.contact_email}
                </a>
              )}
              {event.contact_phone && (
                <p className="mt-1 text-sm text-gray-600">{event.contact_phone}</p>
              )}
            </div>
          </div>
        </div>
      </section>
    </div>
  )
}