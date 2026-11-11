import type { ReactNode } from 'react'
import { Link } from 'react-router-dom'
import {
  CheckCircle2,
  Globe2,
  Receipt,
  ShieldCheck,
  Truck,
} from 'lucide-react'
import { cn } from '@/lib/cn'

type AuthVariant = 'login' | 'register'

const COPY: Record<
  AuthVariant,
  { eyebrow: string; title: string; description: string }
> = {
  login: {
    eyebrow: 'Freight forwarding control tower',
    title: 'Every shipment, from booking to invoice, in one place.',
    description:
      'Track consignments, containers, customs clearance, trucking, documentation and finance — with a complete audit trail on every action.',
  },
  register: {
    eyebrow: 'Set up in minutes',
    title: 'One workspace for your entire freight operation.',
    description:
      'Create an account to run shipments, containers, customs clearance, trucking, documentation and finance without switching systems.',
  },
}

const STATS = [
  { label: 'Shipments', value: 'End to end' },
  { label: 'Documents', value: 'Versioned' },
  { label: 'Audit trail', value: 'Every action' },
]

interface AuthScreenProps {
  variant: AuthVariant
  children: ReactNode
}

/**
 * Shared shell for the two primary auth screens.
 *
 * The left half is a decorative operations "control tower": animated trade
 * routes, drifting light and floating shipment cards. It is hidden below `lg`
 * so the form keeps the whole phone screen, and every moving part is disabled
 * by the reduced-motion rule in index.css.
 */
export function AuthScreen({ variant, children }: AuthScreenProps) {
  const copy = COPY[variant]

  return (
    <div className="flex min-h-screen bg-ink-100">
      <aside className="bg-sidebar relative hidden w-1/2 flex-col justify-between overflow-hidden p-10 lg:flex xl:w-[56%] xl:p-14">
        <div
          className="pointer-events-none absolute inset-0"
          style={{
            background:
              'radial-gradient(circle at 12% 8%, rgba(56, 189, 248, 0.2), transparent 42%), radial-gradient(circle at 88% 82%, rgba(99, 102, 241, 0.24), transparent 48%), linear-gradient(155deg, #0c1729 0%, #07101f 58%, #040a12 100%)',
          }}
          aria-hidden
        />

        <div
          className="pointer-events-none absolute inset-0 opacity-[0.055]"
          style={{
            backgroundImage:
              'linear-gradient(to right, #ffffff 1px, transparent 1px), linear-gradient(to bottom, #ffffff 1px, transparent 1px)',
            backgroundSize: '56px 56px',
            maskImage:
              'radial-gradient(ellipse at 50% 42%, #000 28%, transparent 76%)',
            WebkitMaskImage:
              'radial-gradient(ellipse at 50% 42%, #000 28%, transparent 76%)',
          }}
          aria-hidden
        />

        <div
          className="animate-orb pointer-events-none absolute -top-32 -left-24 size-[28rem] rounded-full bg-sky-500/20 blur-[110px]"
          aria-hidden
        />
        <div
          className="animate-orb-slow pointer-events-none absolute top-1/4 -right-28 size-[30rem] rounded-full bg-brand-500/25 blur-[120px]"
          aria-hidden
        />
        <div
          className="animate-orb pointer-events-none absolute -bottom-40 left-1/3 size-[26rem] rounded-full bg-indigo-500/20 blur-[110px]"
          style={{ animationDelay: '-6s' }}
          aria-hidden
        />

        <div className="relative z-10 flex items-center justify-between gap-4">
          <div className="flex items-center gap-3">
            <span className="overflow-hidden rounded-xl shadow-lg shadow-black/40 ring-1 ring-white/15">
              <img
                src="/image/logo.jpg"
                alt="Logistics ERP logo"
                className="size-10 object-cover"
              />
            </span>
            <div>
              <p className="text-base font-semibold tracking-tight text-white">
                Logistics ERP
              </p>
              <p className="text-[11px] tracking-wide text-slate-400">
                Freight Forwarding &amp; Transport Management
              </p>
            </div>
          </div>

          <span className="hidden items-center gap-2 rounded-full border border-emerald-400/20 bg-emerald-400/10 px-3 py-1 text-[11px] font-medium text-emerald-300 xl:inline-flex">
            <span className="relative flex size-1.5">
              <span className="absolute inline-flex size-full animate-ping rounded-full bg-emerald-400 opacity-75 motion-reduce:animate-none" />
              <span className="relative inline-flex size-1.5 rounded-full bg-emerald-400" />
            </span>
            Live operations
          </span>
        </div>

        <div className="relative z-10 flex flex-1 flex-col justify-center py-8">
          <p className="text-brand-300 text-[11px] font-semibold tracking-[0.22em] uppercase">
            {copy.eyebrow}
          </p>
          <h1 className="mt-4 max-w-xl text-3xl leading-[1.15] font-semibold tracking-tight text-white xl:text-[2.6rem]">
            {copy.title}
          </h1>
          <p className="mt-4 max-w-md text-sm leading-relaxed text-slate-400">
            {copy.description}
          </p>

          <RouteVisual />
        </div>

        <div className="relative z-10 flex items-end justify-between gap-6 border-t border-white/10 pt-6">
          <dl className="grid grid-cols-3 gap-6">
            {STATS.map((stat) => (
              <div key={stat.label}>
                <dt className="text-[10px] tracking-[0.14em] text-slate-500 uppercase">
                  {stat.label}
                </dt>
                <dd className="mt-1 text-sm font-medium text-white">
                  {stat.value}
                </dd>
              </div>
            ))}
          </dl>

          <p className="hidden text-[11px] text-slate-500 xl:block">
            &copy; {new Date().getFullYear()} Logistics ERP
          </p>
        </div>
      </aside>

      <main className="relative flex w-full flex-col items-center justify-center overflow-hidden px-4 py-10 lg:w-1/2 xl:w-[44%]">
        <div
          className="pointer-events-none absolute -top-32 -right-24 size-80 rounded-full bg-brand-300/25 blur-3xl"
          aria-hidden
        />
        <div
          className="pointer-events-none absolute -bottom-32 -left-24 size-80 rounded-full bg-sky-300/20 blur-3xl"
          aria-hidden
        />

        <div className="animate-rise relative w-full max-w-[27rem]">
          <div className="mb-6 flex items-center gap-2.5 lg:hidden">
            <span className="overflow-hidden rounded-lg shadow-sm ring-1 ring-ink-200">
              <img
                src="/image/logo.jpg"
                alt="Logistics ERP logo"
                className="size-9 object-cover"
              />
            </span>
            <div>
              <p className="text-sm font-semibold text-ink-900">Logistics ERP</p>
              <p className="text-[11px] text-ink-500">
                Freight Forwarding &amp; Transport
              </p>
            </div>
          </div>

          <div className="auth-stagger rounded-2xl border border-ink-200/80 bg-white/95 p-6 shadow-[0_24px_60px_-24px_rgba(15,23,42,0.28)] backdrop-blur sm:p-8">
            <nav
              aria-label="Authentication"
              className="relative mb-7 grid grid-cols-2 gap-1 rounded-xl bg-ink-100 p-1"
            >
              <span
                className={cn(
                  'absolute inset-y-1 left-1 w-[calc(50%-0.375rem)] rounded-lg bg-white shadow-xs transition-transform duration-300 ease-out',
                  variant === 'register' && 'translate-x-[calc(100%+0.25rem)]',
                )}
                aria-hidden
              />

              <AuthTab to="/login" active={variant === 'login'}>
                Sign in
              </AuthTab>
              <AuthTab to="/register" active={variant === 'register'}>
                Create account
              </AuthTab>
            </nav>

            {children}
          </div>

          <p className="mt-6 text-center text-[11px] text-ink-400">
            Protected by role-based access and a complete audit trail.
          </p>
        </div>
      </main>
    </div>
  )
}

function AuthTab({
  to,
  active,
  children,
}: {
  to: string
  active: boolean
  children: ReactNode
}) {
  return (
    <Link
      to={to}
      aria-current={active ? 'page' : undefined}
      className={cn(
        'relative z-10 flex h-9 items-center justify-center rounded-lg text-sm font-medium transition-colors',
        active ? 'text-ink-900' : 'text-ink-500 hover:text-ink-800',
      )}
    >
      {children}
    </Link>
  )
}

const ROUTES = [
  {
    d: 'M64 128 C 132 60, 198 64, 258 92 S 382 128, 432 54',
    className: 'animate-route',
    opacity: 0.95,
  },
  {
    d: 'M92 44 C 160 96, 232 32, 306 70 S 388 108, 424 132',
    className: 'animate-route-slow',
    opacity: 0.5,
  },
  {
    d: 'M112 150 C 180 120, 244 150, 316 118 S 396 74, 440 90',
    className: 'animate-route',
    opacity: 0.4,
  },
]

const NODES = [
  { cx: 64, cy: 128 },
  { cx: 258, cy: 92 },
  { cx: 432, cy: 54 },
  { cx: 306, cy: 70 },
  { cx: 112, cy: 150 },
]

function RouteVisual() {
  return (
    <div className="relative mt-9 h-64 w-full max-w-[34rem]">
      <div className="absolute inset-x-2 inset-y-4 overflow-hidden rounded-2xl border border-white/10 bg-white/[0.04] shadow-2xl shadow-black/40 backdrop-blur-md">
        <div className="flex items-center justify-between border-b border-white/10 px-4 py-2.5">
          <div className="flex items-center gap-2">
            <Globe2 className="text-brand-200 size-3.5" aria-hidden />
            <span className="text-[10px] font-semibold tracking-[0.2em] text-slate-300 uppercase">
              Live network
            </span>
          </div>
          <span className="flex items-center gap-1.5 text-[10px] font-medium text-emerald-300">
            <span className="relative flex size-1.5">
              <span className="absolute inline-flex size-full animate-ping rounded-full bg-emerald-400 opacity-75 motion-reduce:animate-none" />
              <span className="relative inline-flex size-1.5 rounded-full bg-emerald-400" />
            </span>
            Online
          </span>
        </div>

        <svg
          viewBox="0 0 480 170"
          className="h-[calc(100%-2.4rem)] w-full"
          role="img"
          aria-label="Animated map of active trade routes"
        >
          <defs>
            <linearGradient id="auth-route" x1="0" y1="0" x2="1" y2="0">
              <stop offset="0%" stopColor="#38bdf8" stopOpacity="0.15" />
              <stop offset="50%" stopColor="#38bdf8" />
              <stop offset="100%" stopColor="#818cf8" stopOpacity="0.25" />
            </linearGradient>
            <radialGradient id="auth-node">
              <stop offset="0%" stopColor="#38bdf8" stopOpacity="0.55" />
              <stop offset="100%" stopColor="#38bdf8" stopOpacity="0" />
            </radialGradient>
          </defs>

          <g fill="rgba(255,255,255,0.045)">
            <path d="M40 70c18-18 54-24 78-12s22 38 6 50-52 18-70 2S22 88 40 70Z" />
            <path d="M300 34c26-12 66-6 82 10s6 34-14 40-54 2-70-12-22-28 2-38Z" />
            <path d="M342 112c22-10 52-4 64 10s-2 30-22 34-48-2-58-16 0-22 16-28Z" />
          </g>

          {ROUTES.map((route) => (
            <path
              key={route.d}
              d={route.d}
              fill="none"
              stroke="url(#auth-route)"
              strokeWidth="1.6"
              strokeLinecap="round"
              strokeDasharray="5 8"
              opacity={route.opacity}
              className={route.className}
            />
          ))}

          {NODES.map((node) => (
            <g key={`${node.cx}-${node.cy}`}>
              <circle cx={node.cx} cy={node.cy} r="11" fill="url(#auth-node)" />
              <circle cx={node.cx} cy={node.cy} r="3" fill="#7dd3fc" />
            </g>
          ))}
        </svg>
      </div>

      <div
        className="animate-float absolute top-0 -left-1 w-44 rounded-xl border border-white/15 bg-slate-950/75 p-3 shadow-xl shadow-black/40 backdrop-blur-md"
        style={{ animationDelay: '-1.5s' }}
        aria-hidden
      >
        <div className="flex items-center gap-2.5">
          <span className="bg-brand-500/20 text-brand-200 flex size-8 items-center justify-center rounded-lg">
            <Truck className="size-4" />
          </span>
          <div className="min-w-0">
            <p className="text-[9px] font-medium tracking-[0.16em] text-slate-400 uppercase">
              Container
            </p>
            <p className="truncate text-xs font-semibold text-white">MSKU 4821</p>
          </div>
        </div>
        <div className="mt-2.5 h-1 overflow-hidden rounded-full bg-white/10">
          <div className="from-brand-400 h-full w-[68%] rounded-full bg-gradient-to-r to-emerald-400" />
        </div>
        <p className="mt-1.5 text-[10px] text-slate-400">In transit · 68%</p>
      </div>

      <div
        className="animate-float-slow absolute top-24 -right-1 w-40 rounded-xl border border-emerald-400/20 bg-slate-950/75 p-3 shadow-xl shadow-black/40 backdrop-blur-md"
        style={{ animationDelay: '-3s' }}
        aria-hidden
      >
        <div className="flex items-center gap-2">
          <ShieldCheck className="size-4 text-emerald-400" />
          <p className="text-[11px] font-semibold text-white">Customs cleared</p>
        </div>
        <p className="mt-1 text-[10px] leading-snug text-slate-400">
          Port of Sihanoukville
        </p>
      </div>

      <div
        className="animate-float absolute bottom-0 left-20 w-44 rounded-xl border border-white/15 bg-slate-950/75 p-3 shadow-xl shadow-black/40 backdrop-blur-md"
        style={{ animationDelay: '-0.8s' }}
        aria-hidden
      >
        <div className="flex items-center gap-2.5">
          <span className="flex size-8 items-center justify-center rounded-lg bg-indigo-400/15 text-indigo-200">
            <Receipt className="size-4" />
          </span>
          <div className="min-w-0">
            <p className="text-[9px] font-medium tracking-[0.16em] text-slate-400 uppercase">
              Invoice
            </p>
            <p className="truncate text-xs font-semibold text-white">
              INV-2041 · $12,480
            </p>
          </div>
        </div>
        <div className="mt-2 flex items-center gap-1.5 text-[10px] font-medium text-emerald-300">
          <CheckCircle2 className="size-3" />
          Paid
        </div>
      </div>
    </div>
  )
}
