import { useState } from 'react'

/**
 * Small hook that owns the open/closed/pending state of a confirmation, so
 * feature components do not each reinvent it.
 */
export function useConfirm() {
  const [isOpen, setIsOpen] = useState(false)
  const [isPending, setIsPending] = useState(false)

  return {
    isOpen,
    isPending,
    open: () => setIsOpen(true),
    close: () => {
      if (!isPending) setIsOpen(false)
    },
    setIsPending,
  }
}
