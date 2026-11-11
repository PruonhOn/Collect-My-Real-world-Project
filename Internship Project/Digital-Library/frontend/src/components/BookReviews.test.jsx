import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { MemoryRouter } from 'react-router-dom'
import { describe, it, expect, vi, beforeEach } from 'vitest'
import { LanguageProvider } from '../context/LanguageContext'
import { useAuth as mockUseAuth } from '../context/AuthContext'
import { useToast as mockUseToast } from '../context/ToastContext'
import client from '../api/client'
import BookReviews from './BookReviews'

vi.mock('../api/client', () => ({ default: { get: vi.fn(), post: vi.fn(), delete: vi.fn() } }))
vi.mock('../context/AuthContext', () => ({ useAuth: vi.fn() }))
vi.mock('../context/ToastContext', () => ({ useToast: vi.fn() }))

function renderReviews(props = {}) {
  return render(
    <LanguageProvider>
      <MemoryRouter>
        <BookReviews
          bookId={1}
          avgRating={4.2}
          reviewsCount={3}
          canReview
          {...props}
        />
      </MemoryRouter>
    </LanguageProvider>,
  )
}

describe('BookReviews', () => {
  beforeEach(() => {
    window.localStorage.setItem('dl_language', 'en')
    vi.clearAllMocks()
    mockUseToast.mockReturnValue({ toast: vi.fn() })
    mockUseAuth.mockReturnValue({ isAuthenticated: true, user: { id: 1, name: 'Kim', role: 'reader' } })
    client.get.mockResolvedValue({
      data: {
        reviews: {
          data: [
            { id: 10, rating: 5, comment: 'Great book!', user_id: 1, user: { id: 1, name: 'Kim' }, created_at: '2026-01-01T00:00:00.000000Z' },
            { id: 11, rating: 3, comment: 'Okay', user_id: 2, user: { id: 2, name: 'Sam' }, created_at: '2026-01-01T00:00:00.000000Z' },
          ],
        },
      },
    })
  })

  it('shows the rating summary and list of reviews', async () => {
    renderReviews()
    expect(await screen.findByText(/4.2 \/ 5/)).toBeInTheDocument()
    expect(screen.getByText('Great book!')).toBeInTheDocument()
    expect(screen.getByText('Okay')).toBeInTheDocument()
  })

  it('shows the review form for an authenticated reader with access', async () => {
    renderReviews()
    expect(await screen.findByRole('button', { name: /submit review/i })).toBeInTheDocument()
  })

  it('submits a rating and comment to the reviews endpoint', async () => {
    const toast = vi.fn()
    mockUseToast.mockReturnValue({ toast })
    client.post.mockResolvedValue({ data: { message: 'Review saved.' } })

    const user = userEvent.setup()
    renderReviews()

    await user.click(await screen.findByRole('button', { name: '4 star' }))
    await user.type(screen.getByPlaceholderText(/comment/i), 'Fantastic!')
    await user.click(screen.getByRole('button', { name: /submit review/i }))

    await waitFor(() =>
      expect(client.post).toHaveBeenCalledWith('/books/1/reviews', { rating: 4, comment: 'Fantastic!' }),
    )
  })

  it('prompts guests to log in instead of showing the form', async () => {
    mockUseAuth.mockReturnValue({ isAuthenticated: false, user: null })
    renderReviews()
    expect(await screen.findByText(/log in to write a review/i)).toBeInTheDocument()
    expect(screen.queryByRole('button', { name: /submit review/i })).not.toBeInTheDocument()
  })

  it('explains that access is required when the user cannot read the book', async () => {
    mockUseAuth.mockReturnValue({ isAuthenticated: true, user: { id: 1, name: 'Kim', role: 'reader' } })
    renderReviews({ canReview: false })
    expect(await screen.findByText(/buy this book or get access/i)).toBeInTheDocument()
  })
})