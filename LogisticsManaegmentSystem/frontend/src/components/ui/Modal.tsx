import { X } from 'lucide-react'
import { useEffect, useId, useRef } from 'react'
import type { ReactNode } from 'react'
import { createPortal } from 'react-dom'
import { cn } from '@/lib/cn'

const SIZES = {
  sm: 'max-w-md',
  md: 'max-w-lg',
  lg: 'max-w-2xl',
  xl: 'max-w-4xl',
  full: 'max-w-6xl',
} as const

export interface ModalProps {
  open: boolean
  onClose: () => void
  title: string
  description?: string
  size?: keyof typeof SIZES
  children: ReactNode
  footer?: ReactNode
  /** Blocks backdrop and Escape dismissal, e.g. while a form is saving. */
  dismissible?: boolean
}

export function Modal({
  open,
  onClose,
  title,
  description,
  size = 'md',
  children,
  footer,
  dismissible = true,
}: ModalProps) {
  const panelRef = useRef<HTMLDivElement>(null)
  const previouslyFocused = useRef<HTMLElement | null>(null)
  const titleId = useId()
  const descriptionId = useId()

  // Move focus into the dialog on open and restore it on close, so keyboard
  // users are not dropped at the top of the page after a modal.
  useEffect(() => {
    if (!open) return

    previouslyFocused.current = document.activeElement as HTMLElement | null

    // Defer so the panel is mounted before we look for something focusable.
    const frame = requestAnimationFrame(() => {
      const focusable = panelRef.current?.querySelector<HTMLElement>(
        'input:not([type="hidden"]), select, textarea, button, [href], [tabindex]:not([tabindex="-1"])',
      )
      ;(focusable ?? panelRef.current)?.focus()
    })

    return () => {
      cancelAnimationFrame(frame)
      previouslyFocused.current?.focus()
    }
  }, [open])

  // Escape closes; Tab is trapped inside the panel.
  useEffect(() => {
    if (!open) return

    function onKeyDown(event: KeyboardEvent) {
      if (event.key === 'Escape' && dismissible) {
        event.stopPropagation()
        onClose()
        return
      }

      if (event.key !== 'Tab' || !panelRef.current) return

      const focusables = Array.from(
        panelRef.current.querySelectorAll<HTMLElement>(
          'input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled]), button:not([disabled]), [href], [tabindex]:not([tabindex="-1"])',
        ),
      ).filter((element) => element.offsetParent !== null)

      if (focusables.length === 0) {
        event.preventDefault()
        return
      }

      const first = focusables[0]
      const last = focusables[focusables.length - 1]

      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault()
        last.focus()
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault()
        first.focus()
      }
    }

    document.addEventListener('keydown', onKeyDown, true)
    return () => document.removeEventListener('keydown', onKeyDown, true)
  }, [open, dismissible, onClose])

  // Prevent the page behind the dialog from scrolling.
  useEffect(() => {
    if (!open) return

    const previousOverflow = document.body.style.overflow
    document.body.style.overflow = 'hidden'

    return () => {
      document.body.style.overflow = previousOverflow
    }
  }, [open])

  if (!open) return null

  return createPortal(
    <div className="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto p-4 sm:items-center">
      <div
        className="fixed inset-0 bg-ink-950/45 backdrop-blur-[2px]"
        onClick={dismissible ? onClose : undefined}
        aria-hidden
      />

      <div
        ref={panelRef}
        role="dialog"
        aria-modal="true"
        aria-labelledby={titleId}
        aria-describedby={description ? descriptionId : undefined}
        tabIndex={-1}
        className={cn(
          'relative z-10 w-full rounded-lg bg-white shadow-xl ring-1 ring-ink-900/10',
          'max-h-[calc(100vh-2rem)] flex flex-col',
          SIZES[size],
        )}
      >
        <div className="border-ink-200 flex items-start justify-between gap-4 border-b px-5 py-4">
          <div className="min-w-0">
            <h2 id={titleId} className="text-base font-semibold text-ink-900">
              {title}
            </h2>
            {description && (
              <p id={descriptionId} className="mt-0.5 text-sm text-ink-500">
                {description}
              </p>
            )}
          </div>

          {dismissible && (
            <button
              type="button"
              onClick={onClose}
              aria-label="Close dialog"
              className="-mt-1 -mr-1 flex size-8 shrink-0 items-center justify-center rounded-md text-ink-400 transition-colors hover:bg-ink-100 hover:text-ink-700"
            >
              <X className="size-4" aria-hidden />
            </button>
          )}
        </div>

        <div className="scrollbar-thin flex-1 overflow-y-auto px-5 py-4">{children}</div>

        {footer && (
          <div className="border-ink-200 flex items-center justify-end gap-2 border-t bg-ink-50/60 px-5 py-3">
            {footer}
          </div>
        )}
      </div>
    </div>,
    document.body,
  )
}
