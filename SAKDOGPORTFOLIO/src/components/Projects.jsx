import { useState } from 'react'
import { projectCategories, projects } from '../data'
import Icon from './Icons'
import Reveal from './Reveal'
import Section, { SectionHeading } from './Section'

function ProjectCard({ project }) {
  const cat = projectCategories.find((c) => c.id === project.category)
  return (
    <div className="group flex h-full flex-col overflow-hidden rounded-2xl border border-line bg-surface shadow-card transition-all duration-300 hover:-translate-y-2 hover:border-accent/60 hover:shadow-xl">
      <div
        className={`relative flex h-44 items-center justify-center overflow-hidden bg-gradient-to-br ${project.gradient}`}
      >
        <div
          aria-hidden="true"
          className="absolute inset-0 opacity-30"
          style={{
            backgroundImage:
              'radial-gradient(rgb(255 255 255 / 0.35) 1px, transparent 1px)',
            backgroundSize: '18px 18px',
          }}
        />
        <span className="font-display text-6xl font-bold tracking-tight text-white/90 drop-shadow-lg transition-transform duration-500 group-hover:scale-110">
          {project.initial}
        </span>
        <span className="absolute top-4 left-4 inline-flex items-center gap-1.5 rounded-full bg-black/30 px-3 py-1 text-xs font-semibold text-white backdrop-blur">
          <Icon name={project.category === 'networking' ? 'wifi' : 'database'} className="h-3.5 w-3.5" />
          {cat?.labelEn}
        </span>
        <Icon
          name="arrowUpRight"
          className="absolute right-4 bottom-4 h-5 w-5 text-white opacity-0 transition-all duration-300 group-hover:opacity-100"
        />
      </div>

      <div className="flex flex-1 flex-col p-6">
        <h3 className="font-display text-lg font-bold text-ink">{project.titleKm}</h3>
        <p className="font-mono text-xs text-accent">{project.titleEn}</p>
        <p className="mt-3 flex-1 text-sm leading-relaxed text-muted">{project.descKm}</p>
        <div className="mt-5 flex flex-wrap gap-2">
          {project.tags.map((tag) => (
            <span
              key={tag}
              className="rounded-full border border-line bg-surface-2 px-3 py-1 font-mono text-xs text-ink/80 transition-colors group-hover:border-accent/50"
            >
              {tag}
            </span>
          ))}
        </div>
      </div>
    </div>
  )
}

export default function Projects() {
  const [filter, setFilter] = useState('all')
  const filtered = filter === 'all' ? projects : projects.filter((p) => p.category === filter)

  return (
    <Section id="projects">
      <SectionHeading
        eyebrow="03 · Projects"
        title="គម្រោង"
        subtitle="A selection of systems I’ve designed and built — pick a category to explore."
      />

      <Reveal className="mb-10 flex flex-wrap justify-center gap-3">
        {projectCategories.map((cat) => (
          <button
            key={cat.id}
            type="button"
            onClick={() => setFilter(cat.id)}
            className={`rounded-full px-5 py-2.5 text-sm font-semibold transition-all duration-300 ${
              filter === cat.id
                ? 'bg-gradient-to-r from-accent to-accent-2 text-base shadow-card'
                : 'border border-line bg-surface text-muted hover:border-accent/60 hover:text-ink'
            }`}
          >
            {cat.labelKm}
            <span className="ml-1.5 font-mono text-xs opacity-80">· {cat.labelEn}</span>
          </button>
        ))}
      </Reveal>

      <div className="grid gap-6 sm:grid-cols-2">
        {filtered.map((project, i) => (
          <Reveal key={project.id} delay={i * 100}>
            <ProjectCard project={project} />
          </Reveal>
        ))}
      </div>
    </Section>
  )
}
