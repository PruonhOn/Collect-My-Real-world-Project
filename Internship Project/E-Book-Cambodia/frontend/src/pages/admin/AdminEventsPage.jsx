import { useState } from 'react'
import { Link } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import {
  adminGetEvents,
  adminGetEvent,
  adminUpdateEvent,
  adminDeleteEvent,
  adminGetEventRegistrations,
  adminDeleteEventRegistration,
  eventCoverUrl,
} from '../../api'
import { useLanguage } from '../../context/LanguageContext'

const formatDateTime = (iso) => {
  if (!iso) return '—'
  return new Date(iso).toLocaleString(undefined, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const STATUS_STYLES = {
  draft: 'bg-gray-100 text-gray-600',
  published: 'bg-emerald-100 text-emerald-700',
  cancelled: 'bg-red-100 text-red-700',
  completed: 'bg-indigo-100 text-indigo-700',
}

function StatusBadge({ event }) {
  const { t } = useLanguage()
  return (
    <span className={`inline-block px-2.5 py-1 rounded-full text-xs font-medium ${STATUS_STYLES[event.status] || STATUS_STYLES.draft}`}>
      {t(event.status) || event.status}
    </span>
  )
}

function FeaturedBadge({ event }) {
  const { t } = useLanguage()
  return event.is_featured ? (
    <span className="inline-block px-2.5 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-700">
      ⭐ {t('featured')}
    </span>
  ) : null
}

export default function AdminEventsPage() {
  const { t } = useLanguage()
  const queryClient = useQueryClient()
  const [search, setSearch] = useState('')
  const [status, setStatus] = useState('')
  const [featuredOnly, setFeaturedOnly] = useState(false)
  const [page, setPage] = useState(1)
  const [viewId, setViewId] = useState(null)
  const [participantsId, setParticipantsId] = useState(null)
  const [error, setError] = useState('')

  const { data, isLoading } = useQuery({
    queryKey: ['admin-events', { search, status, featuredOnly, page }],
    refetchInterval: 10000,
    queryFn: () =>
      adminGetEvents({
        search: search || undefined,
        status: status || undefined,
        featured: featuredOnly ? 1 : undefined,
        page,
      }),
    placeholderData: (prev) => prev,
  })

  const events = data?.data?.events?.data || []
  const paginator = data?.data?.events

  const { data: viewData, isLoading: viewLoading } = useQuery({
    queryKey: ['admin-event', viewId],
    refetchInterval: 10000,
    queryFn: () => adminGetEvent(viewId),
    enabled: !!viewId,
  })

  const { data: participantsData, isLoading: participantsLoading } = useQuery({
    queryKey: ['admin-event-registrations', participantsId],
    refetchInterval: 10000,
    queryFn: () => adminGetEventRegistrations(participantsId),
    enabled: !!participantsId,
  })

  const viewEvent = viewData?.data?.event
  const registrations = participantsData?.data?.registrations?.data || []

  const formatErrors = (err) => {
    const errors = err.response?.data?.errors
    return errors ? Object.values(errors).flat().join(', ') : err.response?.data?.message
  }

  const invalidate = () => {
    queryClient.invalidateQueries({ queryKey: ['admin-events'] })
    queryClient.invalidateQueries({ queryKey: ['events-all'] })
  }

  const updateMutation = useMutation({
    mutationFn: ({ id, data }) => adminUpdateEvent(id, data),
    onSuccess: invalidate,
    onError: (err) => setError(formatErrors(err)),
  })

  const deleteMutation = useMutation({
    mutationFn: (id) => adminDeleteEvent(id),
    onSuccess: invalidate,
  })

  const registrationRemoveMutation = useMutation({
    mutationFn: (registrationId) => adminDeleteEventRegistration(participantsId, registrationId),
    onSuccess: () =>
      queryClient.invalidateQueries({ queryKey: ['admin-event-registrations', participantsId] }),
  })

  const handleDelete = (event) => {
    if (window.confirm(`${t('confirmDelete')} "${event.title}"?`)) {
      deleteMutation.mutate(event.id)
    }
  }

  const handleRemoveRegistration = (registrationId, name) => {
    if (window.confirm(`${t('confirmDelete')} ${name}?`)) {
      registrationRemoveMutation.mutate(registrationId)
    }
  }

  const inputClass =
    'w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500'

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div className="flex items-center justify-between mb-6">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">{t('manageEvents')}</h1>
          <p className="text-gray-500 text-sm mt-1">
            {paginator?.total ?? 0} {t('events')}
          </p>
        </div>
        <Link
          to="/admin/events/new"
          className="px-4 py-2 bg-indigo-600 text-white rounded-xl font-medium hover:bg-indigo-700"
        >
          + {t('addEvent')}
        </Link>
      </div>

      {error && (
        <div className="mb-4 p-3 bg-red-50 text-red-600 rounded-xl text-sm">{error}</div>
      )}

      <div className="grid md:grid-cols-4 gap-4 mb-6">
        <input
          type="text"
          value={search}
          onChange={(e) => {
            setSearch(e.target.value)
            setPage(1)
          }}
          placeholder={t('search')}
          className={`${inputClass} md:col-span-2`}
        />
        <select
          value={status}
          onChange={(e) => {
            setStatus(e.target.value)
            setPage(1)
          }}
          className={inputClass}
        >
          <option value="">{t('filterAll')}</option>
          <option value="draft">{t('draft')}</option>
          <option value="published">{t('published')}</option>
          <option value="cancelled">{t('cancelled')}</option>
          <option value="completed">{t('completed')}</option>
        </select>
        <label className={`flex items-center gap-2 text-sm font-medium text-gray-700 ${inputClass} bg-white cursor-pointer`}>
          <input
            type="checkbox"
            checked={featuredOnly}
            onChange={(e) => {
              setFeaturedOnly(e.target.checked)
              setPage(1)
            }}
            className="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
          />
          ⭐ {t('featuredEvent')}
        </label>
      </div>

      <div className="bg-white rounded-2xl shadow-sm overflow-hidden">
        {isLoading ? (
          <div className="p-8 text-center text-gray-500">{t('loading')}</div>
        ) : events.length === 0 ? (
          <div className="p-8 text-center text-gray-500">{t('noEvents')}</div>
        ) : (
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('eventTitle')}</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('eventCategory')}</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('eventStartDate')}</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('eventLocation')}</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('status')}</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('participants')}</th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">{t('actions')}</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-200">
                {events.map((event) => {
                  const cover = eventCoverUrl(event)
                  return (
                    <tr key={event.id} className="hover:bg-gray-50">
                      <td className="px-6 py-4 text-sm text-gray-500">{event.id}</td>
                      <td className="px-6 py-4">
                        <div className="flex items-center gap-3">
                          {cover ? (
                            <img src={cover} alt="" className="w-12 h-9 object-cover rounded-lg bg-gray-100 shrink-0" />
                          ) : (
                            <div className="w-12 h-9 rounded-lg bg-sky-50 flex items-center justify-center text-sm shrink-0">📅</div>
                          )}
                          <div className="min-w-0">
                            <p className="text-sm font-medium text-gray-900">{event.title}</p>
                            <div className="flex items-center gap-2 mt-0.5">
                              <FeaturedBadge event={event} />
                            </div>
                          </div>
                        </div>
                      </td>
                      <td className="px-6 py-4 text-sm text-gray-600">{event.category || '—'}</td>
                      <td className="px-6 py-4 text-sm text-gray-600">{formatDateTime(event.start_date)}</td>
                      <td className="px-6 py-4 text-sm text-gray-600">{event.location || '—'}</td>
                      <td className="px-6 py-4"><StatusBadge event={event} /></td>
                      <td className="px-6 py-4 text-sm text-gray-600">{event.registrations_count ?? 0}</td>
                      <td className="px-6 py-4">
                        <div className="flex items-center justify-end gap-2 flex-wrap max-w-md ml-auto">
                          <button onClick={() => setViewId(event.id)} className="text-sm text-gray-500 hover:text-gray-800">
                            {t('viewDetails')}
                          </button>
                          <Link to={`/admin/events/${event.id}/edit`} className="text-sm text-indigo-600 hover:text-indigo-800">
                            {t('edit')}
                          </Link>
                          {event.status !== 'published' && (
                            <button
                              onClick={() => updateMutation.mutate({ id: event.id, data: { status: 'published' } })}
                              className="text-sm text-emerald-600 hover:text-emerald-800"
                            >
                              {t('publish')}
                            </button>
                          )}
                          {event.status === 'published' && (
                            <button
                              onClick={() => updateMutation.mutate({ id: event.id, data: { status: 'draft' } })}
                              className="text-sm text-amber-600 hover:text-amber-800"
                            >
                              {t('unpublish')}
                            </button>
                          )}
                          {event.status !== 'cancelled' && (
                            <button
                              onClick={() => updateMutation.mutate({ id: event.id, data: { status: 'cancelled' } })}
                              className="text-sm text-orange-600 hover:text-orange-800"
                            >
                              {t('cancelled')}
                            </button>
                          )}
                          {event.status !== 'completed' && (
                            <button
                              onClick={() => updateMutation.mutate({ id: event.id, data: { status: 'completed' } })}
                              className="text-sm text-indigo-600 hover:text-indigo-800"
                            >
                              {t('markCompleted')}
                            </button>
                          )}
                          <button
                            onClick={() => updateMutation.mutate({ id: event.id, data: { is_featured: !event.is_featured } })}
                            className="text-sm text-amber-600 hover:text-amber-800"
                          >
                            {event.is_featured ? t('unsetFeatured') : t('setFeatured')}
                          </button>
                          <button onClick={() => setParticipantsId(event.id)} className="text-sm text-purple-600 hover:text-purple-800">
                            {t('viewParticipants')}
                          </button>
                          <button onClick={() => handleDelete(event)} className="text-sm text-red-600 hover:text-red-800">
                            {t('delete')}
                          </button>
                        </div>
                      </td>
                    </tr>
                  )
                })}
              </tbody>
            </table>
          </div>
        )}
      </div>

      {paginator && paginator.last_page > 1 && (
        <div className="flex items-center justify-center gap-3 mt-6">
          <button
            onClick={() => setPage((p) => Math.max(1, p - 1))}
            disabled={paginator.current_page <= 1}
            className="px-4 py-2 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50 disabled:opacity-40"
          >
            {t('previous')}
          </button>
          <span className="text-sm text-gray-500">
            {paginator.current_page} / {paginator.last_page}
          </span>
          <button
            onClick={() => setPage((p) => Math.min(paginator.last_page, p + 1))}
            disabled={paginator.current_page >= paginator.last_page}
            className="px-4 py-2 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50 disabled:opacity-40"
          >
            {t('next')}
          </button>
        </div>
      )}

      {viewId && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div className="absolute inset-0 bg-black/50 backdrop-blur-sm" onClick={() => setViewId(null)} />
          <div className="relative w-full max-w-2xl max-h-[85vh] overflow-y-auto bg-white rounded-2xl shadow-2xl">
            <div className="p-6 border-b border-gray-100 sticky top-0 bg-white z-10 flex items-center justify-between">
              <div>
                <p className="text-xs font-semibold uppercase tracking-wider text-indigo-600">{t('viewDetails')}</p>
                <h2 className="text-lg font-bold text-gray-900">{viewEvent?.title}</h2>
              </div>
              <button onClick={() => setViewId(null)} className="w-9 h-9 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-500">✕</button>
            </div>

            {viewLoading || !viewEvent ? (
              <div className="p-8 text-center text-gray-500">{t('loading')}</div>
            ) : (
              <div className="p-6">
                {eventCoverUrl(viewEvent) ? (
                  <img src={eventCoverUrl(viewEvent)} alt="" className="w-full h-48 object-cover rounded-xl mb-4" />
                ) : null}
                <div className="flex flex-wrap gap-2 mb-4">
                  <StatusBadge event={viewEvent} />
                  <FeaturedBadge event={viewEvent} />
                </div>
                <p className="text-gray-700 leading-relaxed mb-4">{viewEvent.description}</p>
                <div className="grid sm:grid-cols-2 gap-3 text-sm">
                  {[
                    [t('eventCategory'), viewEvent.category],
                    [t('eventStartDate'), formatDateTime(viewEvent.start_date)],
                    [t('eventEndDate'), formatDateTime(viewEvent.end_date)],
                    [t('eventLocation'), viewEvent.location],
                    [t('eventOrganizer'), viewEvent.organizer],
                    [t('eventSpeaker'), viewEvent.speaker],
                    [t('eventFee'), viewEvent.fee_type === 'paid' ? `$${viewEvent.fee_amount}` : t('feeFree')],
                    [t('eventMaxParticipants'), viewEvent.max_participants != null ? viewEvent.max_participants : '—'],
                    [t('eventContactPhone'), viewEvent.contact_phone],
                    [t('eventContactEmail'), viewEvent.contact_email],
                  ]
                    .filter(([, value]) => value)
                    .map(([label, value]) => (
                      <div key={label} className="rounded-xl bg-gray-50 p-3">
                        <p className="text-xs text-gray-400">{label}</p>
                        <p className="font-medium text-gray-800 break-words">{value}</p>
                      </div>
                    ))}
                  {viewEvent.social_links?.length > 0 && (
                    <div className="rounded-xl bg-gray-50 p-3 sm:col-span-2">
                      <p className="text-xs text-gray-400">{t('eventSocialLinks')}</p>
                      <div className="flex flex-wrap gap-2 mt-1">
                        {viewEvent.social_links.map((link) => (
                          <a key={link} href={link} target="_blank" rel="noreferrer" className="text-sm text-indigo-600 hover:text-indigo-800 break-all">
                            {link}
                          </a>
                        ))}
                      </div>
                    </div>
                  )}
                </div>
              </div>
            )}
          </div>
        </div>
      )}

      {participantsId && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
          <div className="absolute inset-0 bg-black/50 backdrop-blur-sm" onClick={() => setParticipantsId(null)} />
          <div className="relative w-full max-w-2xl max-h-[85vh] overflow-y-auto bg-white rounded-2xl shadow-2xl">
            <div className="p-6 border-b border-gray-100 sticky top-0 bg-white z-10 flex items-center justify-between">
              <div>
                <p className="text-xs font-semibold uppercase tracking-wider text-indigo-600">{t('viewParticipants')}</p>
                <h2 className="text-lg font-bold text-gray-900">
                  {t('registeredParticipants')} ({regPaginator?.total ?? 0})
                </h2>
              </div>
              <button onClick={() => setParticipantsId(null)} className="w-9 h-9 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center text-gray-500">✕</button>
            </div>

            <div className="p-6">
              {participantsLoading ? (
                <div className="p-8 text-center text-gray-500">{t('loading')}</div>
              ) : registrations.length === 0 ? (
                <div className="p-8 text-center text-gray-400">{t('noEvents')}</div>
              ) : (
                <div className="space-y-3">
                  {registrations.map((registration) => (
                    <div key={registration.id} className="flex items-center gap-4 p-4 rounded-xl border border-gray-100">
                      {registration.user?.avatar ? (
                        <img
                          src={`/storage/${registration.user.avatar}`}
                          alt=""
                          className="w-10 h-10 rounded-full object-cover bg-gray-100 shrink-0"
                        />
                      ) : (
                        <div className="w-10 h-10 rounded-full bg-indigo-50 flex items-center justify-center text-gray-500 shrink-0">👤</div>
                      )}
                      <div className="min-w-0 flex-1">
                        <p className="text-sm font-semibold text-gray-900">{registration.user?.name || '—'}</p>
                        <p className="text-xs text-gray-500">
                          {registration.user?.email || ''}
                          <span className="text-gray-300 mx-1">·</span>
                          {new Date(registration.created_at).toLocaleDateString()}
                        </p>
                      </div>
                      <button
                        onClick={() => handleRemoveRegistration(registration.id, registration.user?.name || '')}
                        className="text-sm text-red-600 hover:text-red-800 shrink-0"
                      >
                        {t('delete')}
                      </button>
                    </div>
                  ))}
                </div>
              )}
            </div>
          </div>
        </div>
      )}
    </div>
  )
}
