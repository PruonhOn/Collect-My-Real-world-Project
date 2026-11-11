import { useEffect, useState } from 'react'
import { useParams, useNavigate } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { adminGetEvent, adminCreateEvent, adminUpdateEvent, eventCoverUrl } from '../../api'
import { useLanguage } from '../../context/LanguageContext'

const EMPTY_FORM = {
  title: '',
  description: '',
  category: '',
  start_date: '',
  end_date: '',
  location: '',
  organizer: '',
  registration_link: '',
  contact_phone: '',
  contact_email: '',
  max_participants: '',
  fee_type: 'free',
  fee_amount: '',
  speaker: '',
  social_links: '',
  status: 'draft',
  is_featured: false,
}

const toLocalInput = (iso) => {
  if (!iso) return ''
  const d = new Date(iso)
  const pad = (n) => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`
}

export default function AdminEventFormPage() {
  const { id } = useParams()
  const isEdit = Boolean(id)
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const { t } = useLanguage()

  const [form, setForm] = useState(EMPTY_FORM)
  const [coverFile, setCoverFile] = useState(null)
  const [removeCover, setRemoveCover] = useState(false)
  const [error, setError] = useState('')

  const { data, isLoading } = useQuery({
    queryKey: ['admin-event', id],
    queryFn: () => adminGetEvent(id),
    enabled: isEdit,
  })

  useEffect(() => {
    const event = data?.data?.event
    if (!event) return
    setForm({
      title: event.title || '',
      description: event.description || '',
      category: event.category || '',
      start_date: toLocalInput(event.start_date),
      end_date: toLocalInput(event.end_date),
      location: event.location || '',
      organizer: event.organizer || '',
      registration_link: event.registration_link || '',
      contact_phone: event.contact_phone || '',
      contact_email: event.contact_email || '',
      max_participants: event.max_participants != null ? String(event.max_participants) : '',
      fee_type: event.fee_type || 'free',
      fee_amount: event.fee_amount != null ? String(event.fee_amount) : '',
      speaker: event.speaker || '',
      social_links: (event.social_links || []).join('\n'),
      status: event.status || 'draft',
      is_featured: !!event.is_featured,
    })
  }, [data])

  const existingCover = isEdit && data?.data?.event ? eventCoverUrl(data.data.event) : null

  const formatErrors = (err) => {
    const errors = err.response?.data?.errors
    return errors ? Object.values(errors).flat().join(', ') : err.response?.data?.message
  }

  const mutation = useMutation({
    mutationFn: (payload) =>
      isEdit ? adminUpdateEvent(id, payload) : adminCreateEvent(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-events'] })
      queryClient.invalidateQueries({ queryKey: ['events-all'] })
      navigate('/admin/events')
    },
    onError: (err) => setError(formatErrors(err)),
  })

  const buildFormData = () => {
    const fd = new FormData()
    fd.append('title', form.title.trim())
    fd.append('description', form.description.trim())
    fd.append('start_date', form.start_date)
    fd.append('location', form.location.trim())
    fd.append('fee_type', form.fee_type)
    fd.append('status', form.status)
    fd.append('is_featured', form.is_featured ? '1' : '0')

    const optional = {
      category: form.category,
      end_date: form.end_date,
      organizer: form.organizer,
      registration_link: form.registration_link,
      contact_phone: form.contact_phone,
      contact_email: form.contact_email,
      max_participants: form.max_participants,
      speaker: form.speaker,
    }

    Object.entries(optional).forEach(([key, value]) => {
      if (value !== '' && value != null) fd.append(key, value)
    })

    if (form.fee_type === 'paid' && form.fee_amount !== '') {
      fd.append('fee_amount', form.fee_amount)
    }

    const links = form.social_links
      .split('\n')
      .map((l) => l.trim())
      .filter(Boolean)
    links.forEach((link) => fd.append('social_links[]', link))

    if (coverFile) {
      fd.append('cover_image', coverFile)
    } else if (isEdit && removeCover) {
      fd.append('remove_cover_image', '1')
    }

    return fd
  }

  const handleSubmit = (e) => {
    e.preventDefault()
    setError('')
    mutation.mutate(buildFormData())
  }

  const set = (field) => (e) => setForm({ ...form, [field]: e.target.value })

  const inputClass =
    'w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500'

  const labelClass = 'block text-sm font-medium text-gray-700 mb-1'

  if (isEdit && isLoading) {
    return (
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div className="py-16 text-center text-gray-500">{t('loading')}</div>
      </div>
    )
  }

  return (
    <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div className="flex items-center justify-between mb-6">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">
            {isEdit ? t('editEvent') : t('addEvent')}
          </h1>
          <p className="text-gray-500 text-sm mt-1">{t('manageEvents')}</p>
        </div>
        <button
          onClick={() => navigate('/admin/events')}
          className="px-4 py-2 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50"
        >
          {t('cancel')}
        </button>
      </div>

      {error && (
        <div className="mb-4 p-3 bg-red-50 text-red-600 rounded-xl text-sm">{error}</div>
      )}

      <form onSubmit={handleSubmit} className="bg-white rounded-2xl shadow-sm p-6 space-y-6">
        <div className="grid md:grid-cols-2 gap-4">
          <div>
            <label className={labelClass}>{t('eventTitle')} *</label>
            <input type="text" required value={form.title} onChange={set('title')} className={inputClass} />
          </div>
          <div>
            <label className={labelClass}>{t('eventCategory')}</label>
            <input type="text" value={form.category} onChange={set('category')} className={inputClass} />
          </div>

          <div className="md:col-span-2">
            <label className={labelClass}>{t('eventDescription')} *</label>
            <textarea rows={4} required value={form.description} onChange={set('description')} className={inputClass} />
          </div>

          <div>
            <label className={labelClass}>{t('eventStartDate')} *</label>
            <input type="datetime-local" required value={form.start_date} onChange={set('start_date')} className={inputClass} />
          </div>
          <div>
            <label className={labelClass}>{t('eventEndDate')}</label>
            <input type="datetime-local" value={form.end_date} onChange={set('end_date')} className={inputClass} />
          </div>

          <div>
            <label className={labelClass}>{t('eventLocation')} *</label>
            <input type="text" required value={form.location} onChange={set('location')} className={inputClass} />
          </div>
          <div>
            <label className={labelClass}>{t('eventOrganizer')}</label>
            <input type="text" value={form.organizer} onChange={set('organizer')} className={inputClass} />
          </div>

          <div>
            <label className={labelClass}>{t('eventSpeaker')}</label>
            <input type="text" value={form.speaker} onChange={set('speaker')} className={inputClass} />
          </div>
          <div>
            <label className={labelClass}>{t('eventRegistrationLink')}</label>
            <input type="url" value={form.registration_link} onChange={set('registration_link')} className={inputClass} />
          </div>

          <div>
            <label className={labelClass}>{t('eventContactPhone')}</label>
            <input type="text" value={form.contact_phone} onChange={set('contact_phone')} className={inputClass} />
          </div>
          <div>
            <label className={labelClass}>{t('eventContactEmail')}</label>
            <input type="email" value={form.contact_email} onChange={set('contact_email')} className={inputClass} />
          </div>

          <div>
            <label className={labelClass}>{t('eventMaxParticipants')}</label>
            <input
              type="number"
              min="1"
              value={form.max_participants}
              onChange={(e) => setForm({ ...form, max_participants: e.target.value })}
              className={inputClass}
            />
          </div>
          <div>
            <label className={labelClass}>{t('eventFee')}</label>
            <div className="flex gap-3">
              <label className="flex items-center gap-2 text-sm text-gray-700">
                <input
                  type="radio"
                  name="fee_type"
                  checked={form.fee_type === 'free'}
                  onChange={() => setForm({ ...form, fee_type: 'free' })}
                  className="w-4 h-4 text-indigo-600 focus:ring-indigo-500"
                />
                {t('feeFree')}
              </label>
              <label className="flex items-center gap-2 text-sm text-gray-700">
                <input
                  type="radio"
                  name="fee_type"
                  checked={form.fee_type === 'paid'}
                  onChange={() => setForm({ ...form, fee_type: 'paid' })}
                  className="w-4 h-4 text-indigo-600 focus:ring-indigo-500"
                />
                {t('feePaid')}
              </label>
            </div>
          </div>

          {form.fee_type === 'paid' && (
            <div>
              <label className={labelClass}>{t('feeAmount')} *</label>
              <input
                type="number"
                min="0"
                step="0.01"
                required
                value={form.fee_amount}
                onChange={(e) => setForm({ ...form, fee_amount: e.target.value })}
                className={inputClass}
              />
            </div>
          )}

          <div>
            <label className={labelClass}>{t('eventStatus')}</label>
            <select value={form.status} onChange={set('status')} className={inputClass}>
              <option value="draft">{t('draft')}</option>
              <option value="published">{t('published')}</option>
              <option value="cancelled">{t('cancelled')}</option>
              <option value="completed">{t('completed')}</option>
            </select>
          </div>
          <div>
            <label className="flex items-center gap-2 text-sm font-medium text-gray-700 mt-6">
              <input
                type="checkbox"
                checked={form.is_featured}
                onChange={(e) => setForm({ ...form, is_featured: e.target.checked })}
                className="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
              />
              {t('featuredEvent')}
            </label>
          </div>

          <div className="md:col-span-2">
            <label className={labelClass}>{t('eventSocialLinks')}</label>
            <textarea
              rows={2}
              placeholder="https://facebook.com/..."
              value={form.social_links}
              onChange={set('social_links')}
              className={inputClass}
            />
          </div>

          <div className="md:col-span-2">
            <label className={labelClass}>{t('eventCoverImage')}</label>
            {(coverFile || (existingCover && !removeCover)) ? (
              <div className="flex items-center gap-4">
                <img
                  src={coverFile ? URL.createObjectURL(coverFile) : existingCover}
                  alt=""
                  className="w-40 h-28 object-cover rounded-xl border border-gray-100"
                />
                <div className="flex flex-col gap-2">
                  <button
                    type="button"
                    onClick={() => {
                      setCoverFile(null)
                      if (existingCover) setRemoveCover(true)
                    }}
                    className="text-sm text-red-600 hover:text-red-800"
                  >
                    {t('removeCoverImage')}
                  </button>
                  <label className="text-sm font-medium text-indigo-600 hover:text-indigo-800 cursor-pointer">
                    {t('edit')}
                    <input
                      type="file"
                      accept="image/*"
                      className="hidden"
                      onChange={(e) => {
                        const file = e.target.files?.[0]
                        if (file) {
                          setCoverFile(file)
                          setRemoveCover(false)
                        }
                      }}
                    />
                  </label>
                </div>
              </div>
            ) : (
              <label className="block w-full h-36 border-2 border-dashed border-gray-300 rounded-xl flex flex-col items-center justify-center gap-2 text-gray-400 hover:border-indigo-400 hover:text-indigo-500 cursor-pointer transition-colors">
                <span className="text-2xl">🖼️</span>
                <span className="text-sm">{t('upload')}</span>
                <input
                  type="file"
                  accept="image/*"
                  className="hidden"
                  onChange={(e) => {
                    const file = e.target.files?.[0]
                    if (file) setCoverFile(file)
                  }}
                />
              </label>
            )}
          </div>
        </div>

        <div className="flex gap-3 pt-2 border-t border-gray-100">
          <button
            type="submit"
            disabled={mutation.isPending}
            className="px-6 py-2.5 bg-indigo-600 text-white rounded-xl font-medium hover:bg-indigo-700 disabled:opacity-60"
          >
            {t('save')}
          </button>
          <button
            type="button"
            onClick={() => navigate('/admin/events')}
            className="px-6 py-2.5 border border-gray-200 rounded-xl text-gray-600 hover:bg-gray-50"
          >
            {t('cancel')}
          </button>
        </div>
      </form>
    </div>
  )
}
