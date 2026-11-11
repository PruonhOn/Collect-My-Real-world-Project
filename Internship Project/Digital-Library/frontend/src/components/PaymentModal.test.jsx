import { render, screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, it, expect, vi } from 'vitest'
import { LanguageProvider } from '../context/LanguageContext'
import PaymentModal from './PaymentModal'

function renderPayment(onPay = vi.fn(), onClose = vi.fn()) {
  window.localStorage.setItem('dl_language', 'en')
  return render(
    <LanguageProvider>
      <PaymentModal
        book={{ id: 1, title: 'The Little Prince', price: 9.99 }}
        onPay={onPay}
        onClose={onClose}
      />
    </LanguageProvider>,
  )
}

describe('PaymentModal', () => {
  it('shows the book title and formatted price', () => {
    renderPayment()
    expect(screen.getByText(/The Little Prince/)).toBeInTheDocument()
    expect(screen.getByText(/\$9\.99/)).toBeInTheDocument()
  })

  it('lists multiple payment methods including card/stripe', () => {
    renderPayment()
    expect(screen.getAllByRole('button').length).toBeGreaterThan(3)
    expect(screen.getByText(/Card \/ Stripe/i)).toBeInTheDocument()
  })

  it('submits the selected payment method', async () => {
    const user = userEvent.setup()
    const onPay = vi.fn().mockResolvedValue(undefined)
    renderPayment(onPay)

    await user.click(screen.getByText(/Card \/ Stripe/i))
    await user.click(screen.getByRole('button', { name: /i have paid/i }))

    expect(onPay).toHaveBeenCalledWith('stripe')
  })

  it('calls onClose when the close button is pressed', async () => {
    const user = userEvent.setup()
    const onClose = vi.fn()
    renderPayment(vi.fn().mockResolvedValue(undefined), onClose)

    await user.click(screen.getByRole('button', { name: /close/i }))
    expect(onClose).toHaveBeenCalled()
  })
})