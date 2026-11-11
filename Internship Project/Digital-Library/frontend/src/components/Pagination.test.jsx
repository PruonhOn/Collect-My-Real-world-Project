import { screen } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { describe, it, expect, vi } from 'vitest'
import Pagination from './Pagination'
import { renderWithLang } from '../test/render'

describe('Pagination', () => {
  it('renders nothing when there is a single page', () => {
    renderWithLang(<Pagination meta={{ last_page: 1 }} onPageChange={vi.fn()} />)
    expect(screen.queryByText(/page/i)).not.toBeInTheDocument()
  })

  it('shows current and last page numbers', () => {
    renderWithLang(
      <Pagination
        meta={{ current_page: 2, last_page: 5, prev_page_url: '#', next_page_url: '#' }}
        onPageChange={vi.fn()}
      />,
    )
    expect(screen.getByText(/2 \/ 5/)).toBeInTheDocument()
  })

  it('calls onPageChange with next page when clicking next', async () => {
    const user = userEvent.setup()
    const onChange = vi.fn()
    renderWithLang(
      <Pagination
        meta={{ current_page: 1, last_page: 3, prev_page_url: null, next_page_url: '#next' }}
        onPageChange={onChange}
      />,
    )
    await user.click(screen.getByText(/next/i))
    expect(onChange).toHaveBeenCalledWith(2)
  })

  it('disables the prev button on the first page', () => {
    renderWithLang(
      <Pagination
        meta={{ current_page: 1, last_page: 3, prev_page_url: null, next_page_url: '#next' }}
        onPageChange={vi.fn()}
      />,
    )
    expect(screen.getByText(/prev/i).closest('button')).toBeDisabled()
  })
})