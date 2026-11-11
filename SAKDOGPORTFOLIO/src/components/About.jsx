import { about } from '../data'
import Icon from './Icons'
import Reveal from './Reveal'
import Section, { SectionHeading } from './Section'

export default function About() {
  return (
    <Section id="about">
      <SectionHeading
        eyebrow="01 · About Me"
        title="អំពីខ្ញុំ"
        subtitle="A bit about who I am, where I come from, and what drives me as a developer."
      />

      <div className="grid gap-10 lg:grid-cols-2">
        <Reveal>
          <div className="rounded-2xl border border-line bg-surface p-8 shadow-card">
            <h3 className="font-display text-xl font-bold text-ink">
              {about.schoolKm}
              <span className="mt-1 block font-mono text-sm font-normal text-accent">{about.schoolEn}</span>
            </h3>
            <div className="mt-5 space-y-4 text-[15px] leading-relaxed text-muted">
              {about.bioKm.map((paragraph, i) => (
                <p key={i}>{paragraph}</p>
              ))}
              <p className="border-l-2 border-accent pl-4 text-sm italic text-ink/80">{about.bioEn}</p>
            </div>
          </div>
        </Reveal>

        <Reveal delay={150}>
          <div className="grid grid-cols-2 gap-4">
            {about.stats.map((stat) => (
              <div
                key={stat.labelEn}
                className="group rounded-2xl border border-line bg-surface p-6 text-center shadow-card transition-all duration-300 hover:-translate-y-1 hover:border-accent/60"
              >
                <p className="bg-gradient-to-r from-accent to-accent-2 bg-clip-text font-mono text-4xl font-bold text-transparent transition-transform duration-300 group-hover:scale-110">
                  {stat.value}
                </p>
                <p className="mt-2 font-display text-sm font-semibold text-ink">{stat.labelKm}</p>
                <p className="text-xs text-muted">{stat.labelEn}</p>
              </div>
            ))}
          </div>
        </Reveal>
      </div>

      <div className="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {about.features.map((feature, i) => (
          <Reveal key={feature.titleEn} delay={i * 100}>
            <div className="group h-full rounded-2xl border border-line bg-surface p-6 shadow-card transition-all duration-300 hover:-translate-y-1.5 hover:border-accent/60 hover:shadow-lg">
              <span className="inline-flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-accent/20 to-accent-2/20 text-accent transition-all duration-300 group-hover:from-accent group-hover:to-accent-2 group-hover:text-base">
                <Icon name={feature.icon} className="h-6 w-6" />
              </span>
              <h4 className="mt-5 font-display text-lg font-bold text-ink">{feature.titleKm}</h4>
              <p className="font-mono text-xs text-accent">{feature.titleEn}</p>
              <p className="mt-2 text-sm leading-relaxed text-muted">{feature.descKm}</p>
              <p className="mt-1 text-xs text-muted/60">{feature.descEn}</p>
            </div>
          </Reveal>
        ))}
      </div>
    </Section>
  )
}
