import { testimonials } from '../data'
import Icon from './Icons'
import Reveal from './Reveal'
import Section, { SectionHeading } from './Section'

export default function Testimonials() {
  return (
    <Section id="testimonials">
      <SectionHeading
        eyebrow="05 · Testimonials"
        title="ពាក្យសម្តី"
        subtitle="What professors, teammates, and mentors say about working with me."
      />

      <div className="grid gap-6 lg:grid-cols-3">
        {testimonials.map((t, i) => (
          <Reveal key={t.name} delay={i * 120}>
            <figure className="group relative flex h-full flex-col rounded-2xl border border-line bg-surface p-7 shadow-card transition-all duration-300 hover:-translate-y-1.5 hover:border-accent/60">
              <Icon
                name="quote"
                className="absolute top-6 right-6 h-9 w-9 text-accent/20 transition-colors duration-300 group-hover:text-accent/40"
              />
              <blockquote className="relative flex-1">
                <p className="text-[15px] leading-relaxed text-ink/90">{t.quoteKm}</p>
                <p className="mt-3 border-l-2 border-accent/50 pl-3 text-xs italic text-muted">
                  {t.quoteEn}
                </p>
              </blockquote>
              <figcaption className="mt-6 flex items-center gap-3 border-t border-line pt-5">
                <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-accent/30 to-accent-2/30 font-display text-sm font-bold text-ink">
                  {t.name
                    .split(' ')
                    .map((w) => w[0])
                    .slice(0, 2)
                    .join('')}
                </span>
                <div>
                  <p className="font-display text-sm font-bold text-ink">{t.name}</p>
                  <p className="text-xs text-accent">{t.roleKm}</p>
                  <p className="text-xs text-muted/70">{t.roleEn}</p>
                </div>
              </figcaption>
            </figure>
          </Reveal>
        ))}
      </div>
    </Section>
  )
}
