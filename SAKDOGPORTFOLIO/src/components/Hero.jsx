import { profile, techBadges } from '../data'
import Icon from './Icons'
import Reveal from './Reveal'

export default function Hero() {
  return (
    <section id="home" className="relative overflow-hidden pt-32 pb-20 md:pt-40 md:pb-28">
      <div
        aria-hidden="true"
        className="pointer-events-none absolute -top-40 -right-32 h-96 w-96 rounded-full bg-accent/20 blur-[120px]"
      />
      <div
        aria-hidden="true"
        className="pointer-events-none absolute -bottom-40 -left-32 h-96 w-96 rounded-full bg-accent-2/15 blur-[120px]"
      />

      <div className="mx-auto grid max-w-6xl items-center gap-14 px-6 lg:grid-cols-[1.15fr_0.85fr]">
        <div>
          <Reveal>
            <p className="font-mono text-sm text-accent">
              <span className="text-accent-2">&lt;</span> Hello world
              <span className="text-accent-2">/&gt;</span> — {profile.greetingKm}
            </p>
          </Reveal>

          <Reveal delay={100}>
            <h1 className="mt-4 font-display text-5xl leading-[1.15] font-bold sm:text-6xl md:text-7xl">
              <span className="bg-gradient-to-r from-accent via-violet-300 to-accent-2 bg-clip-text text-transparent">
                {profile.name}
              </span>
              <span className="mt-2 block text-ink">Software Engineer</span>
            </h1>
          </Reveal>

          <Reveal delay={200}>
            <p className="mt-3 font-mono text-[1rem] font-semibold text-accent-2 md:text-lg">
              {profile.roleEn}
            </p>
            <p className="mt-1 text-sm font-medium text-muted">{profile.roleKm}</p>
          </Reveal>

          <Reveal delay={300}>
            <p className="mt-6 max-w-xl text-lg leading-relaxed text-muted md:text-xl">
              {profile.taglineKm}
            </p>
            <p className="mt-2 max-w-xl text-sm italic text-muted/70">{profile.taglineEn}</p>
          </Reveal>

          <Reveal delay={400}>
            <div className="mt-9 flex flex-wrap gap-4">
              <a
                href="#projects"
                className="group inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-accent to-accent-2 px-7 py-3.5 font-semibold text-base shadow-card transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg"
              >
                មើលគម្រោង
                <Icon name="arrowRight" className="h-4 w-4 transition-transform duration-300 group-hover:translate-x-1" />
              </a>
              <a
                href="#contact"
                className="inline-flex items-center gap-2 rounded-full border border-line bg-surface/60 px-7 py-3.5 font-semibold text-ink backdrop-blur transition-all duration-300 hover:-translate-y-0.5 hover:border-accent hover:text-accent"
              >
                ទាក់ទងខ្ញុំ
              </a>
            </div>
          </Reveal>

          <Reveal delay={500}>
            <div className="mt-12">
              <p className="mb-3 font-mono text-xs tracking-widest text-muted uppercase">Tech stack</p>
              <div className="flex flex-wrap gap-3">
                {techBadges.map((tech) => (
                  <span
                    key={tech.name}
                    className="inline-flex items-center gap-2 rounded-full border border-line bg-surface px-4 py-1.5 text-sm font-medium text-ink transition-all duration-300 hover:-translate-y-0.5 hover:border-accent/60"
                  >
                    <span
                      className="h-2 w-2 rounded-full"
                      style={{ backgroundColor: tech.color, boxShadow: `0 0 10px ${tech.color}` }}
                    />
                    {tech.name}
                  </span>
                ))}
              </div>
            </div>
          </Reveal>
        </div>

        <Reveal delay={300} className="relative mx-auto w-full max-w-md lg:max-w-none">
          <div className="group relative">
            <div
              aria-hidden="true"
              className="absolute -inset-1 rounded-2xl bg-gradient-to-br from-accent/40 to-accent-2/40 opacity-60 blur-lg transition-opacity duration-500 group-hover:opacity-90"
            />
            <div className="relative overflow-hidden rounded-2xl border border-line bg-surface shadow-card">
              <div className="flex items-center gap-2 border-b border-line bg-surface-2/60 px-4 py-3">
                <span className="h-3 w-3 rounded-full bg-rose-500/80" />
                <span className="h-3 w-3 rounded-full bg-amber-400/80" />
                <span className="h-3 w-3 rounded-full bg-emerald-400/80" />
                <span className="ml-3 font-mono text-xs text-muted">sak.js</span>
              </div>
              <pre className="overflow-x-auto p-5 font-mono text-sm leading-relaxed text-ink/90">
                <code>
                  <span className="text-violet-400">const</span> <span className="text-accent-2">dev</span> ={' '}
                  <span className="text-accent">{"{"}</span>
                  {'\n'}  <span className="text-amber-300">name</span>: <span className="text-emerald-300">"Sak"</span>,
                  {'\n'}  <span className="text-amber-300">role</span>: <span className="text-emerald-300">"Full-stack Developer"</span>,
                  {'\n'}  <span className="text-amber-300">stack</span>: <span className="text-accent">[</span>
                  <span className="text-emerald-300">"React"</span>, <span className="text-emerald-300">"PHP"</span>, <span className="text-emerald-300">"JavaScript"</span>
                  <span className="text-accent">]</span>,
                  {'\n'}  <span className="text-amber-300">networks</span>: <span className="text-emerald-300">"Cisco CCNA 1-3"</span>,
                  {'\n'}  <span className="text-amber-300">learning</span>: <span className="text-emerald-300">"always"</span>,
                  {'\n'}
                  <span className="text-accent">{"}"}</span>;
                </code>
              </pre>
            </div>

            <span className="absolute -top-5 -right-4 inline-flex items-center gap-2 rounded-full border border-line bg-surface-2 px-4 py-2 font-mono text-xs font-semibold text-accent-2 shadow-card">
              <span className="relative flex h-2 w-2">
                <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75" />
                <span className="relative inline-flex h-2 w-2 rounded-full bg-emerald-400" />
              </span>
              Available for work
            </span>
          </div>
        </Reveal>
      </div>
    </section>
  )
}
