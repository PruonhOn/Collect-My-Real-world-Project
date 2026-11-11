/**
 * The one VIP crown in the app. Inline SVG rather than an emoji so it stays
 * crisp at the 9-12px badge sizes the nav and book cards use, and inherits
 * `currentColor` like every other icon in the project.
 */
export default function CrownIcon({ className = 'w-3 h-3' }) {
  return (
    <svg className={className} fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
      <path d="M11.562 3.266a.5.5 0 0 1 .876 0L15.39 8.87a1 1 0 0 0 1.516.294L21.183 5.5a.5.5 0 0 1 .798.519l-2.834 10.246a1 1 0 0 1-.973.835h-9.348a1 1 0 0 1-.96-.86L5.03 6.1a.5.5 0 0 1 .858-.515l4.36 4.32a1 1 0 0 0 1.516-.294z" />
      <path d="M5 21h14" />
    </svg>
  )
}
