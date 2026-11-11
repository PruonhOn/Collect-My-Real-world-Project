import { screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, it, expect, vi } from 'vitest'
import EmptyState from './EmptyState'
import ErrorState from './ErrorState'
import { renderWithLang } from '../test/render'

describe('EmptyState', () => {
  it('shows custom title and message', () => {
    renderWithLang(<EmptyState title="Empty" message="Nothing here yet." />)
    expect(screen.getByText('Empty')).toBeInTheDocument()
    expect(screen.getByText('Nothing here yet.')).toBeInTheDocument()
  })

  it('falls back to default messages', () => {
    renderWithLang(<EmptyState />)
    expect(screen.getByText('Nothing here')).toBeInTheDocument()
  })

  it('renders the action element', () => {
    renderWithLang(<EmptyState action={<button>Create</button>} />)
    expect(screen.getByRole('button', { name: 'Create' })).toBeInTheDocument()
  })
})

describe('ErrorState', () => {
  it('shows the message and a retry button when provided', async () => {
    const user = userEvent.setup()
    const onRetry = vi.fn()
    renderWithLang(<ErrorState message="Boom" onRetry={onRetry} />)
    expect(screen.getByText(/Boom/)).toBeInTheDocument()
    await user.click(screen.getByRole('button', { name: /try again/i }))
    expect(onRetry).toHaveBeenCalledTimes(1)
  })

  it('does not render a retry button without onRetry', () => {
    renderWithLang(<ErrorState message="Boom" />)
    expect(screen.queryByRole('button')).not.toBeInTheDocument()
  })
})