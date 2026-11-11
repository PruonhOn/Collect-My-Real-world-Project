import Reveal from './Reveal'

export function SectionHeading({ eyebrow, title, subtitle }) {
  return (
    <Reveal className="mb-14">
      <p className="font-mono text-xs tracking-[0.25em] text-accent uppercase">
        {eyebrow}
      </p>
      <h2 className="font-display mt-3 text-3xl font-bold text-ink sm:text-4xl md:text-[2.75rem] md:leading-tight">
        {title}
      </h2>
      {subtitle ? <p className="mt-4 max-w-2xl text-[1rem] text-muted md:text-lg">{subtitle}</p> : null}
    </Reveal>
  )
}

export default function Section({ id, className = '', children }) {
  return (
    <section id={id} className={`scroll-mt-24 py-20 md:py-28 ${className}`}>
      <div className="mx-auto max-w-6xl px-6">{children}</div>
    </section>
  )
}
