import { cn } from '@/lib/cn'

/**
 * Full-page navigation to the API, which then redirects to Google.
 *
 * An anchor rather than a click handler on purpose: this is a navigation, and it
 * has to work with keyboard activation, middle-click and the browser's own
 * back/forward behaviour.
 */
const GOOGLE_REDIRECT_URL = `${(
  import.meta.env.VITE_API_BASE_URL ?? '/api/v1'
).replace(/\/$/, '')}/auth/google/redirect`

export function GoogleSignInButton({ className }: { className?: string }) {
  return (
    <a
      href={GOOGLE_REDIRECT_URL}
      className={cn(
        'inline-flex h-11 w-full items-center justify-center gap-2.5 rounded-md border border-ink-300 bg-white',
        'text-sm font-medium text-ink-700 shadow-xs transition-colors',
        'hover:border-ink-400 hover:bg-ink-50 focus:ring-2 focus:ring-brand-500/25 focus:outline-none',
        'active:bg-ink-100',
        className,
      )}
    >
      <GoogleMark />
      Continue with Google
    </a>
  )
}

export function AuthDivider() {
  return (
    <div className="my-5 flex items-center gap-3" aria-hidden>
      <span className="bg-ink-200 h-px flex-1" />
      <span className="text-[11px] font-medium tracking-wider text-ink-400 uppercase">
        or
      </span>
      <span className="bg-ink-200 h-px flex-1" />
    </div>
  )
}

function GoogleMark() {
  return (
    <svg viewBox="0 0 24 24" className="size-[18px]" aria-hidden>
      <path
        fill="#4285F4"
        d="M21.6 12.227c0-.709-.064-1.39-.182-2.045H12v3.868h5.382a4.6 4.6 0 0 1-1.996 3.018v2.51h3.232c1.891-1.742 2.982-4.305 2.982-7.35Z"
      />
      <path
        fill="#34A853"
        d="M12 22c2.7 0 4.964-.895 6.618-2.422l-3.232-2.51c-.895.6-2.04.955-3.386.955-2.605 0-4.81-1.76-5.598-4.123H3.06v2.59A9.996 9.996 0 0 0 12 22Z"
      />
      <path
        fill="#FBBC05"
        d="M6.402 13.9A6.01 6.01 0 0 1 6.09 12c0-.66.113-1.3.312-1.9V7.51H3.06A9.996 9.996 0 0 0 2 12c0 1.614.386 3.14 1.06 4.49l3.342-2.59Z"
      />
      <path
        fill="#EA4335"
        d="M12 5.977c1.468 0 2.786.505 3.823 1.496l2.868-2.868C16.96 2.99 14.695 2 12 2a9.996 9.996 0 0 0-8.94 5.51l3.342 2.59C7.19 7.737 9.395 5.977 12 5.977Z"
      />
    </svg>
  )
}
