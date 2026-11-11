import { render, screen, act } from '@testing-library/react'
import { describe, it, expect } from 'vitest'
import { LanguageProvider, useLanguage } from './LanguageContext'

function Probe() {
  const { t } = useLanguage()
  return (
    <div>
      <p data-testid="interp">{t('reviewsCountSuffix', { count: 3 })}</p>
      <p data-testid="missing">{t('does_not_exist_key')}</p>
    </div>
  )
}

describe('LanguageContext', () => {
  it('interpolates placeholders in translations', () => {
    window.localStorage.setItem('dl_language', 'en')
    render(
      <LanguageProvider>
        <Probe />
      </LanguageProvider>,
    )
    expect(screen.getByTestId('interp')).toHaveTextContent('3 reviews')
  })

  it('returns the key itself when the translation is missing', () => {
    window.localStorage.setItem('dl_language', 'en')
    act(() => {})
    render(
      <LanguageProvider>
        <Probe />
      </LanguageProvider>,
    )
    expect(screen.getByTestId('missing')).toHaveTextContent('does_not_exist_key')
  })

  it('loads saved language from localStorage', () => {
    window.localStorage.setItem('dl_language', 'fr')
    render(
      <LanguageProvider>
        <Probe />
      </LanguageProvider>,
    )
    expect(screen.getByTestId('interp')).toHaveTextContent('3 avis')
  })
})