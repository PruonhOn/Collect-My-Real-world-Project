import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { useState } from 'react'
import api from '../api/client'
import { useI18n } from '../i18n'
import { localizeNotification } from '../utils/notifications'

export default function Notifications() {
  const { t, locale } = useI18n()
  const queryClient = useQueryClient()
  const [page, setPage] = useState(1)

  const { data, isLoading, isError } = useQuery({
    queryKey: ['notifications', page, locale],
    queryFn: async () => {
      const res = await api.get('/notifications', { params: { page, per_page: 10 } })
      return res.data.data
    },
  })

  const invalidate = async () => {
    await Promise.all([
      queryClient.invalidateQueries({ queryKey: ['notifications'] }),
      queryClient.invalidateQueries({ queryKey: ['notifications-unread'] }),
      queryClient.invalidateQueries({ queryKey: ['notifications-bell'] }),
    ])
  }

  const read = useMutation({
    mutationFn: (id) => api.patch(`/notifications/${id}/read`),
    onSuccess: invalidate,
  })

  const readAll = useMutation({
    mutationFn: () => api.post('/notifications/read-all'),
    onSuccess: invalidate,
  })

  return (
    <div className="page">
      <div className="mx-auto max-w-3xl">
        <div className="flex flex-wrap items-center justify-between gap-4">
          <div>
            <p className="section-eyebrow">Account</p>
            <h1 className="section-title">{t('notif.title')}</h1>
          </div>
          {!!data?.notifications?.some((n) => !n.read_at) && (
            <button type="button" onClick={() => readAll.mutate()} className="btn-primary">
              {t('notif.markAllRead')}
            </button>
          )}
        </div>

        <div className="mt-8">
          {isLoading && <p className="text-sm text-slate-500">{t('common.loading')}</p>}
          {isError && <p className="text-sm text-red-600">{t('common.error')}</p>}

          {!isLoading && data?.notifications?.length === 0 && (
            <div className="rounded-2xl border border-dashed border-slate-300 bg-white/50 py-16 text-center text-sm text-slate-500">
              {t('notif.empty')}
            </div>
          )}

          <div className="space-y-3">
            {data?.notifications?.map((notif) => {
              const text = localizeNotification(notif, t)
              return (
                <button
                  key={notif.id}
                  type="button"
                  onClick={() => {
                    if (!notif.read_at) read.mutate(notif.id)
                  }}
                  className={`block w-full rounded-2xl border bg-white p-5 text-left transition ${
                    notif.read_at
                      ? 'border-slate-200 opacity-70'
                      : 'border-indigo-200 hover:border-indigo-300 hover:bg-indigo-50/40'
                  }`}
                >
                  <div className="flex items-start justify-between gap-3">
                    <p className="flex items-center gap-2 font-bold text-slate-900">
                      {!notif.read_at && <span className="h-2 w-2 shrink-0 rounded-full bg-indigo-500" />}
                      {text.title}
                    </p>
                    <span className="shrink-0 text-xs text-slate-400">
                      {new Date(notif.created_at).toLocaleString(locale === 'km' ? 'en-US' : locale)}
                    </span>
                  </div>
                  <p className="mt-1.5 text-sm leading-relaxed text-slate-600">{text.message}</p>
                  {!notif.read_at && (
                    <span className="badge mt-3 bg-indigo-100 text-indigo-700">unread</span>
                  )}
                </button>
              )
            })}
          </div>
        </div>

        {data?.pagination?.last_page > 1 && (
          <div className="mt-8 flex items-center justify-center gap-2">
            <button
              type="button"
              disabled={page <= 1}
              onClick={() => setPage((p) => p - 1)}
              className="btn-secondary px-3 py-2"
            >
              ←
            </button>
            <span className="px-3 text-sm font-medium text-slate-600">
              {data.pagination.current_page} / {data.pagination.last_page}
            </span>
            <button
              type="button"
              disabled={page >= data.pagination.last_page}
              onClick={() => setPage((p) => p + 1)}
              className="btn-secondary px-3 py-2"
            >
              →
            </button>
          </div>
        )}
      </div>
    </div>
  )
}