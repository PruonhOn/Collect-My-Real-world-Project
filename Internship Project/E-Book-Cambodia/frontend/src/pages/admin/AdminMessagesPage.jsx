import { useRef, useState } from 'react'
import { useSearchParams } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { adminGetMessages, adminSendMessage, adminReplyMessage, adminDeleteMessage, adminGetUsers } from '../../api'
import MessageInbox from '../../components/messaging/MessageInbox'
import BroadcastMessageForm from '../../components/admin/BroadcastMessageForm'
import { useLanguage } from '../../context/LanguageContext'

export default function AdminMessagesPage() {
  const { t } = useLanguage()
  const queryClient = useQueryClient()
  const [searchParams] = useSearchParams()
  const inboxRef = useRef(null)
  const conversationParam = searchParams.get('conversation')
  const initialConversationId = conversationParam ? Number(conversationParam) : null
  // Lets a notification deep-link straight to the tab that holds the message.
  const tabParam = searchParams.get('tab')
  const [tab, setTab] = useState(tabParam === 'guests' ? 'guests' : 'conversations')

  // Guest contact forms table
  const [status, setStatus] = useState('')
  const [search, setSearch] = useState('')
  const [replying, setReplying] = useState(null)
  const [replyText, setReplyText] = useState('')

  // New admin message modal
  const [sendingTo, setSendingTo] = useState(false)
  const [sendForm, setSendForm] = useState({ user_id: '', subject: '', message: '' })
  const [sendError, setSendError] = useState('')

  // Broadcast modal: the same composer used by the "To Web Page" admin page
  const [broadcasting, setBroadcasting] = useState(false)

  const { data: guestsData, isLoading } = useQuery({
    queryKey: ['admin-guest-messages', status, search],
    refetchInterval: 5000,
    queryFn: () => adminGetMessages({ guests: 1, status, search, per_page: 50 }),
    enabled: tab === 'guests',
  })

  const guests = guestsData?.data?.data || []

  const { data: usersData } = useQuery({
    queryKey: ['admin-users-list'],
    refetchInterval: 5000,
    queryFn: () => adminGetUsers({ per_page: 1000 }),
    enabled: sendingTo,
  })

  const users = usersData?.data?.data || []

  const invalidateGuests = () => {
    queryClient.invalidateQueries({ queryKey: ['admin-guest-messages'] })
    queryClient.invalidateQueries({ queryKey: ['admin-dashboard'] })
  }

  const replyMutation = useMutation({
    mutationFn: ({ id, reply }) => adminReplyMessage(id, { reply }),
    onSuccess: () => {
      invalidateGuests()
      setReplying(null)
      setReplyText('')
    },
  })

  const deleteMutation = useMutation({
    mutationFn: (id) => adminDeleteMessage(id),
    onSuccess: invalidateGuests,
  })

  const sendMutation = useMutation({
    mutationFn: adminSendMessage,
    onSuccess: (res) => {
      const conv = res?.data?.conversation
      if (conv?.id) {
        inboxRef.current?.selectConversation(conv.id)
      }
      setSendingTo(false)
      setSendForm({ user_id: '', subject: '', message: '' })
      setSendError('')
      setTab('conversations')
      queryClient.invalidateQueries({ queryKey: ['admin-conversations'] })
      queryClient.invalidateQueries({ queryKey: ['conversation-messages'] })
    },
    onError: () => setSendError(t('messageSendError')),
  })

  const handleDelete = (message) => {
    if (window.confirm(`${t('confirmDelete')}?`)) {
      deleteMutation.mutate(message.id)
    }
  }

  const openReply = (message) => {
    setReplying(message)
    setReplyText(message.reply || '')
  }

  const submitReply = (e) => {
    e.preventDefault()
    if (!replyText.trim()) return
    replyMutation.mutate({ id: replying.id, reply: replyText.trim() })
  }

  const openSendModal = () => {
    setSendForm({ user_id: '', subject: '', message: '' })
    setSendError('')
    setSendingTo(true)
  }

  const submitSend = (e) => {
    e.preventDefault()
    if (!sendForm.user_id || !sendForm.subject.trim() || !sendForm.message.trim()) return
    sendMutation.mutate({
      user_id: sendForm.user_id,
      subject: sendForm.subject.trim(),
      message: sendForm.message.trim(),
    })
  }

  return (
    <div className="h-full min-h-0 max-h-full flex flex-col overflow-hidden overflow-y-hidden">
      <div className="shrink-0 flex flex-col md:flex-row md:items-center justify-between gap-4 px-4 sm:px-6 lg:px-8 py-3">
        <h1 className="text-xl font-bold text-gray-900">{t('messagesManagement')}</h1>
        <div className="flex flex-col sm:flex-row items-stretch gap-2">
          <button
            onClick={openSendModal}
            className="px-3 py-1.5 bg-indigo-600 text-white font-semibold rounded-lg hover:bg-indigo-700"
          >
            {t('sendMessageToUser')}
          </button>
          <button
            type="button"
            onClick={() => setBroadcasting(true)}
            className="px-3 py-1.5 bg-white text-indigo-600 border border-indigo-200 font-semibold rounded-lg hover:bg-indigo-50"
          >
            {t('toWebPage')}
          </button>
          <div className="inline-flex rounded-lg border border-gray-200 overflow-hidden">
            <button
              onClick={() => setTab('conversations')}
              className={`px-3 py-1.5 text-sm font-semibold ${
                tab === 'conversations' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'
              }`}
            >
              {t('conversations')}
            </button>
            <button
              onClick={() => setTab('guests')}
              className={`px-3 py-1.5 text-sm font-semibold ${
                tab === 'guests' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'
              }`}
            >
              {t('guestRequests')}
            </button>
          </div>
        </div>
      </div>

      <div className="flex-1 min-h-0 h-full px-4 sm:px-6 lg:px-8 pb-3 sm:pb-4 overflow-hidden overflow-y-hidden">
        <div className="h-full min-h-0 mx-auto max-w-7xl flex flex-col overflow-hidden overflow-y-hidden">
          {tab === 'conversations' ? (
            <MessageInbox
              key={initialConversationId ? `conversation-${initialConversationId}` : 'all'}
              ref={inboxRef}
              conversationsKey="admin-conversations"
              initialConversationId={initialConversationId}
            />
          ) : (
            <div className="flex-1 min-h-0 h-full flex flex-col bg-white rounded-2xl shadow-sm overflow-hidden">
              <div className="shrink-0 flex flex-col sm:flex-row gap-2 p-3 border-b border-gray-100">
                <input
                  type="search"
                  value={search}
                  onChange={(e) => setSearch(e.target.value)}
                  placeholder={t('search')}
                  className="flex-1 px-3 py-1.5 rounded-lg border border-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                />
                <select
                  value={status}
                  onChange={(e) => setStatus(e.target.value)}
                  className="px-3 py-1.5 rounded-lg border border-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                >
                  <option value="">All</option>
                  <option value="pending">{t('pending')}</option>
                  <option value="replied">{t('replied')}</option>
                </select>
              </div>

              <div className="flex-1 min-h-0 overflow-hidden">
                {isLoading ? (
                  <div className="p-6 text-center text-gray-500">{t('loading')}</div>
                ) : guests.length === 0 ? (
                  <div className="p-6 text-center text-gray-500">{t('noMessages')}</div>
                ) : (
                  <div className="h-full overflow-x-auto overflow-y-hidden">
                    <table className="min-w-full divide-y divide-gray-200">
                      <thead className="bg-gray-50">
                        <tr>
                          <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{t('name')}</th>
                          <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{t('messageSubject')}</th>
                          <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{t('message')}</th>
                          <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{t('status')}</th>
                          <th className="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{t('reply')}</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-gray-200">
                        {guests.map((message) => (
                          <tr key={message.id} className="hover:bg-gray-50">
                            <td className="px-4 py-2 max-w-[200px]">
                              <div className="text-sm font-medium text-gray-900 truncate">{message.sender_name}</div>
                              <div className="text-xs text-gray-500 truncate">{message.sender_email || ''}</div>
                            </td>
                            <td className="px-4 py-2 max-w-[160px]">
                              {message.topic && (
                                <span className="inline-flex items-center px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 text-xs font-semibold mb-1">
                                  {message.topic}
                                </span>
                              )}
                              <div className="text-sm font-medium text-gray-900 truncate">{message.subject || t('noSubject')}</div>
                            </td>
                            <td className="px-4 py-2 text-sm text-gray-600 max-w-xs truncate">{message.message}</td>
                            <td className="px-4 py-2">
                              <span
                                className={`inline-block text-xs font-medium px-2 py-0.5 rounded-full ${
                                  message.is_replied ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'
                                }`}
                              >
                                {message.is_replied ? t('replied') : t('pending')}
                              </span>
                            </td>
                            <td className="px-4 py-2 text-right whitespace-nowrap">
                              <button
                                onClick={() => openReply(message)}
                                className={`text-sm font-medium mr-2 ${
                                  message.is_replied ? 'text-emerald-600 hover:text-emerald-800' : 'text-indigo-600 hover:text-indigo-800'
                                }`}
                              >
                                {t('reply')}
                              </button>
                              <button onClick={() => handleDelete(message)} className="text-sm text-red-600 hover:text-red-800">
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
          )}
        </div>
      </div>

      {replying && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
          <div className="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6" onClick={(e) => e.stopPropagation()}>
            <div className="flex items-center justify-between gap-3 mb-4">
              <div className="min-w-0">
                <h2 className="text-lg font-bold text-gray-900">
                  {t('replyTo')} {replying.sender_name || '-'}
                </h2>
                <div className="text-sm text-gray-500 truncate">{replying.subject || t('noSubject')}</div>
              </div>
              <button
                onClick={() => setReplying(null)}
                className="shrink-0 w-8 h-8 rounded-full bg-gray-100 text-gray-500 hover:bg-gray-200"
                aria-label={t('close')}
              >
                ✕
              </button>
            </div>

            <div className="bg-gray-50 rounded-xl p-3 mb-4 max-h-40 overflow-y-auto">
              <p className="text-sm text-gray-700 whitespace-pre-line">{replying.message}</p>
            </div>

            {replying.is_replied && (
              <div className="bg-emerald-50 rounded-xl p-3 mb-4 max-h-40 overflow-y-auto">
                <div className="text-xs font-semibold text-emerald-700 mb-1">{t('replyFromUs')}</div>
                <p className="text-sm text-gray-700 whitespace-pre-line">{replying.reply}</p>
              </div>
            )}

            <form onSubmit={submitReply} className="space-y-3">
              <textarea
                rows="4"
                value={replyText}
                onChange={(e) => setReplyText(e.target.value)}
                placeholder={t('replyPlaceholder')}
                required
                className="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500"
              />
              <div className="flex justify-end gap-2">
                <button
                  type="button"
                  onClick={() => setReplying(null)}
                  className="px-4 py-2 rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50"
                >
                  {t('cancel')}
                </button>
                <button
                  type="submit"
                  disabled={replyMutation.isPending || !replyText.trim()}
                  className="px-5 py-2 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 disabled:opacity-60"
                >
                  {replyMutation.isPending ? t('loading') : t('sendReply')}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {sendingTo && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
          <div className="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6" onClick={(e) => e.stopPropagation()}>
            <div className="flex items-center justify-between gap-3 mb-4">
              <h2 className="text-lg font-bold text-gray-900">{t('sendMessageToUser')}</h2>
              <button
                onClick={() => setSendingTo(false)}
                className="shrink-0 w-8 h-8 rounded-full bg-gray-100 text-gray-500 hover:bg-gray-200"
                aria-label={t('close')}
              >
                ✕
              </button>
            </div>

            {sendError && <div className="p-3 rounded-xl text-sm bg-red-50 text-red-600 mb-4">{sendError}</div>}

            <form onSubmit={submitSend} className="space-y-3">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">{t('selectUser')}</label>
                <select
                  value={sendForm.user_id}
                  onChange={(e) => setSendForm({ ...sendForm, user_id: e.target.value })}
                  className="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                  required
                >
                  <option value="">{t('selectUser')}...</option>
                  {users.map((user) => (
                    <option key={user.id} value={user.id}>
                      {user.name} — {user.email}
                    </option>
                  ))}
                </select>
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">{t('messageSubject')}</label>
                <input
                  type="text"
                  value={sendForm.subject}
                  onChange={(e) => setSendForm({ ...sendForm, subject: e.target.value })}
                  placeholder={t('messageSubjectPlaceholder')}
                  className="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                  required
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">{t('yourMessage')}</label>
                <textarea
                  rows="4"
                  value={sendForm.message}
                  onChange={(e) => setSendForm({ ...sendForm, message: e.target.value })}
                  placeholder={t('messagePlaceholder')}
                  className="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                  required
                />
              </div>
              <div className="flex justify-end gap-2">
                <button
                  type="button"
                  onClick={() => setSendingTo(false)}
                  className="px-4 py-2 rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50"
                >
                  {t('cancel')}
                </button>
                <button
                  type="submit"
                  disabled={sendMutation.isPending}
                  className="px-5 py-2 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 disabled:opacity-60"
                >
                  {sendMutation.isPending ? t('loading') : t('sendMessage')}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {broadcasting && (
        <div className="fixed inset-0 z-50 flex items-start justify-center bg-black/50 p-4 overflow-y-auto">
          <div
            className="bg-white rounded-2xl shadow-2xl w-full max-w-2xl my-8"
            onClick={(e) => e.stopPropagation()}
          >
            <div className="flex items-center justify-between gap-3 px-6 pt-6">
              <h2 className="text-lg font-bold text-gray-900">{t('toWebPage')}</h2>
              <button
                type="button"
                onClick={() => setBroadcasting(false)}
                className="shrink-0 w-8 h-8 rounded-full bg-gray-100 text-gray-500 hover:bg-gray-200"
                aria-label={t('close')}
              >
                ✕
              </button>
            </div>
            <div className="p-6 pt-4">
              <BroadcastMessageForm
                className="space-y-4"
                onDone={() => setBroadcasting(false)}
                // Remount so a reopened modal starts from a blank form.
                key={String(broadcasting)}
              />
            </div>
          </div>
        </div>
      )}
    </div>
  )
}
