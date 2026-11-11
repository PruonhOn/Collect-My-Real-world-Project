import { forwardRef, useEffect, useImperativeHandle, useRef, useState } from 'react'
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { getConversations, getConversationMessages, sendMessage, avatarUrl } from '../../api'
import { useLanguage } from '../../context/LanguageContext'
import { notificationKeys } from '../../services/notifications'

const MAX_DRAFT_HEIGHT = 128

function formatTime(value) {
  if (!value) return ''
  const date = new Date(value)
  const sameDay = new Date().toDateString() === date.toDateString()
  return sameDay
    ? date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
    : date.toLocaleString()
}

const MessageInbox = forwardRef(function MessageInbox({ conversationsKey, initialConversationId = null }, ref) {
  const { t } = useLanguage()
  const queryClient = useQueryClient()
  const [activeId, setActiveId] = useState(initialConversationId)
  const [mobileView, setMobileView] = useState(initialConversationId ? 'chat' : 'list')
  const [draft, setDraft] = useState('')
  const [sendError, setSendError] = useState('')
  const threadEndRef = useRef(null)
  const draftRef = useRef(null)

  useImperativeHandle(ref, () => ({
    selectConversation: (id) => {
      setActiveId(id)
      setMobileView('chat')
    },
  }))

  const { data: convData, isLoading: listLoading, isError: listError } = useQuery({
    queryKey: [conversationsKey],
    queryFn: getConversations,
    refetchInterval: 3000,
    refetchOnWindowFocus: true,
  })

  const conversations = convData?.data?.conversations || []
  const activeIdValue = conversations.some((c) => c.id === activeId) ? activeId : (conversations[0]?.id ?? null)

  const { data: threadData, isLoading: threadLoading, error: threadError } = useQuery({
    queryKey: ['conversation-messages', activeIdValue],
    queryFn: () => getConversationMessages(activeIdValue),
    enabled: !!activeIdValue,
    refetchInterval: 3000,
    refetchOnWindowFocus: true,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['my-messages-unread'] })
      queryClient.invalidateQueries({ queryKey: ['admin-messages-unread-count'] })
      queryClient.invalidateQueries({ queryKey: notificationKeys.all })
    },
  })

  const messages = threadData?.data?.messages || []
  const activeConversation = conversations.find((c) => c.id === activeIdValue) || null

  useEffect(() => {
    if (threadEndRef.current) {
      threadEndRef.current.scrollIntoView({ block: 'nearest', behavior: 'smooth' })
    }
  }, [messages.length, activeIdValue])

  useEffect(() => {
    const el = draftRef.current
    if (!el) return
    el.style.height = 'auto'
    el.style.height = `${Math.min(el.scrollHeight, MAX_DRAFT_HEIGHT)}px`
  }, [draft, mobileView])

  const invalidateAll = () => {
    queryClient.invalidateQueries({ queryKey: [conversationsKey] })
    queryClient.invalidateQueries({ queryKey: ['conversation-messages'] })
    queryClient.invalidateQueries({ queryKey: ['my-messages-unread'] })
    queryClient.invalidateQueries({ queryKey: ['admin-messages-unread-count'] })
    queryClient.invalidateQueries({ queryKey: notificationKeys.all })
  }

  const sendMutation = useMutation({
    mutationFn: sendMessage,
    onSuccess: (res) => {
      const conv = res?.data?.conversation
      if (conv?.id) {
        setActiveId(conv.id)
        setMobileView('chat')
      }
      setDraft('')
      setSendError('')
      invalidateAll()
    },
    onError: () => setSendError(t('messageSendError')),
  })

  const handleSend = () => {
    const text = draft.trim()
    if (!text || !activeIdValue) return
    setSendError('')
    sendMutation.mutate({ conversation_id: activeIdValue, message: text })
  }

  const openConversation = (id) => {
    setActiveId(id)
    setMobileView('chat')
    setSendError('')
  }

  const listVisible = mobileView === 'list'
  const threadVisible = mobileView === 'chat'

  return (
    <div className="h-full min-h-0 grid grid-cols-1 grid-rows-[minmax(0,1fr)] lg:grid-cols-[320px_1fr] bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
      {/* Conversation list */}
      <div
        className={`min-h-0 flex-col border-b lg:border-b-0 lg:border-r border-gray-100 ${
          listVisible ? 'flex' : 'hidden lg:flex'
        }`}
      >
        <div className="shrink-0 px-4 py-3 border-b border-gray-100 text-sm font-semibold text-gray-700">
          {t('conversations')}
        </div>
        <div className="flex-1 min-h-0 overflow-y-auto">
          {listLoading ? (
            <div className="p-6 text-center text-sm text-gray-500">{t('loading')}</div>
          ) : listError ? (
            <div className="p-6 text-center text-sm text-red-500">{t('loadError')}</div>
          ) : conversations.length === 0 ? (
            <div className="p-6 text-center text-sm text-gray-500">{t('noConversations')}</div>
          ) : (
            <ul>
              {conversations.map((conversation) => (
                <li key={conversation.id}>
                  <button
                    onClick={() => openConversation(conversation.id)}
                    className={`w-full text-left px-4 py-3 flex items-start gap-3 hover:bg-gray-50 transition-colors ${
                      conversation.id === activeIdValue
                        ? 'bg-indigo-50 border-l-2 border-indigo-500'
                        : 'border-l-2 border-transparent'
                    }`}
                  >
                    {avatarUrl(conversation.other_participant) ? (
                      <img
                        src={avatarUrl(conversation.other_participant)}
                        alt=""
                        className="w-9 h-9 rounded-full object-cover shrink-0"
                      />
                    ) : (
                      <div className="w-9 h-9 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-sm shrink-0">
                        {(conversation.other_participant?.name || '?').charAt(0).toUpperCase()}
                      </div>
                    )}
                    <div className="min-w-0 flex-1">
                      <div className="flex items-center justify-between gap-2">
                        <span className="font-medium text-gray-900 text-sm truncate">
                          {conversation.other_participant?.name || t('supportTeam')}
                        </span>
                        <span className="text-[11px] text-gray-400 shrink-0">
                          {formatTime(conversation.updated_at)}
                        </span>
                      </div>
                      <div className="flex items-center justify-between gap-2 mt-0.5">
                        <span className="text-xs text-gray-500 truncate">
                          {conversation.last_message?.message || t('noMessages')}
                        </span>
                        {conversation.unread_count > 0 && (
                          <span className="shrink-0 min-w-[20px] h-5 px-1.5 rounded-full bg-indigo-600 text-white text-[11px] font-semibold flex items-center justify-center">
                            {conversation.unread_count > 9 ? '9+' : conversation.unread_count}
                          </span>
                        )}
                      </div>
                    </div>
                  </button>
                </li>
              ))}
            </ul>
          )}
        </div>
      </div>

      {/* Thread */}
      <div className={`min-h-0 flex-col ${threadVisible ? 'flex' : 'hidden lg:flex'}`}>
        <div className="shrink-0 px-3 sm:px-5 py-3 border-b border-gray-100 flex items-center gap-3">
          <button
            onClick={() => setMobileView('list')}
            className="lg:hidden shrink-0 w-8 h-8 rounded-full bg-gray-100 text-gray-600 hover:bg-gray-200 flex items-center justify-center"
            aria-label={t('back')}
          >
            <svg className="w-4 h-4" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
          </button>
          <div className="min-w-0 flex-1">
            <div className="font-semibold text-gray-900 truncate">
              {activeConversation?.other_participant?.name || t('supportTeam')}
            </div>
            {activeConversation?.other_participant?.email && (
              <div className="text-xs text-gray-500 truncate">{activeConversation.other_participant.email}</div>
            )}
          </div>
          {sendError && <span className="shrink-0 text-xs text-red-600">{sendError}</span>}
        </div>

        {!activeIdValue ? (
          <div className="flex-1 min-h-0 flex flex-col items-center justify-center gap-2 text-gray-400 p-8">
            <div className="text-3xl">💬</div>
            <p className="text-sm">{t('noConversations')}</p>
          </div>
        ) : threadLoading ? (
          <div className="flex-1 min-h-0 grid place-items-center text-sm text-gray-500">{t('loading')}</div>
        ) : threadError ? (
          <div className="flex-1 min-h-0 grid place-items-center text-sm text-red-500">{t('loadError')}</div>
        ) : messages.length === 0 ? (
          <div className="flex-1 min-h-0 grid place-items-center px-6 text-sm text-gray-500 text-center">
            {t('noMessages')}
          </div>
        ) : (
          <div className="flex-1 min-h-0 overflow-y-auto px-4 sm:px-5 py-4 space-y-3 overscroll-contain">
            {messages.map((message) => (
              <div key={message.id} className={`flex ${message.is_mine ? 'justify-end' : 'justify-start'}`}>
                <div
                  className={`max-w-[75%] rounded-2xl px-4 py-2.5 ${
                    message.is_mine ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-800'
                  }`}
                >
                  <div
                    className={`text-[11px] mb-0.5 flex items-center gap-2 ${
                      message.is_mine ? 'text-indigo-200' : 'text-gray-400'
                    }`}
                  >
                    <span className="font-medium">{message.sender?.name || '?'}</span>
                    <span>{formatTime(message.created_at)}</span>
                  </div>
                  <p className="text-sm whitespace-pre-line break-words">{message.message}</p>
                </div>
              </div>
            ))}
            <div ref={threadEndRef} />
          </div>
        )}

        {/* Compose reply */}
        <div className="shrink-0 border-t border-gray-100 p-3">
          {activeIdValue ? (
            <form
              className="flex items-end gap-2"
              onSubmit={(e) => {
                e.preventDefault()
                handleSend()
              }}
            >
              <textarea
                ref={draftRef}
                rows="1"
                value={draft}
                onChange={(e) => setDraft(e.target.value)}
                onKeyDown={(e) => {
                  if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault()
                    handleSend()
                  }
                }}
                placeholder={t('messagePlaceholder')}
                className="flex-1 resize-none overflow-y-auto px-4 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 max-h-32"
              />
              <button
                type="submit"
                disabled={sendMutation.isPending || !draft.trim()}
                className="px-5 py-2.5 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 disabled:opacity-50 shrink-0"
              >
                {sendMutation.isPending ? t('loading') : t('sendMessage')}
              </button>
            </form>
          ) : (
            <p className="py-2 text-center text-xs text-gray-400">{t('noConversations')}</p>
          )}
        </div>
      </div>
    </div>
  )
})

export default MessageInbox
