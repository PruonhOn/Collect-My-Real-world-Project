import { AlertTriangle } from 'lucide-react'
import type { ReactNode } from 'react'
import { Button } from '@/components/ui/Button'
import { Modal } from '@/components/ui/Modal'

export interface ConfirmDialogProps {
  open: boolean
  title: string
  message: ReactNode
  confirmLabel?: string
  cancelLabel?: string
  tone?: 'danger' | 'primary'
  loading?: boolean
  onConfirm: () => void | Promise<void>
  onCancel: () => void
}

/**
 * Confirmation for destructive or irreversible actions.
 *
 * The brief requires confirmation on critical actions, so this is a component
 * rather than a bare `window.confirm`, which cannot be styled and blocks the
 * event loop.
 */
export function ConfirmDialog({
  open,
  title,
  message,
  confirmLabel = 'Confirm',
  cancelLabel = 'Cancel',
  tone = 'danger',
  loading = false,
  onConfirm,
  onCancel,
}: ConfirmDialogProps) {
  return (
    <Modal
      open={open}
      onClose={onCancel}
      title={title}
      size="sm"
      dismissible={!loading}
      footer={
        <>
          <Button variant="outline" onClick={onCancel} disabled={loading}>
            {cancelLabel}
          </Button>
          <Button
            variant={tone === 'danger' ? 'danger' : 'primary'}
            onClick={() => void onConfirm()}
            loading={loading}
          >
            {confirmLabel}
          </Button>
        </>
      }
    >
      <div className="flex gap-3">
        <div
          className={
            tone === 'danger'
              ? 'bg-danger-50 text-danger-600 flex size-9 shrink-0 items-center justify-center rounded-full'
              : 'bg-brand-50 text-brand-600 flex size-9 shrink-0 items-center justify-center rounded-full'
          }
        >
          <AlertTriangle className="size-4.5" aria-hidden />
        </div>
        <div className="pt-1 text-sm text-ink-600">{message}</div>
      </div>
    </Modal>
  )
}
