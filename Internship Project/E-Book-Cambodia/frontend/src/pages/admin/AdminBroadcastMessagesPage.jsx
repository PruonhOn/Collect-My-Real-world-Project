import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { adminDeleteBroadcastMessage, adminGetBroadcastMessages, adminUpdateBroadcastMessage } from '../../api'
import BroadcastMessageForm from '../../components/admin/BroadcastMessageForm'
import { broadcastKeys } from '../../services/broadcasts'
import { useLanguage } from '../../context/LanguageContext'

const TONE_SWATCH = {
  info: 'bg-blue-600',
  success: 'bg-emerald-600',
  warning: 'bg-amber-500',
  danger: 'bg-rose-600',
}

export default function AdminBroadcastMessagesPage() {
  const { t } = useLanguage()
  const queryClient = useQueryClient()
  const [showForm, setShowForm] = useState(false)
  const [editing, setEditing] = useState(null)

  const { data, isLoading } = useQuery({
    queryKey: ['admin-broadcast-messages'],
    refetchInterval: 10000,
    queryFn: () => adminGetBroadcastMessages({ per_page: 50 }),
  })

  const messages = data?.data?.messages?.data || []

  const toggleActive = (message) => {
  updateMutation.mutate({ id: message.id, payload: { is_active: !message.is_active } })
}

  const updateMutation = useMutation({
    mutationFn: ({ id, payload }) => adminUpdateBroadcastMessage(id, payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-broadcast-messages'] })
      queryClient.invalidateQueries({ queryKey: broadcastKeys.all })
    },
  })

  const deleteMutation = useMutation({
    mutationFn: (id) => adminDeleteBroadcastMessage(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-broadcast-messages'] })
      queryClient.invalidateQueries({ queryKey: broadcastKeys.all })
    },
  })

  const handleDelete = (message) => {
    if (window.confirm(`${t('confirmDelete')} "${message.title || message.body.slice(0, 40)}"?`)) {
      deleteMutation.mutate(message.id)
    }
  }

  return (
    <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div className="flex items-center justify-between gap-4 mb-6">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">{t('toWebPage')}</h1>
          <p className="text-gray-500 text-sm mt-1">{t('toWebPageHint')}</p>
        </div>
        <button
          type="button"
          onClick={() => {
            setShowForm(!showForm)
            setEditing(null)
          }}
          className="px-4 py-2 bg-indigo-600 text-white rounded-xl font-medium hover:bg-indigo-700 whitespace-nowrap"
        >
          + {t('newBroadcastMessage')}
        </button>
      </div>

      {showForm && (
        <div className="mb-6">
          <BroadcastMessageForm
            editing={editing}
            onDone={() => {
              setShowForm(false)
              setEditing(null)
            }}
          />
        </div>
      )}

      <div className="bg-white rounded-2xl shadow-sm overflow-hidden">
        {isLoading ? (
          <div className="p-8 text-center text-gray-500">{t('loading')}</div>
        ) : messages.length === 0 ? (
          <div className="p-8 text-center text-gray-500">{t('noBroadcastMessages')}</div>
        ) : (
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                    {t('message')}
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                    {t('displayStyle')}
                  </th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('status')}</th>
                  <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">
                    {t('edit')}
                  </th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-200">
                {messages.map((message) => (
                  <tr key={message.id} className="hover:bg-gray-50">
                    <td className="px-6 py-4">
                      <div className="flex items-start gap-3">
                        <span
                          className={`mt-1 w-2.5 h-2.5 rounded-full shrink-0 ${TONE_SWATCH[message.tone] || TONE_SWATCH.info}`}
                        />
                        <div className="min-w-0">
                          <p className="text-sm font-medium text-gray-900 truncate max-w-md">
                            {message.title || '-'}
                          </p>
                          <p className="text-xs text-gray-500 line-clamp-2 max-w-md">{message.body}</p>
                        </div>
                      </div>
                    </td>
                    <td className="px-6 py-4 text-sm text-gray-600 whitespace-nowrap">
                      {message.type === 'popup' ? t('stylePopup') : t('styleBanner')}
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <button
                        type="button"
                        onClick={() => toggleActive(message)}
                        className={`px-2.5 py-1 rounded-full text-xs font-semibold transition-colors ${
                          message.is_active
                            ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100'
                            : 'bg-gray-100 text-gray-500 hover:bg-gray-200'
                        }`}
                      >
                        {message.is_active ? t('active') : t('inactive')}
                      </button>
                    </td>
                    <td className="px-6 py-4 text-right text-sm space-x-3 whitespace-nowrap">
                      <button
                        type="button"
                        onClick={() => {
                          setEditing(message)
                          setShowForm(true)
                        }}
                        className="text-indigo-600 hover:text-indigo-800"
                      >
                        {t('edit')}
                      </button>
                      <button
                        type="button"
                        onClick={() => handleDelete(message)}
                        className="text-red-600 hover:text-red-800"
                      >
                        {t('delete')}
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  )
}