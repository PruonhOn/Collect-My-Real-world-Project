import { useEffect, useRef, useState } from 'react'
import { skillGroups } from '../data'
import Icon from './Icons'
import Reveal from './Reveal'
import Section, { SectionHeading } from './Section'

function SkillBar({ name, level, accent }) {
  const ref = useRef(null)
  const [inView, setInView] = useState(false)

  useEffect(() => {
    const el = ref.current
    if (!el) return
    const observer = new IntersectionObserver(
      ([entry]) => {
        if (entry.isIntersecting) {
          setInView(true)
          observer.disconnect()
        }
      },
      { threshold: 0.4 },
    )
    observer.observe(el)
    return () => observer.disconnect()
  }, [])

  return (
    <div ref={ref}>
      <div className="mb-2 flex items-center justify-between">
        <span className="text-sm font-medium text-ink">{name}</span>
        <span className="font-mono text-xs text-accent">{level}%</span>
      </div>
      <div className="h-2 overflow-hidden rounded-full bg-surface-2">
        <div
          className="h-full rounded-full transition-all duration-1000 ease-out"
          style={{
            width: inView ? `${level}%` : '0%',
            backgroundColor: accent,
            boxShadow: `0 0 12px ${accent}66`,
          }}
        />
      </div>
    </div>
  )
}

export default function Skills() {
  return (
    <Section id="skills">
      <SectionHeading
        eyebrow="02 · Skills"
        title="ជំនាញ"
        subtitle="The technologies and tools I work with every day — from the browser to the network closet."
      />

      <div className="grid gap-6 md:grid-cols-2">
        {skillGroups.map((group, gi) => (
          <Reveal key={group.titleEn} delay={gi * 120}>
            <div className="group h-full rounded-2xl border border-line bg-surface p-7 shadow-card transition-all duration-300 hover:-translate-y-1 hover:border-accent/60">
              <div className="mb-6 flex items-center gap-3">
                <span
                  className="flex h-11 w-11 items-center justify-center rounded-xl transition-transform duration-300 group-hover:scale-110"
                  style={{ backgroundColor: `${group.accent}1f`, color: group.accent }}
                >
                  <Icon name={group.icon} className="h-6 w-6" />
                </span>
                <div>
                  <h3 className="font-display text-lg font-bold text-ink">{group.titleKm}</h3>
                  <p className="font-mono text-xs text-muted">{group.titleEn}</p>
                </div>
              </div>
              <div className="space-y-5">
                {group.skills.map((skill) => (
                  <SkillBar
                    key={skill.name}
                    name={skill.name}
                    level={skill.level}
                    accent={group.accent}
                  />
                ))}
              </div>
            </div>
          </Reveal>
        ))}
      </div>
    </Section>
  )
}
