import { footerNoteEn, footerNoteKm, navLinks, profile } from '../data'
import Icon from './Icons'

const socials = [
  { name: 'GitHub', icon: 'github', href: profile.github },
  { name: 'Telegram', icon: 'telegram', href: profile.telegram },
  { name: 'LinkedIn', icon: 'linkedin', href: profile.linkedin },
  { name: 'Email', icon: 'mail', href: `mailto:${profile.email}` },
]

export default function Footer() {
  const year = new Date().getFullYear()

  return (
    <footer className="border-t border-line bg-base-2/60">
      <div className="mx-auto max-w-6xl px-6 py-12">
        <div className="flex flex-col items-center gap-8 md:flex-row md:items-start md:justify-between">
          <a href="#home" className="flex items-center gap-3">
            <span className="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-accent to-accent-2 font-display text-lg font-bold text-base">
              {profile.name.charAt(0).toUpperCase()}
            </span>
            <div>
              <p className="font-display text-lg font-bold text-ink">{profile.name}</p>
              <p className="font-mono text-xs text-muted">{profile.roleEn}</p>
            </div>
          </a>

          <nav aria-label="Footer">
            <ul className="flex flex-wrap justify-center gap-x-6 gap-y-2">
              {navLinks.map((link) => (
                <li key={link.id}>
                  <a
                    href={`#${link.id}`}
                    className="text-sm text-muted transition-colors hover:text-accent"
                  >
                    {link.labelKm} <span className="font-mono text-xs text-muted/50">{link.labelEn}</span>
                  </a>
                </li>
              ))}
            </ul>
          </nav>

          <div className="flex gap-3">
            {socials.map((s) => (
              <a
                key={s.name}
                href={s.href}
                target={s.href.startsWith('mailto') ? undefined : '_blank'}
                rel="noreferrer"
                aria-label={s.name}
                className="flex h-10 w-10 items-center justify-center rounded-xl border border-line bg-surface text-muted transition-all duration-300 hover:-translate-y-0.5 hover:border-accent/60 hover:text-accent"
              >
                <Icon name={s.icon} className="h-5 w-5" />
              </a>
            ))}
          </div>
        </div>

        <div className="mt-10 flex flex-col items-center gap-2 border-t border-line pt-6 text-center sm:flex-row sm:justify-between sm:text-left">
          <p className="text-xs text-muted">
            © {year} {profile.name}. {footerNoteKm}
          </p>
          <p className="font-mono text-xs text-muted/60">{footerNoteEn} · React + Tailwind CSS</p>
        </div>
      </div>
    </footer>
  )
}
