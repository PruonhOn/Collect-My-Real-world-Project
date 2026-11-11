import { render } from '@testing-library/react'
import { MemoryRouter } from 'react-router-dom'
import { LanguageProvider } from '../context/LanguageContext'

export function renderWithLang(ui, { route = '/' } = {}) {
  window.localStorage.setItem('dl_language', 'en')
  return render(
    <LanguageProvider>
      <MemoryRouter initialEntries={[route]}>{ui}</MemoryRouter>
    </LanguageProvider>,
  )
}