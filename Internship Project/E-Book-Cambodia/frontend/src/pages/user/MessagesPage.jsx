import { useRef, useState } from 'react'
import { useMutation, useQueryClient } from '@tanstack/react-query'
import { sendMessage } from '../../api'
import { useLanguage } from '../../context/LanguageContext'
import { Link } from 'react-router-dom'
import MessageInbox from '../../components/messaging/MessageInbox'

const inputClass =
  'w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500'
const labelClass = 'block text-sm font-medium text-gray-700 mb-1'

const TOPICS = [
  'subjectGeneral',
  'subjectBook',
  'subjectAccount',
  'subjectPayment',
  'subjectVip',
  'subjectTechnical',
  'subjectPartnership',
]

export default function MessagesPage() {
  const { t } = useLanguage()
  const queryClient = useQueryClient()
  const inboxRef = useRef(null)
  const [composing, setComposing] = useState(false)
  const [msgForm, setMsgForm] = useState({ topic: TOPICS[0], subject: '', message: '' })
  const [msgStatus, setMsgStatus] = useState({ type: '', text: '' })

  const sendMessageMutation = useMutation({
    mutationFn: sendMessage,
    onSuccess: (res) => {
      const conv = res?.data?.conversation
      if (conv?.id) {
        inboxRef.current?.selectConversation(conv.id)
      }
      setComposing(false)
      setMsgForm({ topic: TOPICS[0], subject: '', message: '' })
      setMsgStatus({ type: 'success', text: t('messageSent') })
      queryClient.invalidateQueries({ queryKey: ['my-conversations'] })
      queryClient.invalidateQueries({ queryKey: ['my-messages-unread'] })
      setTimeout(() => setMsgStatus({ type: '', text: '' }), 3000)
    },
    onError: () => {
      setMsgStatus({ type: 'error', text: t('messageSendError') })
      setTimeout(() => setMsgStatus({ type: '', text: '' }), 4000)
    },
  })

  const handleSend = (e) => {
    e.preventDefault()
    sendMessageMutation.mutate(msgForm)
  }

  return (
    <div className="h-full min-h-0 flex flex-col">
      <div className="shrink-0 flex flex-wrap items-center justify-between gap-3 px-4 sm:px-6 lg:px-8 py-4">
        <div className="min-w-0">
          <h1 className="text-2xl font-bold text-gray-900">{t('myMessages')}</h1>
          <p className="text-sm text-gray-500 mt-1 hidden sm:block">{t('ourTeamReply')}</p>
        </div>
        <div className="flex items-center gap-3 shrink-0">
          <button
            onClick={() => {
              setMsgForm({ topic: TOPICS[0], subject: '', message: '' })
              setMsgStatus({ type: '', text: '' })
              setComposing(true)
            }}
            className="px-4 py-2 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700"
          >
            {t('newMessage')}
          </button>
          <Link to="/profile" className="text-sm font-medium text-indigo-600 hover:text-indigo-700">
            ← {t('profile')}
          </Link>
        </div>
      </div>

      <div className="flex-1 min-h-0 px-4 sm:px-6 lg:px-8 pb-4 sm:pb-6">
        <MessageInbox ref={inboxRef} conversationsKey="my-conversations" />
      </div>

      {composing && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" onClick={() => setComposing(false)}>
          <div className="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6" onClick={(e) => e.stopPropagation()}>
            <div className="flex items-center justify-between gap-3 mb-4">
              <h2 className="text-lg font-bold text-gray-900">{t('newMessage')}</h2>
              <button
                onClick={() => setComposing(false)}
                className="shrink-0 w-8 h-8 rounded-full bg-gray-100 text-gray-500 hover:bg-gray-200"
                aria-label={t('close')}
              >
                ✕
              </button>
            </div>

            {msgStatus.text && (
              <div
                className={`p-3 rounded-xl text-sm mb-4 ${
                  msgStatus.type === 'success' ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-600'
                }`}
              >
                {msgStatus.text}
              </div>
            )}

            <form onSubmit={handleSend} className="space-y-4">
              <div>
                <label className={labelClass}>{t('messageTopic')}</label>
                <select
                  value={msgForm.topic}
                  onChange={(e) => setMsgForm({ ...msgForm, topic: e.target.value })}
                  className={inputClass}
                >
                  {TOPICS.map((topic) => (
                    <option key={topic} value={topic}>
                      {t(`contactPage.${topic}`)}
                    </option>
                  ))}
                </select>
              </div>
              <div>
                <label className={labelClass}>{t('messageSubject')}</label>
                <input
                  type="text"
                  value={msgForm.subject}
                  onChange={(e) => setMsgForm({ ...msgForm, subject: e.target.value })}
                  placeholder={t('messageSubjectPlaceholder')}
                  className={inputClass}
                />
              </div>
              <div>
                <label className={labelClass}>{t('yourMessage')}</label>
                <textarea
                  rows="4"
                  value={msgForm.message}
                  onChange={(e) => setMsgForm({ ...msgForm, message: e.target.value })}
                  placeholder={t('messagePlaceholder')}
                  className={inputClass}
                  required
                />
              </div>
              <div className="flex justify-end gap-2">
                <button
                  type="button"
                  onClick={() => setComposing(false)}
                  className="px-4 py-2 rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50"
                >
                  {t('cancel')}
                </button>
                <button
                  type="submit"
                  disabled={sendMessageMutation.isPending}
                  className="px-5 py-2 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 disabled:opacity-60"
                >
                  {sendMessageMutation.isPending ? t('loading') : t('sendMessage')}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  )
}