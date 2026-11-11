/**
 * Theme templates.
 *
 * A theme template overrides the brand ramp (and the fixed sidebar surface)
 * defined in `index.css`. Adding a fourth theme is: add an entry here, and a
 * matching `html[data-theme="..."]` block in `index.css`. Nothing else in the
 * application references a theme by name, so the two places are the whole
 * contract.
 *
 * `swatch` is the literal colour shown on the picker card. It cannot be a
 * Tailwind utility such as `bg-brand-600`, because that would track the
 * currently applied theme and every card would show the currently selected
 * colour instead of its own.
 */

export type ThemeId = 'maritime' | 'emerald' | 'sunset' | 'light' | 'dark'

export interface Theme {
  id: ThemeId
  /** Short label for the picker card and the account menu. */
  label: string
  /** One line describing the look, shown under the label. */
  description: string
  /** Representative accent, as a CSS colour. */
  swatch: string
  /** Deep tone used for the card's header strip. */
  swatchDeep: string
}

export const THEMES: readonly Theme[] = [
  {
    id: 'maritime',
    label: 'Maritime',
    description: 'Deep maritime blue. The default for the ERP.',
    swatch: 'oklch(0.5 0.13 250)',
    swatchDeep: 'oklch(0.36 0.095 252)',
  },
  {
    id: 'emerald',
    label: 'Emerald',
    description: 'Cool teal green. Calmer for long operational shifts.',
    swatch: 'oklch(0.5 0.13 160)',
    swatchDeep: 'oklch(0.36 0.095 160)',
  },
  {
    id: 'sunset',
    label: 'Sunset',
    description: 'Warm amber with high contrast for bright depots.',
    swatch: 'oklch(0.58 0.15 55)',
    swatchDeep: 'oklch(0.42 0.1 45)',
  },
  {
    id: 'light',
    label: 'Light',
    description: 'Default light mode with maritime branding',
    swatch: 'oklch(0.5 0.13 250)',
    swatchDeep: 'oklch(0.36 0.095 252)',
  },
  {
    id: 'dark',
    label: 'Dark',
    description: 'Dark mode for low-light environments',
    swatch: 'oklch(0.4 0.1 250)',
    swatchDeep: 'oklch(0.24 0.08 250)',
  },
] as const

export const DEFAULT_THEME: ThemeId = 'maritime'

export function isThemeId(value: unknown): value is ThemeId {
  return typeof value === 'string' && THEMES.some((theme) => theme.id === value)
}

export function getTheme(id: string): Theme {
  return THEMES.find((theme) => theme.id === id) ?? THEMES[0]
}
