import { createContext, useContext } from 'react'

export type ToastVariant = 'success' | 'error' | 'info' | 'warning'

export interface Toast {
  id: number
  variant: ToastVariant
  title: string
  description?: string
}

export interface ToastContextValue {
  success: (title: string, description?: string) => void
  error: (title: string, description?: string) => void
  info: (title: string, description?: string) => void
  warning: (title: string, description?: string) => void
}

export const ToastContext = createContext<ToastContextValue | null>(null)

/**
 * Returns the toast helpers.
 *
 * Throws when used outside the provider, because a silently missing toast is
 * far harder to diagnose than an immediate error.
 */
export function useToast(): ToastContextValue {
  const context = useContext(ToastContext)

  if (!context) {
    throw new Error('useToast must be used inside a <ToastProvider>.')
  }

  return context
}
