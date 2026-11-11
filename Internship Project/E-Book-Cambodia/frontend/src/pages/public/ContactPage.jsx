import { useState } from 'react'
import { Link } from 'react-router-dom'
import { useLanguage } from '../../context/LanguageContext.jsx'
import { submitContact } from '../../api/index.js'

const CHANNELS = [
  {
    key: 'email',
    value: 'onpruonh123@gmail.com',
    href: 'mailto:onpruonh123@gmail.com',
    icon: (
      <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.8" d="M3 8l7.9 5.26a2 2 0 002.2 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
      </svg>
    ),
  },
  {
    key: 'telegram',
    value: '@PruonhOn',
    href: 'https://t.me/PruonhOn',
    icon: (
      <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
        <path d="M21.93 4.62a1.5 1.5 0 00-2.04-.68L2.86 11.53a1.5 1.5 0 00.13 2.75l4.4 1.58 1.71 5.13a1.5 1.5 0 002.44.6l2.42-2.21 4.27 3.12a1.5 1.5 0 002.37-.92l3.33-15.96zM8.98 14.9l.65-3.24 6.79-6.15-7.71 7.35 2.19 3.82-.39 4.32-1.53-6.1z" />
      </svg>
    ),
  },
  {
    key: 'facebook',
    value: 'Pi-Education Cambodia',
    href: 'https://www.facebook.com/profile.php?id=100075216953022',
    icon: (
      <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
        <path d="M22 12a10 10 0 10-11.56 9.88v-6.99H7.9V12h2.54V9.8c0-2.5 1.49-3.89 3.78-3.89 1.09 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56V12h2.78l-.44 2.89h-2.34v6.99A10 10 0 0022 12z" />
      </svg>
    ),
  },
  {
    key: 'tiktok',
    value: '@darkness29293',
    href: 'https://www.tiktok.com/@darkness29293?_r=1&_t=ZS-99ZqqOWoNka',
    icon: (
      <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
        <path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-5.2 1.74 2.89 2.89 0 012.31-4.64 2.93 2.93 0 01.88.13V9.4a6.84 6.84 0 00-1-.05A6.33 6.33 0 005 20.1a6.34 6.34 0 0010.86-4.43v-7a8.16 8.16 0 004.77 1.52v-3.4a4.85 4.85 0 01-1.04-.1z" />
      </svg>
    ),
  },
  {
    key: 'instagram',
    value: '@pi_education',
    href: 'https://www.instagram.com/pi_education',
    icon: (
      <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0 3.837a6.001 6.001 0 100 12.001 6.001 6.001 0 000-12.001zm0 1.89a4.111 4.111 0 110 8.223 4.111 4.111 0 010-8.223zm4.782-2.26a1.437 1.437 0 100 2.875 1.437 1.437 0 000-2.875z" />
      </svg>
    ),
  },
  {
    key: 'whatsapp',
    value: '0967594210',
    href: 'https://wa.me/855967594210',
    icon: (
      <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
      </svg>
    ),
  },
  {
    key: 'linkedin',
    value: 'Pi-Education Cambodia',
    href: 'https://www.linkedin.com/company/pi-education',
    icon: (
      <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
        <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.225 0z" />
      </svg>
    ),
  },
  {
    key: 'youtube',
    value: '@PiEducation',
    href: 'https://www.youtube.com/@PiEducation',
    icon: (
      <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
        <path d="M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z" />
      </svg>
    ),
  },
  {
    key: 'twitter',
    value: '@PiEducation',
    href: 'https://x.com/PiEducation',
    icon: (
      <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
        <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z" />
      </svg>
    ),
  },
]

const SUBJECT_KEYS = [
  'subjectGeneral',
  'subjectBook',
  'subjectAccount',
  'subjectPayment',
  'subjectVip',
  'subjectTechnical',
  'subjectPartnership',
]

const EMAIL_SUBJECT_MAP = {
  subjectBook: 'Book%20Support',
  subjectAccount: 'Account%20Support',
  subjectPayment: 'Payment%20Support',
  subjectVip: 'VIP%20Support',
  subjectTechnical: 'Technical%20Support',
  subjectPartnership: 'Partnership',
  subjectGeneral: 'General%20Inquiry',
}

const QUICK_SUPPORT = ['subjectBook', 'subjectPayment', 'subjectAccount', 'subjectVip']

const FAQ_KEYS = [1, 2, 3, 4, 5, 6].map((n) => `faq${n}`)

const inputClass =
  'w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition'

export default function ContactPage() {
  const { t } = useLanguage()
  const [form, setForm] = useState({ name: '', email: '', phone: '', subject: '', message: '' })
  const [errors, setErrors] = useState({})
  const [status, setStatus] = useState(null)
  const [sending, setSending] = useState(false)

  const handleChange = (e) => {
    const { name, value } = e.target
    setForm((f) => ({ ...f, [name]: value }))
    if (errors[name]) setErrors((prev) => ({ ...prev, [name]: undefined }))
  }

  const validate = () => {
    const next = {}
    if (!form.name.trim()) next.name = t('contactPage.required')
    if (!form.email.trim()) next.email = t('contactPage.required')
    else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email.trim())) next.email = t('contactPage.invalidEmail')
    if (!form.subject) next.subject = t('contactPage.required')
    if (!form.message.trim()) next.message = t('contactPage.required')
    return next
  }

  const handleSubmit = async (e) => {
    e.preventDefault()
    const next = validate()
    setErrors(next)
    if (Object.keys(next).length) return
    setSending(true)
    setStatus(null)
    try {
      await submitContact({
        name: form.name.trim(),
        email: form.email.trim(),
        phone: form.phone.trim() || null,
        subject: form.subject,
        message: form.message.trim(),
      })
      setStatus('success')
      setForm({ name: '', email: '', phone: '', subject: '', message: '' })
    } catch {
      setStatus('error')
    } finally {
      setSending(false)
    }
  }

  const subjectValue = (key) => t(`contactPage.${key}`)

  return (
    <div className="bg-gray-50 min-h-screen">
      {/* Hero */}
      <section className="bg-gradient-to-br from-indigo-600 via-indigo-700 to-purple-700 text-white">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14 sm:py-16">
          <div className="max-w-3xl">
            <span className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/15 text-white text-xs font-semibold mb-4">
              🇰🇭 Pi Education Cambodia
            </span>
            <h1 className="text-2xl sm:text-3xl lg:text-4xl font-bold mb-3">{t('contactPage.heroTitle')}</h1>
            <p className="text-indigo-100 text-sm sm:text-base whitespace-pre-line">{t('contactPage.heroSubtitle')}</p>
          </div>
        </div>
      </section>

      {/* Contact info + form */}
      <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-6 pb-4">
        <div className="grid grid-cols-1 lg:grid-cols-5 gap-6">
          {/* Info cards */}
          <div className="lg:col-span-2">
            <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 h-full">
              <h2 className="text-lg font-semibold text-gray-900">{t('contactPage.infoTitle')}</h2>
              <p className="text-sm text-gray-500 mb-5">{t('contactPage.infoSub')}</p>
              <div className="space-y-3">
                {CHANNELS.map((channel) => (
                  <a
                    key={channel.key}
                    href={channel.href}
                    target={channel.href.startsWith('mailto') ? undefined : '_blank'}
                    rel={channel.href.startsWith('mailto') ? undefined : 'noreferrer'}
                    className="flex items-center gap-3 p-3 rounded-xl border border-gray-100 hover:border-indigo-200 hover:bg-indigo-50/50 transition group"
                  >
                    <span className="shrink-0 w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center group-hover:bg-indigo-600 group-hover:text-white transition">
                      {channel.icon}
                    </span>
                    <span className="min-w-0">
                      <span className="block text-xs font-semibold text-gray-400 uppercase tracking-wide">
                        {t(`contactPage.${channel.key}Label`)}
                      </span>
                      <span className="block text-sm font-medium text-gray-800 truncate">{channel.value}</span>
                    </span>
                    <span className="ml-auto text-indigo-400">{channel.href.startsWith('mailto') ? '✉️' : '↗'}</span>
                  </a>
                ))}
              </div>
            </div>
          </div>

          {/* Form */}
          <div className="lg:col-span-3">
            <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 sm:p-8">
              <h2 className="text-lg font-semibold text-gray-900">{t('contactPage.formTitle')}</h2>
              <p className="text-sm text-gray-500 mb-6">{t('contactPage.formSub')}</p>

              {status === 'success' && (
                <div className="mb-5 p-3 bg-green-50 text-green-700 rounded-xl text-sm flex items-center justify-between gap-3">
                  <span>✓ {t('contactPage.success')}</span>
                  <button onClick={() => setStatus(null)} className="text-green-600 font-medium hover:text-green-800 text-xs shrink-0">
                    {t('contactPage.sendAnother')}
                  </button>
                </div>
              )}
              {status === 'error' && (
                <div className="mb-5 p-3 bg-red-50 text-red-600 rounded-xl text-sm">✕ {t('contactPage.error')}</div>
              )}

              <form onSubmit={handleSubmit} noValidate className="space-y-4">
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1.5">{t('contactPage.fullName')} *</label>
                    <input
                      type="text"
                      name="name"
                      value={form.name}
                      onChange={handleChange}
                      placeholder={t('contactPage.fullNamePlaceholder')}
                      className={inputClass}
                    />
                    {errors.name && <p className="mt-1 text-xs text-red-500">{errors.name}</p>}
                  </div>
                  <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1.5">{t('contactPage.email')} *</label>
                    <input
                      type="email"
                      name="email"
                      value={form.email}
                      onChange={handleChange}
                      placeholder={t('contactPage.emailPlaceholder')}
                      className={inputClass}
                    />
                    {errors.email && <p className="mt-1 text-xs text-red-500">{errors.email}</p>}
                  </div>
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1.5">
                    {t('contactPage.phone')} <span className="text-gray-400">{t('contactPage.phoneOptional')}</span>
                  </label>
                  <input
                    type="tel"
                    name="phone"
                    value={form.phone}
                    onChange={handleChange}
                    placeholder={t('contactPage.phonePlaceholder')}
                    className={inputClass}
                  />
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1.5">{t('contactPage.subject')} *</label>
                  <select name="subject" value={form.subject} onChange={handleChange} className={inputClass}>
                    <option value="">{t('contactPage.subjectPlaceholder')}</option>
                    {SUBJECT_KEYS.map((key) => (
                      <option key={key} value={key}>
                        {subjectValue(key)}
                      </option>
                    ))}
                  </select>
                  {errors.subject && <p className="mt-1 text-xs text-red-500">{errors.subject}</p>}
                </div>

                <div>
                  <label className="block text-sm font-medium text-gray-700 mb-1.5">{t('contactPage.message')} *</label>
                  <textarea
                    name="message"
                    rows="5"
                    value={form.message}
                    onChange={handleChange}
                    placeholder={t('contactPage.messagePlaceholder')}
                    className={inputClass}
                  />
                  {errors.message && <p className="mt-1 text-xs text-red-500">{errors.message}</p>}
                </div>

                <button
                  type="submit"
                  disabled={sending}
                  className="w-full py-3 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 disabled:opacity-60 transition"
                >
                  {sending ? t('contactPage.sending') : t('contactPage.submit')}
                </button>
              </form>
            </div>
          </div>
        </div>
      </section>

      {/* Quick support */}
      <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div className="text-center mb-8">
          <h2 className="text-xl sm:text-2xl font-bold text-gray-900">{t('contactPage.quickTitle')}</h2>
          <p className="text-sm sm:text-base text-gray-500 mt-1">{t('contactPage.quickSub')}</p>
        </div>
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          {QUICK_SUPPORT.map((key) => (
            <a
              key={key}
              href={`mailto:onpruonh123@gmail.com?subject=${EMAIL_SUBJECT_MAP[key]}`}
              className="group bg-white rounded-2xl border border-gray-100 shadow-sm p-5 hover:border-indigo-200 hover:shadow-md transition"
            >
              <div className="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg mb-3 group-hover:bg-indigo-600 transition">
                {key === 'subjectBook' ? '📚' : key === 'subjectPayment' ? '💳' : key === 'subjectAccount' ? '👤' : '👑'}
              </div>
              <h3 className="font-semibold text-gray-900 text-sm mb-1">{t(`contactPage.quick${key.replace('subject', '')}Title`)}</h3>
              <p className="text-xs text-gray-500 mb-3">{t(`contactPage.quick${key.replace('subject', '')}Desc`)}</p>
              <span className="inline-flex items-center gap-1 text-indigo-600 text-xs font-semibold group-hover:gap-2 transition-all">
                {t('contactPage.quickAction')}
                <span>→</span>
              </span>
            </a>
          ))}
        </div>
      </section>

      {/* FAQ */}
      <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-12">
        <div className="max-w-3xl mx-auto">
          <div className="text-center mb-8">
            <h2 className="text-xl sm:text-2xl font-bold text-gray-900">{t('contactPage.faqTitle')}</h2>
            <p className="text-sm sm:text-base text-gray-500 mt-1">{t('contactPage.faqSub')}</p>
          </div>
          <div className="space-y-3">
            {FAQ_KEYS.map((key) => (
              <details key={key} className="group bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                <summary className="flex items-center justify-between gap-3 px-5 py-4 cursor-pointer list-none">
                  <span className="font-medium text-gray-900 text-sm sm:text-base">{t(`contactPage.${key}q`)}</span>
                  <span className="shrink-0 w-6 h-6 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center transition group-open:rotate-45">
                    +
                  </span>
                </summary>
                <p className="px-5 pb-4 text-sm text-gray-600 leading-relaxed">{t(`contactPage.${key}a`)}</p>
              </details>
            ))}
          </div>
        </div>
      </section>

      {/* Trust / brand */}
      <section className="bg-white border-t border-gray-100">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
          <div className="max-w-3xl mx-auto text-center">
            <div className="flex items-center justify-center gap-2 mb-3">
              <img src="/pi-logo.jpg" alt="Pi-Education logo" className="w-12 h-12 rounded-xl object-cover" />
              <span className="font-bold text-gray-900 text-lg">{t('contactPage.brandTitle')}</span>
            </div>
            <p className="text-sm font-semibold text-indigo-600 mb-6">{t('contactPage.brandSub')}</p>
            <ul className="grid grid-cols-1 sm:grid-cols-2 gap-3 text-left">
              {[1, 2, 3, 4].map((n) => (
                <li key={n} className="flex items-center gap-2 text-sm text-gray-600">
                  <span className="text-indigo-500 shrink-0">✓</span>
                  {t(`contactPage.brandPoint${n}`)}
                </li>
              ))}
            </ul>
            <Link
              to="/books"
              className="inline-block mt-7 px-6 py-2.5 bg-indigo-600 text-white font-semibold rounded-xl hover:bg-indigo-700 transition"
            >
              {t('contactPage.brandCta')}
            </Link>
          </div>
        </div>
      </section>
    </div>
  )
}