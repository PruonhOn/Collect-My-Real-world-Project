import { experience } from '../data'
import Icon from './Icons'
import Reveal from './Reveal'
import Section, { SectionHeading } from './Section'

export default function Experience() {
  return (
    <Section id="experience">
      <SectionHeading
        eyebrow="04 · Experience"
        title="បទពិសោធន៍"
        subtitle="The journey so far — studies, certification, and real projects."
      />

      <div className="relative mx-auto max-w-3xl">
        <div
          aria-hidden="true"
          className="absolute top-2 bottom-2 left-[19px] w-px bg-gradient-to-b from-accent via-accent-2 to-transparent md:left-1/2"
        />
        <div className="space-y-10">
          {experience.map((item, i) => {
            const isLeft = i % 2 === 0
            return (
              <Reveal key={item.titleEn} delay={i * 120}>
                <div className={`relative flex md:items-center ${isLeft ? 'md:flex-row' : 'md:flex-row-reverse'}`}>
                  <div
                    className="absolute top-1 left-0 flex h-10 w-10 items-center justify-center rounded-full border border-line bg-base shadow-card md:left-1/2 md:-translate-x-1/2"
                  >
                    <span className="h-3 w-3 rounded-full bg-gradient-to-br from-accent to-accent-2" />
                  </div>

                  <div className={`pl-14 md:w-1/2 md:pl-0 ${isLeft ? 'md:pr-12' : 'md:pl-12'}`}>
                    <div className="group rounded-2xl border border-line bg-surface p-6 shadow-card transition-all duration-300 hover:-translate-y-1 hover:border-accent/60">
                      <span className="inline-block rounded-full border border-accent/40 bg-accent/10 px-3 py-1 font-mono text-xs font-semibold text-accent">
                        {item.period}
                      </span>
                      <h3 className="mt-3 font-display text-lg font-bold text-ink">{item.titleKm}</h3>
                      <p className="font-mono text-xs text-accent">{item.titleEn}</p>
                      <p className="mt-2 text-sm font-medium text-ink/80">
                        {item.orgKm} <span className="text-muted">— {item.orgEn}</span>
                      </p>
                      <ul className="mt-4 space-y-2">
                        {item.bulletsKm.map((bullet, bi) => (
                          <li key={bi} className="flex items-start gap-2.5 text-sm leading-relaxed text-muted">
                            <Icon name="check" className="mt-0.5 h-4 w-4 shrink-0 text-accent" />
                            <span>
                              {bullet}
                              <span className="mt-1 block text-xs text-muted/60">{item.bulletsEn[bi]}</span>
                            </span>
                          </li>
                        ))}
                      </ul>
                    </div>
                  </div>
                </div>
              </Reveal>
            )
          })}
        </div>
      </div>
    </Section>
  )
}
