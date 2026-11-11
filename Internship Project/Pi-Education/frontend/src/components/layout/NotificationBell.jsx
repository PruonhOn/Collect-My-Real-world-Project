import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { Link } from 'react-router-dom'
import { useEffect, useRef, useState } from 'react'
import api from '../../api/client'
import { useI18n } from '../../i18n'
import { localizeNotification } from '../../utils/notifications'

export default function NotificationBell() {
  const { t, locale } = useI18n()
  const queryClient = useQueryClient()
  const [open, setOpen] = useState(false)
  const ref = useRef(null)

  useEffect(() => {
    if (!open) return
    const onPointerDown = (e) => {
      if (ref.current && !ref.current.contains(e.target)) setOpen(false)
    }
    document.addEventListener('pointerdown', onPointerDown)
    return () => document.removeEventListener('pointerdown', onPointerDown)
  }, [open])

  const count = useQuery({
    queryKey: ['notifications-unread', locale],
    queryFn: async () => {
      const res = await api.get('/notifications/unread-count')
      return res.data.data.unread_count
    },
    refetchInterval: 30000,
  })

  const list = useQuery({
    queryKey: ['notifications-bell', locale],
    queryFn: async () => {
      const res = await api.get('/notifications', { params: { per_page: 5 } })
      return res.data.data
    },
    enabled: open,
  })

  const markAll = useMutation({
    mutationFn: () => api.post('/notifications/read-all'),
    onSuccess: () => invalidate(),
  })

  const invalidate = async () => {
    await Promise.all([
      queryClient.invalidateQueries({ queryKey: ['notifications-unread'] }),
      queryClient.invalidateQueries({ queryKey: ['notifications-bell'] }),
    ])
  }

  return (
    <div className="relative" ref={ref}>
      <button
        type="button"
        onClick={() => setOpen(!open)}
        aria-label="Notifications"
        className="relative rounded-lg p-2 text-slate-700 hover:bg-slate-100"
      >
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
          <path d="M6 8a6 6 0 0112 0c0 7 3 9 3 9H3s3-2 3-9" />
          <path d="M10.3 21a1.94 1.94 0 003.4 0" />
        </svg>
        {!!count.data && (
          <span className="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1 text-xs font-bold text-white">
            {count.data > 9 ? '9+' : count.data}
          </span>
        )}
      </button>

      {open && (
        <div className="absolute right-0 z-50 mt-2 w-80 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl">
          <div className="flex items-center justify-between border-b border-slate-100 px-4 py-3">
            <h3 className="font-semibold text-slate-900">{t('notif.title')}</h3>
            {!!count.data && (
              <button
                type="button"
                onClick={() => markAll.mutate()}
                className="text-xs font-medium text-indigo-600 hover:text-indigo-700"
              >
                {t('notif.markAllRead')}
              </button>
            )}
          </div>

          <div className="max-h-96 overflow-y-auto">
            {list.isLoading && <p className="px-4 py-6 text-center text-sm text-slate-500">{t('common.loading')}</p>}
            {!list.isLoading && list.data?.notifications?.length === 0 && (
              <p className="px-4 py-8 text-center text-sm text-slate-500">{t('notif.empty')}</p>
            )}
            {list.data?.notifications?.map((notif) => {
              const text = localizeNotification(notif, t)
              return (
                <div key={notif.id} className="border-b border-slate-50 px-4 py-3 hover:bg-slate-50">
                  <div className="flex items-start justify-between gap-2">
                    <p className="text-sm font-semibold text-slate-900">{text.title}</p>
                    {!notif.read_at && <span className="mt-1 h-2 w-2 shrink-0 rounded-full bg-indigo-500" />}
                  </div>
                  <p className="mt-0.5 text-xs leading-relaxed text-slate-600">{text.message}</p>
                  <p className="mt-1 text-[11px] text-slate-400">
                    {new Date(notif.created_at).toLocaleString(locale === 'km' ? 'en-US' : locale)}
                  </p>
                </div>
              )
            })}
          </div>

          <Link
            to="/notifications"
            onClick={() => setOpen(false)}
            className="block border-t border-slate-100 bg-slate-50 px-4 py-2.5 text-center text-sm font-medium text-indigo-600 hover:bg-slate-100"
          >
            {t('notif.viewAll')}
          </Link>
        </div>
      )}
    </div>
  )
}