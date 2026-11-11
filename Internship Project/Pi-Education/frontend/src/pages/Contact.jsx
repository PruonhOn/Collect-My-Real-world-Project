import { useState } from 'react'
import { useMutation } from '@tanstack/react-query'
import api from '../api/client'
import { useI18n } from '../i18n'

const contactChannels = [
  {
    titleKey: 'contact.emailUs',
    value: 'support@pi-education.com',
    href: 'mailto:support@pi-education.com',
    icon: (
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <rect x="2" y="4" width="20" height="16" rx="2" />
        <path d="M22 7l-10 6L2 7" />
      </svg>
    ),
  },
  {
    titleKey: 'contact.partnerships',
    value: 'partners@pi-education.com',
    href: 'mailto:partners@pi-education.com',
    icon: (
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2" />
        <circle cx="9" cy="7" r="4" />
        <path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75" />
      </svg>
    ),
  },
  {
    titleKey: 'contact.responseTime',
    valueKey: 'contact.within24h',
    icon: (
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <circle cx="12" cy="12" r="10" />
        <path d="M12 6v6l4 2" />
      </svg>
    ),
  },
]

const topics = ['general', 'bug', 'suggestion', 'partnership']

const topicLabels = {
  en: { general: 'General question', bug: 'Bug report', suggestion: 'Book suggestion', partnership: 'Partnership' },
  km: { general: 'សំណួរទូទៅ', bug: 'រាយការណ៍កំហុស', suggestion: 'សំណើសៀវភៅ', partnership: 'ដៃគូភាពជាដៃគូ' },
  zh: { general: '一般问题', bug: '错误报告', suggestion: '书籍建议', partnership: '合作关系' },
  fr: { general: 'Question générale', bug: 'Signaler un bug', suggestion: 'Suggestion de livre', partnership: 'Partenariat' },
}

export default function Contact() {
  const { t, locale } = useI18n()
  const [form, setForm] = useState({
    name: '',
    email: '',
    topic: topics[0],
    message: '',
  })

  const submit = useMutation({
    mutationFn: (data) => api.post('/contact', data),
  })

  const update = (field) => (e) => setForm((f) => ({ ...f, [field]: e.target.value }))

  const handleSubmit = (e) => {
    e.preventDefault()
    submit.mutate(form, {
      onSuccess: () => {
        setForm({ name: '', email: '', topic: topics[0], message: '' })
      },
    })
  }

  return (
    <div>
      <section className="border-b border-slate-200 bg-white">
        <div className="mx-auto max-w-7xl px-4 py-16 text-center sm:px-6">
          <p className="section-eyebrow">{t('common.contact')}</p>
          <h1 className="section-title">{t('contact.title')}</h1>
          <p className="mx-auto mt-4 max-w-2xl text-lg text-slate-500">
            {t('contact.subtitle')}
          </p>
        </div>
      </section>

      <section className="mx-auto max-w-7xl px-4 py-16 sm:px-6">
        <div className="grid gap-10 lg:grid-cols-5">
          <div className="lg:col-span-2">
            <h2 className="text-xl font-bold text-slate-900">{t('contact.otherWays')}</h2>
            <p className="mt-2 text-sm text-slate-500">
              {t('contact.preferDirect')}
            </p>
            <div className="mt-6 space-y-4">
              {contactChannels.map((c) => (
                <div key={c.titleKey} className="card card-hover flex items-center gap-4 p-5">
                  <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-700 text-white shadow-lg shadow-indigo-500/20">
                    {c.icon}
                  </div>
                  <div>
                    <p className="text-xs font-bold tracking-wide text-slate-400 uppercase">{t(c.titleKey)}</p>
                    {c.href ? (
                      <a href={c.href} className="mt-0.5 block text-sm font-semibold text-indigo-600 hover:underline">
                        {c.value}
                      </a>
                    ) : (
                      <p className="mt-0.5 text-sm font-semibold text-slate-900">{t(c.valueKey)}</p>
                    )}
                  </div>
                </div>
              ))}
            </div>
          </div>

          <div className="lg:col-span-3">
            <div className="card p-8">
              <h2 className="text-xl font-bold text-slate-900">{t('contact.sendMessage')}</h2>
              <p className="mt-1 text-sm text-slate-500">
                {t('contact.formDesc')}
              </p>

              {submit.isSuccess && (
                <div className="mt-5 flex items-start gap-3 rounded-xl bg-emerald-50 p-4 ring-1 ring-emerald-200">
                  <span className="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-white">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round">
                      <path d="M20 6L9 17l-5-5" />
                    </svg>
                  </span>
                  <p className="text-sm font-medium text-emerald-800">
                    {t('contact.success')}
                  </p>
                </div>
              )}

              {submit.isError && (
                <div className="mt-5 flex items-start gap-3 rounded-xl bg-red-50 p-4 ring-1 ring-red-200">
                  <span className="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-red-600 text-white">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round">
                      <path d="M18 6L6 18M6 6l12 12" />
                    </svg>
                  </span>
                  <p className="text-sm font-medium text-red-800">
                    {submit.error?.response?.data?.message || t('common.error')}
                  </p>
                </div>
              )}

              <form onSubmit={handleSubmit} className="mt-6 space-y-5">
                <div className="grid gap-5 sm:grid-cols-2">
                  <div>
                    <label className="label">{t('contact.yourName')}</label>
                    <input
                      type="text"
                      required
                      value={form.name}
                      onChange={update('name')}
                      className="input"
                      placeholder="Sokha Chan"
                    />
                  </div>
                  <div>
                    <label className="label">{t('contact.yourEmail')}</label>
                    <input
                      type="email"
                      required
                      value={form.email}
                      onChange={update('email')}
                      className="input"
                      placeholder="you@example.com"
                    />
                  </div>
                </div>

                <div>
                  <label className="label">{t('contact.topic')}</label>
                  <select value={form.topic} onChange={update('topic')} className="select">
                    {topics.map((tp) => (
                      <option key={tp} value={tp}>
                        {topicLabels[locale]?.[tp] || topicLabels.en[tp]}
                      </option>
                    ))}
                  </select>
                </div>

                <div>
                  <label className="label">{t('contact.message')}</label>
                  <textarea
                    required
                    rows={6}
                    value={form.message}
                    onChange={update('message')}
                    className="input resize-none"
                    placeholder={t('contact.placeholder')}
                  />
                </div>

                <button
                  type="submit"
                  disabled={submit.isPending}
                  className="btn-primary w-full px-6 py-3.5"
                >
                  {submit.isPending ? t('common.loading') : t('contact.send')}
                </button>
              </form>
            </div>
          </div>
        </div>
      </section>
    </div>
  )
}
