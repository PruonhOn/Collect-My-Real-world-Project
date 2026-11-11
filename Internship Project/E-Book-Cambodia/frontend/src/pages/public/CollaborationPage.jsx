import { useCallback, useEffect, useRef, useState } from 'react'
import { Link } from 'react-router-dom'
import { useLanguage } from '../../context/LanguageContext'
import { submitContact } from '../../api'

const IconSchool = ({ className = 'w-6 h-6' }) => (
  <svg className={className} fill="none" stroke="currentColor" strokeWidth="1.6" viewBox="0 0 24 24" aria-hidden="true">
    <path strokeLinecap="round" strokeLinejoin="round" d="M12 3 2 8l10 5 10-5-10-5Z" />
    <path strokeLinecap="round" strokeLinejoin="round" d="M6 11v6.3c2 1.5 4 1.5 6 0s4 1.5 6 0V11" />
    <path strokeLinecap="round" strokeLinejoin="round" d="M4 21h16" />
  </svg>
)

const IconBook = ({ className = 'w-6 h-6' }) => (
  <svg className={className} fill="none" stroke="currentColor" strokeWidth="1.6" viewBox="0 0 24 24" aria-hidden="true">
    <path strokeLinecap="round" strokeLinejoin="round" d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20V2H6.5A2.5 2.5 0 0 0 4 4.5v15Z" />
    <path strokeLinecap="round" strokeLinejoin="round" d="M4 19.5A2.5 2.5 0 0 0 6.5 22H20v-5" />
    <path strokeLinecap="round" strokeLinejoin="round" d="M9 7h7" />
  </svg>
)

const IconGlobe = ({ className = 'w-6 h-6' }) => (
  <svg className={className} fill="none" stroke="currentColor" strokeWidth="1.6" viewBox="0 0 24 24" aria-hidden="true">
    <circle cx="12" cy="12" r="10" />
    <path strokeLinecap="round" strokeLinejoin="round" d="M2 12h20" />
    <path strokeLinecap="round" strokeLinejoin="round" d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10Z" />
  </svg>
)

const IconMedal = ({ className = 'w-6 h-6' }) => (
  <svg className={className} fill="none" stroke="currentColor" strokeWidth="1.6" viewBox="0 0 24 24" aria-hidden="true">
    <circle cx="12" cy="9" r="6" />
    <path strokeLinecap="round" strokeLinejoin="round" d="m8.5 14.5-1.5 7L12 19l5 2.5-1.5-7" />
  </svg>
)

const PARTNERS = [
  {
    key: 'School',
    chip: 'bg-indigo-50 text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white',
    border: 'hover:border-indigo-200',
    shadow: 'hover:shadow-indigo-200/70',
    watermark: 'text-indigo-500/10 group-hover:text-indigo-600/15',
    cta: 'text-indigo-600',
    Icon: IconSchool,
  },
  {
    key: 'Author',
    chip: 'bg-amber-50 text-amber-600 group-hover:bg-amber-500 group-hover:text-white',
    border: 'hover:border-amber-200',
    shadow: 'hover:shadow-amber-200/70',
    watermark: 'text-amber-500/10 group-hover:text-amber-600/15',
    cta: 'text-amber-600',
    Icon: IconBook,
  },
  {
    key: 'Ngo',
    chip: 'bg-rose-50 text-rose-600 group-hover:bg-rose-600 group-hover:text-white',
    border: 'hover:border-rose-200',
    shadow: 'hover:shadow-rose-200/70',
    watermark: 'text-rose-500/10 group-hover:text-rose-600/15',
    cta: 'text-rose-600',
    Icon: IconGlobe,
  },
  {
    key: 'Sponsor',
    chip: 'bg-teal-50 text-teal-600 group-hover:bg-teal-600 group-hover:text-white',
    border: 'hover:border-teal-200',
    shadow: 'hover:shadow-teal-200/70',
    watermark: 'text-teal-500/10 group-hover:text-teal-600/15',
    cta: 'text-teal-600',
    Icon: IconMedal,
  },
]

const STATS = [
  { value: 10, suffix: '+', key: 'statSchools', gradient: 'from-teal-400 to-emerald-500' },
  { value: 50, suffix: '+', key: 'statAuthors', gradient: 'from-amber-400 to-orange-500' },
  { value: 5000, separator: true, suffix: '+', key: 'statStudents', gradient: 'from-indigo-400 to-violet-500' },
]

const LOGOS = [
  { name: 'Royal University of Phnom Penh', tag: 'RU', hue: 'from-indigo-500 to-blue-600' },
  { name: 'Sunrise International School', tag: 'SI', hue: 'from-emerald-500 to-teal-600' },
  { name: 'Kphera Publishers', tag: 'KP', hue: 'from-amber-500 to-orange-600' },
  { name: 'គ្រឹះស្ថានពន្លឺអប់រំ', tag: 'ពអ', hue: 'from-rose-500 to-pink-600' },
  { name: 'Meta Learning Cambodia', tag: 'ML', hue: 'from-sky-500 to-indigo-600' },
  { name: 'អង្គការអានកម្ពុជា', tag: 'អក', hue: 'from-teal-500 to-cyan-600' },
  { name: 'City Horizon Academy', tag: 'CH', hue: 'from-violet-500 to-purple-600' },
  { name: 'Dara Books House', tag: 'DB', hue: 'from-lime-500 to-green-600' },
  { name: 'ComFos Education Network', tag: 'CN', hue: 'from-fuchsia-500 to-pink-600' },
  { name: 'Women in Tech Cambodia', tag: 'WI', hue: 'from-orange-500 to-red-600' },
]

function useInViewCountUp(target, started, duration = 1400) {
  const [value, setValue] = useState(0)
  useEffect(() => {
    if (!started) return
    let raf
    const start = performance.now()
    const tick = (now) => {
      const progress = Math.min((now - start) / duration, 1)
      const eased = 1 - Math.pow(1 - progress, 3)
      setValue(Math.round(target * eased))
      if (progress < 1) raf = requestAnimationFrame(tick)
    }
    raf = requestAnimationFrame(tick)
    return () => cancelAnimationFrame(raf)
  }, [target, started, duration])
  return value
}

function useInView(threshold = 0.3) {
  const ref = useRef(null)
  const [inView, setInView] = useState(false)
  useEffect(() => {
    const node = ref.current
    if (!node) return
    const observer = new IntersectionObserver(
      (entries) => {
        if (entries[0].isIntersecting) {
          setInView(true)
          observer.disconnect()
        }
      },
      { threshold }
    )
    observer.observe(node)
    return () => observer.disconnect()
  }, [threshold])
  return { ref, inView }
}

function StatItem({ stat }) {
  const { t } = useLanguage()
  const { ref, inView } = useInView(0.5)
  const value = useInViewCountUp(stat.value, inView)
  return (
    <div ref={ref} className="text-center">
      <span
        className={`inline-block bg-gradient-to-br ${stat.gradient} bg-clip-text text-transparent text-4xl sm:text-5xl font-black tabular-nums`}
      >
        {stat.separator ? value.toLocaleString() : value}
        {stat.suffix}
      </span>
      <p className="mt-2 text-xs sm:text-sm font-medium uppercase tracking-wider text-emerald-100/80">
        {t(`collabPage.${stat.key}`)}
      </p>
    </div>
  )
}

function PartnerModal({ partner, onClose }) {
  const { t } = useLanguage()
  const [form, setForm] = useState({ name: '', email: '', message: '' })
  const [errors, setErrors] = useState({})
  const [status, setStatus] = useState(null)
  const [sending, setSending] = useState(false)

  useEffect(() => {
    const onKey = (e) => {
      if (e.key === 'Escape') onClose()
    }
    window.addEventListener('keydown', onKey)
    document.body.style.overflow = 'hidden'
    return () => {
      window.removeEventListener('keydown', onKey)
      document.body.style.overflow = ''
    }
  }, [onClose])

  const handleChange = (e) => {
    const { name: field, value } = e.target
    setForm((f) => ({ ...f, [field]: value }))
    if (errors[field]) setErrors((prev) => ({ ...prev, [field]: undefined }))
  }

  const handleSubmit = async (e) => {
    e.preventDefault()
    const next = {}
    if (!form.name.trim()) next.name = t('collabPage.formRequired')
    if (!form.email.trim()) next.email = t('collabPage.formRequired')
    else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email.trim())) next.email = t('collabPage.formRequired')
    if (!form.message.trim()) next.message = t('collabPage.formRequired')
    setErrors(next)
    if (Object.keys(next).length) return
    setSending(true)
    setStatus(null)
    try {
      await submitContact({
        name: form.name.trim(),
        email: form.email.trim(),
        phone: null,
        subject: 'subjectPartnership',
        message: `[${t(`collabPage.partner${partner.key}Title`)}] ${form.message.trim()}`,
      })
      setStatus('success')
    } catch {
      setStatus('error')
    } finally {
      setSending(false)
    }
  }

  const { Icon } = partner

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div className="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" onClick={onClose} />
      <div
        role="dialog"
        aria-modal="true"
        aria-label={t(`collabPage.partner${partner.key}Title`)}
        className="relative w-full max-w-lg max-h-[90vh] overflow-y-auto bg-white rounded-3xl shadow-2xl"
      >
        <button
          type="button"
          onClick={onClose}
          className="absolute top-4 right-4 z-10 w-9 h-9 rounded-full bg-gray-100 hover:bg-gray-200 text-gray-500 flex items-center justify-center transition"
          aria-label={t('collabPage.modalClose')}
        >
          ✕
        </button>

        <div className="p-6 sm:p-8">
          <div className="flex items-start gap-4 mb-5 pr-10">
            <span className={`shrink-0 w-14 h-14 rounded-2xl flex items-center justify-center ${partner.chip.split(' group-hover')[0]}`}>
              <Icon className="w-7 h-7" />
            </span>
            <div>
              <p className="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-1">
                {t('collabPage.modalTitle')}
              </p>
              <h2 className="text-xl font-bold text-gray-900">{t(`collabPage.partner${partner.key}Title`)}</h2>
            </div>
          </div>

          <p className="text-sm text-gray-600 leading-relaxed mb-5">
            {t(`collabPage.partner${partner.key}Intro`)}
          </p>

          <ol className="space-y-2.5 mb-6">
            {[1, 2, 3].map((step) => (
              <li key={step} className="flex items-center gap-3 text-sm text-gray-700">
                <span className="shrink-0 w-6 h-6 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs font-bold">
                  {step}
                </span>
                {t(`collabPage.modalStep${step}`)}
              </li>
            ))}
          </ol>

          {status === 'success' ? (
            <div className="p-4 bg-green-50 border border-green-100 rounded-2xl text-sm text-green-700 flex items-start gap-3">
              <span className="text-lg shrink-0">✓</span>
              <div>
                <p className="font-semibold mb-1">{t('collabPage.formSuccess')}</p>
                <Link to="/contact" className="inline-block mt-2 font-semibold text-emerald-700 underline hover:text-emerald-900">
                  {t('collabPage.ctaAction')} →
                </Link>
              </div>
            </div>
          ) : (
            <form onSubmit={handleSubmit} noValidate className="space-y-3">
              {status === 'error' && (
                <div className="p-3 bg-red-50 text-red-600 rounded-xl text-sm">✕ {t('contactPage.error')}</div>
              )}
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">{t('collabPage.formName')} *</label>
                <input
                  type="text"
                  name="name"
                  value={form.name}
                  onChange={handleChange}
                  className="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                />
                {errors.name && <p className="mt-1 text-xs text-red-500">{errors.name}</p>}
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">{t('collabPage.formEmail')} *</label>
                <input
                  type="email"
                  name="email"
                  value={form.email}
                  onChange={handleChange}
                  placeholder="you@example.com"
                  className="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                />
                {errors.email && <p className="mt-1 text-xs text-red-500">{errors.email}</p>}
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">{t('collabPage.formMessage')} *</label>
                <textarea
                  name="message"
                  rows="4"
                  value={form.message}
                  onChange={handleChange}
                  placeholder={t('collabPage.formMessagePlaceholder')}
                  className="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                />
                {errors.message && <p className="mt-1 text-xs text-red-500">{errors.message}</p>}
              </div>
              <button
                type="submit"
                disabled={sending}
                className="w-full py-3 bg-gradient-to-r from-teal-600 to-emerald-600 text-white font-semibold rounded-xl hover:from-teal-700 hover:to-emerald-700 disabled:opacity-60 transition"
              >
                {sending ? t('collabPage.formSending') : `${t('collabPage.formSubmit')} →`}
              </button>
            </form>
          )}
        </div>
      </div>
    </div>
  )
}

export default function CollaborationPage() {
  const { t } = useLanguage()
  const [active, setActive] = useState(null)
  const closeModal = useCallback(() => setActive(null), [])

  return (
    <div>
      {/* Hero */}
      <section className="relative overflow-hidden bg-gradient-to-br from-teal-700 via-emerald-600 to-green-600 text-white">
        <div className="absolute -left-24 -top-24 w-72 h-72 bg-white/10 rounded-full blur-2xl" />
        <div className="absolute -right-24 -bottom-24 w-72 h-72 bg-white/10 rounded-full blur-2xl" />
        <div className="relative max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-16 md:py-20 text-center">
          <span className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/15 text-white text-xs font-semibold mb-4">
            🇰🇭 {t('appName')}
          </span>
          <div className="text-5xl mb-4">🤝</div>
          <h1 className="text-4xl md:text-5xl font-bold leading-tight mb-4">{t('collaboration')}</h1>
          <p className="text-lg text-emerald-100 mb-8">{t('collabPage.heroSub')}</p>
          <Link
            to="/contact"
            className="inline-flex items-center justify-center h-11 px-6 text-sm font-semibold bg-white text-emerald-700 rounded-xl shadow-sm hover:bg-emerald-50 transition-colors"
          >
            {t('collabPage.ctaAction')} →
          </Link>
        </div>
      </section>

      {/* Who we collaborate with */}
      <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14">
        <div className="text-center mb-10">
          <p className="text-xs font-semibold uppercase tracking-wider text-emerald-600 mb-2">មិត្តភាព • Partnership</p>
          <h2 className="text-2xl sm:text-3xl font-bold text-gray-900">{t('collabPage.whoTitle')}</h2>
          <p className="mt-2 text-gray-500">{t('collabPage.whoSub')}</p>
        </div>

        <div className="grid sm:grid-cols-2 gap-6">
          {PARTNERS.map((partner) => {
            const { Icon } = partner
            return (
              <button
                key={partner.key}
                type="button"
                onClick={() => setActive(partner)}
                className={`group relative overflow-hidden text-left bg-white rounded-2xl border border-gray-100 shadow-sm p-6 sm:p-8 hover:-translate-y-1.5 hover:shadow-xl transition-all duration-300 ${partner.border} ${partner.shadow}`}
              >
                <Icon className={`absolute -right-6 -bottom-6 w-32 h-32 -rotate-12 transition-transform duration-500 group-hover:scale-110 ${partner.watermark}`} />
                <div className="relative">
                  <span className={`flex items-center justify-center w-14 h-14 rounded-2xl transition-colors duration-300 ${partner.chip}`}>
                    <Icon />
                  </span>
                  <h2 className="mt-5 text-lg font-bold text-gray-900">
                    {t(`collabPage.partner${partner.key}Title`)}
                  </h2>
                  <p className="mt-2 text-sm text-gray-600 leading-relaxed">
                    {t(`collabPage.partner${partner.key}Desc`)}
                  </p>
                  <span className={`mt-5 inline-flex items-center gap-2 text-sm font-semibold ${partner.cta}`}>
                    {t(`collabPage.partner${partner.key}Cta`)}
                    <span className="transition-transform duration-300 group-hover:translate-x-1">→</span>
                  </span>
                </div>
              </button>
            )
          })}
        </div>
      </section>

      {/* Impact metrics */}
      <section className="bg-gradient-to-br from-teal-800 via-emerald-800 to-green-900">
        <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-14">
          <div className="grid grid-cols-1 sm:grid-cols-3 gap-8">
            {STATS.map((stat) => (
              <StatItem key={stat.key} stat={stat} />
            ))}
          </div>
        </div>
      </section>

      {/* Logo marquee */}
      <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14 overflow-hidden">
        <div className="text-center mb-8">
          <p className="text-xs font-semibold uppercase tracking-wider text-emerald-600 mb-2">ដៃគូរបស់យើង</p>
          <h2 className="text-2xl sm:text-3xl font-bold text-gray-900">{t('collabPage.marqueeTitle')}</h2>
        </div>

        <div className="relative">
          <div className="pointer-events-none absolute inset-y-0 left-0 w-20 bg-gradient-to-r from-white to-transparent z-10" />
          <div className="pointer-events-none absolute inset-y-0 right-0 w-20 bg-gradient-to-l from-white to-transparent z-10" />
          <div className="marquee-track flex w-max items-center gap-4 py-2">
            {[...LOGOS, ...LOGOS].map((logo, index) => (
              <div
                key={`${logo.name}-${index}`}
                className="flex shrink-0 items-center gap-3 rounded-2xl border border-gray-100 bg-white px-5 py-3 shadow-sm hover:shadow-md transition-shadow"
              >
                <span className={`flex items-center justify-center w-10 h-10 rounded-xl bg-gradient-to-br ${logo.hue} text-white text-sm font-black`}>
                  {logo.tag}
                </span>
                <span className="text-sm font-semibold text-gray-800 whitespace-nowrap">{logo.name}</span>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* CTA */}
      <section className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-16">
        <div className="relative overflow-hidden rounded-3xl bg-gradient-to-br from-teal-700 via-emerald-600 to-green-600 text-white p-8 sm:p-12 text-center">
          <div className="absolute -right-16 -top-16 w-56 h-56 bg-white/10 rounded-full blur-2xl" />
          <div className="absolute -left-16 -bottom-16 w-56 h-56 bg-white/10 rounded-full blur-2xl" />
          <h3 className="relative text-2xl sm:text-3xl font-bold mb-3">{t('collabPage.ctaTitle')}</h3>
          <Link
            to="/contact"
            className="relative inline-flex items-center justify-center h-11 px-6 mt-2 text-sm font-semibold bg-white text-emerald-700 rounded-xl shadow-sm hover:bg-emerald-50 transition-colors"
          >
            {t('collabPage.ctaAction')} →
          </Link>
        </div>
      </section>

      {active && <PartnerModal partner={active} onClose={closeModal} />}

      <style>{`
        .marquee-track {
          animation: marquee 42s linear infinite;
        }
        .marquee-track:hover {
          animation-play-state: paused;
        }
        @keyframes marquee {
          from { transform: translateX(0); }
          to { transform: translateX(-50%); }
        }
      `}</style>
    </div>
  )
}