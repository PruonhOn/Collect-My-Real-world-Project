import { useState } from 'react'
import { profile } from '../data'
import Icon from './Icons'
import Reveal from './Reveal'
import Section, { SectionHeading } from './Section'

const socials = [
  { name: 'Email', icon: 'mail', href: `mailto:${profile.email}`, handle: profile.email },
  { name: 'Telegram', icon: 'telegram', href: profile.telegram, handle: '@yourhandle' },
  { name: 'GitHub', icon: 'github', href: profile.github, handle: '@yourhandle' },
  { name: 'LinkedIn', icon: 'linkedin', href: profile.linkedin, handle: 'in/yourhandle' },
]

export default function Contact() {
  const [form, setForm] = useState({ name: '', email: '', message: '' })
  const [sent, setSent] = useState(false)

  const handleChange = (e) => setForm({ ...form, [e.target.name]: e.target.value })

  const handleSubmit = (e) => {
    e.preventDefault()
    const subject = encodeURIComponent(`Portfolio message from ${form.name}`)
    const body = encodeURIComponent(`${form.message}\n\n— ${form.name} (${form.email})`)
    window.location.href = `mailto:${profile.email}?subject=${subject}&body=${body}`
    setSent(true)
    setTimeout(() => setSent(false), 5000)
  }

  const inputClass =
    'w-full rounded-xl border border-line bg-base-2 px-4 py-3 text-sm text-ink placeholder:text-muted/50 outline-none transition-all duration-200 focus:border-accent focus:ring-2 focus:ring-accent/20'

  return (
    <Section id="contact">
      <SectionHeading
        eyebrow="06 · Contact"
        title="ទាក់ទងខ្ញុំ"
        subtitle="Have a project in mind or just want to say hello? My inbox is always open."
      />

      <div className="grid gap-10 lg:grid-cols-[0.9fr_1.1fr]">
        <Reveal>
          <div className="flex h-full flex-col justify-between rounded-2xl border border-line bg-surface p-8 shadow-card">
            <div>
              <h3 className="font-display text-xl font-bold text-ink">ផ្ញើសារមកខ្ញុំ</h3>
              <p className="mt-3 text-sm leading-relaxed text-muted">
                ឆ្លើយតបវិញក្នុងរយៈពេល ២៤ ម៉ោង។
                <span className="block text-xs text-muted/70">
                  I usually reply within 24 hours.
                </span>
              </p>
            </div>

            <div className="mt-8 space-y-3">
              {socials.map((s) => (
                <a
                  key={s.name}
                  href={s.href}
                  target={s.href.startsWith('mailto') ? undefined : '_blank'}
                  rel="noreferrer"
                  className="group flex items-center justify-between rounded-xl border border-line bg-base-2 px-4 py-3.5 transition-all duration-300 hover:-translate-y-0.5 hover:border-accent/60"
                >
                  <span className="flex items-center gap-3">
                    <span className="flex h-10 w-10 items-center justify-center rounded-lg bg-surface-2 text-accent transition-colors duration-300 group-hover:bg-gradient-to-br group-hover:from-accent group-hover:to-accent-2 group-hover:text-base">
                      <Icon name={s.icon} className="h-5 w-5" />
                    </span>
                    <span>
                      <span className="block text-sm font-semibold text-ink">{s.name}</span>
                      <span className="block font-mono text-xs text-muted">{s.handle}</span>
                    </span>
                  </span>
                  <Icon name="arrowUpRight" className="h-4 w-4 text-muted transition-all duration-300 group-hover:translate-x-0.5 group-hover:text-accent" />
                </a>
              ))}
            </div>
          </div>
        </Reveal>

        <Reveal delay={150}>
          <form
            onSubmit={handleSubmit}
            className="rounded-2xl border border-line bg-surface p-8 shadow-card"
          >
            <div className="grid gap-5 sm:grid-cols-2">
              <div>
                <label htmlFor="name" className="mb-2 block text-sm font-semibold text-ink">
                  ឈ្មោះ <span className="text-xs font-normal text-muted">· Name</span>
                </label>
                <input
                  id="name"
                  name="name"
                  type="text"
                  required
                  value={form.name}
                  onChange={handleChange}
                  placeholder="ឈ្មោះរបស់អ្នក / Your name"
                  className={inputClass}
                />
              </div>
              <div>
                <label htmlFor="email" className="mb-2 block text-sm font-semibold text-ink">
                  អ៊ីមែល <span className="text-xs font-normal text-muted">· Email</span>
                </label>
                <input
                  id="email"
                  name="email"
                  type="email"
                  required
                  value={form.email}
                  onChange={handleChange}
                  placeholder="you@example.com"
                  className={inputClass}
                />
              </div>
            </div>

            <div className="mt-5">
              <label htmlFor="message" className="mb-2 block text-sm font-semibold text-ink">
                សារ <span className="text-xs font-normal text-muted">· Message</span>
              </label>
              <textarea
                id="message"
                name="message"
                rows="5"
                required
                value={form.message}
                onChange={handleChange}
                placeholder="ប្រាប់ខ្ញុំអំពីគម្រោងរបស់អ្នក… / Tell me about your project…"
                className={`${inputClass} resize-none`}
              />
            </div>

            <div className="mt-6 flex flex-wrap items-center gap-4">
              <button
                type="submit"
                className="group inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-accent to-accent-2 px-7 py-3.5 font-semibold text-base shadow-card transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg"
              >
                ផ្ញើសារ
                <Icon name="arrowRight" className="h-4 w-4 transition-transform duration-300 group-hover:translate-x-1" />
              </button>
              {sent && (
                <p className="inline-flex items-center gap-2 text-sm font-medium text-emerald-400">
                  <Icon name="check" className="h-4 w-4" />
                  បើកកម្មវិធីអ៊ីមែលរបស់អ្នកដើម្បីផ្ញើ!
                </p>
              )}
            </div>
          </form>
        </Reveal>
      </div>
    </Section>
  )
}
