import { Loader2 } from 'lucide-react'

/** Full-viewport spinner used while a session is being restored. */
export function FullPageLoader({ label }: { label?: string }) {
  return (
    <div
      className="flex min-h-screen flex-col items-center justify-center gap-3 bg-ink-100"
      role="status"
      aria-live="polite"
    >
      <Loader2 className="text-brand-600 size-7 animate-spin" aria-hidden />
      {label && <p className="text-sm text-ink-500">{label}</p>}
    </div>
  )
}
