import { createContext, useCallback, useContext, useState } from 'react'

const ToastContext = createContext(null)

let idCounter = 0

export function ToastProvider({ children }) {
  const [toasts, setToasts] = useState([])

  const remove = useCallback((id) => {
    setToasts((prev) => prev.filter((t) => t.id !== id))
  }, [])

  const push = useCallback((message, type = 'success') => {
    const id = ++idCounter
    setToasts((prev) => [...prev, { id, message, type }])
    setTimeout(() => remove(id), 4000)
  }, [remove])

  const toast = useCallback(
    (message, type = 'success') => push(message, type),
    [push],
  )

  const icons = {
    success: '✓',
    error: '✕',
    info: 'ℹ',
    warning: '⚠',
  }

  const colors = {
    success: 'bg-emerald-600',
    error: 'bg-red-600',
    info: 'bg-blue-600',
    warning: 'bg-amber-500',
  }

  return (
    <ToastContext.Provider value={{ toast }}>
      {children}
      <div className="fixed top-4 right-4 z-[100] flex flex-col gap-2 w-80 max-w-[90vw]">
        {toasts.map((t) => (
          <div
            key={t.id}
            className={`${colors[t.type]} text-white rounded-xl shadow-xl p-4 flex items-start gap-3 animate-toast-in`}
          >
            <span className="font-bold text-lg leading-none mt-0.5">{icons[t.type]}</span>
            <span className="text-sm flex-1">{t.message}</span>
            <button
              onClick={() => remove(t.id)}
              className="text-white/70 hover:text-white"
              aria-label="បិទ"
            >
              ×
            </button>
          </div>
        ))}
      </div>
    </ToastContext.Provider>
  )
}

export function useToast() {
  const ctx = useContext(ToastContext)
  if (!ctx) throw new Error('useToast must be used within ToastProvider')
  return ctx
}
