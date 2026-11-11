import { useEffect, useState } from 'react'
import { navLinks, profile } from '../data'
import Icon from './Icons'

function useActiveSection(ids) {
  const [active, setActive] = useState(ids[0])

  useEffect(() => {
    const onScroll = () => {
      const pos = window.scrollY + 140
      let current = ids[0]
      for (const id of ids) {
        const el = document.getElementById(id)
        if (el && el.offsetTop <= pos) current = id
      }
      const last = document.getElementById(ids[ids.length - 1])
      if (last && pos + window.innerHeight >= document.body.scrollHeight) {
        current = ids[ids.length - 1]
      }
      setActive(current)
    }
    onScroll()
    window.addEventListener('scroll', onScroll, { passive: true })
    return () => window.removeEventListener('scroll', onScroll)
  }, [ids])

  return active
}

const SECTION_IDS = navLinks.map((l) => l.id)

export default function Navbar() {
  const [open, setOpen] = useState(false)
  const [scrolled, setScrolled] = useState(false)
  const active = useActiveSection(SECTION_IDS)

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 12)
    onScroll()
    window.addEventListener('scroll', onScroll, { passive: true })
    return () => window.removeEventListener('scroll', onScroll)
  }, [])

  useEffect(() => {
    document.body.style.overflow = open ? 'hidden' : ''
    return () => {
      document.body.style.overflow = ''
    }
  }, [open])

  return (
    <header
      className={`fixed inset-x-0 top-0 z-50 border-b transition-all duration-300 ${
        scrolled
          ? 'border-line bg-base/85 shadow-lg shadow-black/20 backdrop-blur-md'
          : 'border-transparent bg-transparent'
      }`}
    >
      <nav className="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
        <a href="#home" className="group flex items-center gap-3" onClick={() => setOpen(false)}>
          <span className="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-accent to-accent-2 font-display text-lg font-bold text-base shadow-card transition-transform duration-300 group-hover:scale-105">
            {profile.name.charAt(0).toUpperCase()}
          </span>
          <span className="font-display text-lg font-bold text-ink">{profile.name}</span>
        </a>

        <ul className="hidden items-center gap-1 lg:flex">
          {navLinks.map((link) => (
            <li key={link.id}>
              <a
                href={`#${link.id}`}
                className={`rounded-lg px-4 py-2 text-sm font-medium transition-colors duration-200 ${
                  active === link.id
                    ? 'text-ink'
                    : 'text-muted hover:text-ink'
                }`}
              >
                {link.labelKm}
                <span
                  className={`block h-0.5 rounded-full bg-gradient-to-r from-accent to-accent-2 transition-all duration-300 ${
                    active === link.id ? 'w-full opacity-100' : 'w-0 opacity-0'
                  }`}
                />
              </a>
            </li>
          ))}
          <li className="ml-3">
            <a
              href={`mailto:${profile.email}`}
              className="inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-accent to-accent-2 px-5 py-2 text-sm font-semibold text-base shadow-card transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg"
            >
              <Icon name="mail" className="h-4 w-4" />
              ទាក់ទង
            </a>
          </li>
        </ul>

        <button
          type="button"
          aria-label={open ? 'Close menu' : 'Open menu'}
          onClick={() => setOpen((v) => !v)}
          className="flex h-10 w-10 items-center justify-center rounded-xl border border-line text-ink transition-colors hover:bg-surface lg:hidden"
        >
          <Icon name={open ? 'x' : 'menu'} className="h-5 w-5" />
        </button>
      </nav>

      <div
        className={`overflow-hidden border-b border-line bg-base/95 backdrop-blur-md transition-all duration-300 lg:hidden ${
          open ? 'max-h-96 opacity-100' : 'max-h-0 border-transparent opacity-0'
        }`}
      >
        <ul className="space-y-1 px-6 py-4">
          {navLinks.map((link) => (
            <li key={link.id}>
              <a
                href={`#${link.id}`}
                onClick={() => setOpen(false)}
                className={`flex items-center justify-between rounded-lg px-4 py-3 text-sm font-medium transition-colors ${
                  active === link.id ? 'bg-surface text-ink' : 'text-muted hover:bg-surface hover:text-ink'
                }`}
              >
                <span>
                  {link.labelKm} <span className="text-xs text-accent">· {link.labelEn}</span>
                </span>
                <Icon name="arrowUpRight" className="h-4 w-4 text-accent" />
              </a>
            </li>
          ))}
        </ul>
      </div>
    </header>
  )
}
