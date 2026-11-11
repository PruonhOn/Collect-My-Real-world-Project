import { useState } from 'react'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import { adminCreateBroadcastMessage, adminUpdateBroadcastMessage } from '../../api'
import { broadcastKeys } from '../../services/broadcasts'
import { useLanguage } from '../../context/LanguageContext'

const EMPTY_BROADCAST_FORM = {
  title: '',
  body: '',
  type: 'banner',
  tone: 'info',
  link_url: '',
  link_label: '',
  is_active: true,
  starts_at: '',
  ends_at: '',
}

const inputClass =
  'w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500'

/**
 * Composer for a web page announcement, shared by the "To Web Page" admin page
 * and the "Send to Web Page" shortcut in message management, so both publish
 * exactly the same thing.
 */
export default function BroadcastMessageForm({
  editing = null,
  onDone,
  className = 'bg-white rounded-2xl shadow-sm p-6',
}) {
  const { t } = useLanguage()
  const queryClient = useQueryClient()
  const [form, setForm] = useState(() => (editing ? formFromMessage(editing) : EMPTY_BROADCAST_FORM))
  const [error, setError] = useState('')

  const onError = (err) => {
    const errors = err.response?.data?.errors
    setError(errors ? Object.values(errors).flat().join(', ') : err.response?.data?.message)
  }

  const handleSuccess = () => {
    // The public feed is what visitors render, so refresh it too; otherwise a
    // new banner stays invisible until the page is reloaded.
    queryClient.invalidateQueries({ queryKey: ['admin-broadcast-messages'] })
    queryClient.invalidateQueries({ queryKey: broadcastKeys.all })
    onDone?.()
  }

  const createMutation = useMutation({
    mutationFn: (payload) => adminCreateBroadcastMessage(payload),
    onSuccess: handleSuccess,
    onError,
  })

  const updateMutation = useMutation({
    mutationFn: ({ id, payload }) => adminUpdateBroadcastMessage(id, payload),
    onSuccess: handleSuccess,
    onError,
  })

  const handleSubmit = (event) => {
    event.preventDefault()
    setError('')
    const payload = {
      title: form.title || null,
      body: form.body,
      type: form.type,
      tone: form.tone,
      link_url: form.link_url || null,
      link_label: form.link_label || null,
      is_active: form.is_active,
      starts_at: form.starts_at || null,
      ends_at: form.ends_at || null,
    }
    if (editing) {
      updateMutation.mutate({ id: editing.id, payload })
    } else {
      createMutation.mutate(payload)
    }
  }

  const isSaving = createMutation.isPending || updateMutation.isPending

  return (
    <form onSubmit={handleSubmit} className={`space-y-4 ${className}`}>
      <h2 className="text-lg font-semibold text-gray-900">
        {editing ? t('editBroadcastMessage') : t('newBroadcastMessage')}
      </h2>
      {error && <div className="p-3 bg-red-50 text-red-600 rounded-xl text-sm">{error}</div>}

      <div className="grid gap-4 sm:grid-cols-2">
        <div className="sm:col-span-2">
          <label className="block text-sm font-medium text-gray-700 mb-1">{t('title')}</label>
          <input
            type="text"
            value={form.title}
            onChange={(e) => setForm({ ...form, title: e.target.value })}
            className={inputClass}
            placeholder={t('broadcastTitlePlaceholder')}
          />
        </div>

        <div className="sm:col-span-2">
          <label className="block text-sm font-medium text-gray-700 mb-1">{t('message')} *</label>
          <textarea
            required
            rows={3}
            value={form.body}
            onChange={(e) => setForm({ ...form, body: e.target.value })}
            className={inputClass}
            placeholder={t('broadcastBodyPlaceholder')}
          />
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">{t('displayStyle')} *</label>
          <select
            value={form.type}
            onChange={(e) => setForm({ ...form, type: e.target.value })}
            className={inputClass}
          >
            <option value="banner">{t('styleBanner')}</option>
            <option value="popup">{t('stylePopup')}</option>
          </select>
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">{t('color')}</label>
          <select
            value={form.tone}
            onChange={(e) => setForm({ ...form, tone: e.target.value })}
            className={inputClass}
          >
            <option value="info">{t('toneInfo')}</option>
            <option value="success">{t('toneSuccess')}</option>
            <option value="warning">{t('toneWarning')}</option>
            <option value="danger">{t('toneDanger')}</option>
          </select>
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">{t('linkUrl')}</label>
          <input
            type="url"
            value={form.link_url}
            onChange={(e) => setForm({ ...form, link_url: e.target.value })}
            className={inputClass}
            placeholder="https://..."
          />
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">{t('linkLabel')}</label>
          <input
            type="text"
            value={form.link_label}
            onChange={(e) => setForm({ ...form, link_label: e.target.value })}
            className={inputClass}
            placeholder={t('learnMore')}
          />
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">{t('startsAt')}</label>
          <input
            type="datetime-local"
            value={form.starts_at}
            onChange={(e) => setForm({ ...form, starts_at: e.target.value })}
            className={inputClass}
          />
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700 mb-1">{t('endsAt')}</label>
          <input
            type="datetime-local"
            value={form.ends_at}
            onChange={(e) => setForm({ ...form, ends_at: e.target.value })}
            className={inputClass}
          />
        </div>
      </div>

      <label className="flex items-center gap-2 text-sm font-medium text-gray-700">
        <input
          type="checkbox"
          checked={form.is_active}
          onChange={(e) => setForm({ ...form, is_active: e.target.checked })}
          className="w-4 h-4 rounded border-gray-300 text-indigo-600"
        />
        {t('showImmediately')}
      </label>

      <div className="flex gap-3">
        <button
          type="submit"
          disabled={isSaving}
          className="px-6 py-2.5 bg-indigo-600 text-white rounded-xl font-medium hover:bg-indigo-700 disabled:opacity-60"
        >
          {isSaving ? t('loading') : t('save')}
        </button>
        {onDone && (
          <button
            type="button"
            onClick={onDone}
            className="px-6 py-2.5 border border-gray-200 rounded-xl text-gray-600 hover:bg-gray-50"
          >
            {t('cancel')}
          </button>
        )}
      </div>
    </form>
  )
}

/** Maps an API record onto the form's string-shaped datetime inputs. */
function formFromMessage(message) {
  return {
    title: message.title || '',
    body: message.body || '',
    type: message.type || 'banner',
    tone: message.tone || 'info',
    link_url: message.link_url || '',
    link_label: message.link_label || '',
    is_active: !!message.is_active,
    starts_at: message.starts_at ? message.starts_at.slice(0, 16) : '',
    ends_at: message.ends_at ? message.ends_at.slice(0, 16) : '',
  }
}