import { AlertCircle, CheckCircle2, Info, X, XCircle } from 'lucide-react'
import { useCallback, useEffect, useMemo, useRef, useState, type ReactNode } from 'react'
import { cn } from '@/lib/cn'
import { ToastContext, type Toast, type ToastContextValue, type ToastVariant } from '@/components/ui/toast-context'

const AUTO_DISMISS_MS = 5000

const ICONS: Record<ToastVariant, typeof Info> = {
  success: CheckCircle2,
  error: XCircle,
  info: Info,
  warning: AlertCircle,
}

const STYLES: Record<ToastVariant, string> = {
  success: 'text-success-600',
  error: 'text-danger-600',
  info: 'text-info-600',
  warning: 'text-warning-600',
}

export function ToastProvider({ children }: { children: ReactNode }) {
  const [toasts, setToasts] = useState<Toast[]>([])
  const nextId = useRef(1)
  const timers = useRef(new Map<number, ReturnType<typeof setTimeout>>())

  const dismiss = useCallback((id: number) => {
    setToasts((current) => current.filter((toast) => toast.id !== id))

    const timer = timers.current.get(id)
    if (timer) {
      clearTimeout(timer)
      timers.current.delete(id)
    }
  }, [])

  const push = useCallback(
    (variant: ToastVariant, title: string, description?: string) => {
      const id = nextId.current++
      setToasts((current) => [...current, { id, variant, title, description }])

      timers.current.set(
        id,
        setTimeout(() => dismiss(id), AUTO_DISMISS_MS),
      )
    },
    [dismiss],
  )

  // Clear pending timers if the provider unmounts mid-countdown.
  useEffect(() => {
    const pending = timers.current
    return () => {
      pending.forEach(clearTimeout)
      pending.clear()
    }
  }, [])

  const value = useMemo<ToastContextValue>(
    () => ({
      success: (title, description) => push('success', title, description),
      error: (title, description) => push('error', title, description),
      info: (title, description) => push('info', title, description),
      warning: (title, description) => push('warning', title, description),
    }),
    [push],
  )

  return (
    <ToastContext.Provider value={value}>
      {children}

      <div
        // Assertive so a failed save interrupts a screen reader immediately;
        // a success confirmation can wait.
        aria-live="assertive"
        aria-atomic="false"
        className="pointer-events-none fixed right-4 bottom-4 z-[100] flex w-full max-w-sm flex-col gap-2"
      >
        {toasts.map((toast) => {
          const Icon = ICONS[toast.variant]

          return (
            <div
              key={toast.id}
              role="alert"
              className="animate-in fade-in slide-in-from-bottom-2 pointer-events-auto flex items-start gap-3 rounded-lg border border-ink-200 bg-white p-3.5 shadow-lg"
            >
              <Icon className={cn('mt-0.5 size-4.5 shrink-0', STYLES[toast.variant])} aria-hidden />

              <div className="min-w-0 flex-1">
                <p className="text-sm font-medium text-ink-900">{toast.title}</p>
                {toast.description && (
                  <p className="mt-0.5 text-xs break-words text-ink-500">{toast.description}</p>
                )}
              </div>

              <button
                type="button"
                onClick={() => dismiss(toast.id)}
                aria-label="Dismiss notification"
                className="text-ink-400 hover:text-ink-700 -mt-0.5 -mr-0.5 flex size-6 shrink-0 items-center justify-center rounded transition-colors hover:bg-ink-100"
              >
                <X className="size-3.5" aria-hidden />
              </button>
            </div>
          )
        })}
      </div>
    </ToastContext.Provider>
  )
}
