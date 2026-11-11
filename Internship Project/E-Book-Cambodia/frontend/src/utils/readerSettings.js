/**
 * Reader display settings.
 *
 * These are presentation-only values, so they live on the device (localStorage)
 * and, when the reader is signed in, on the account as well. Keeping the
 * defaults and the normalisation in one module means the settings panel, the
 * account sync and the CSS all agree on the allowed values.
 */

export const THEMES = ['light', 'sepia', 'focus']

export const FONTS = ['serif', 'sans']

export const MODES = ['spread', 'single', 'scroll']

/** English body size in px for each of the five A− / A+ steps. */
export const FONT_STEPS = [16, 17, 18, 20, 22]

export const LINE_HEIGHTS = [1.6, 1.7, 1.9, 2.1]

export const DEFAULT_SETTINGS = Object.freeze({
  fontSize: 2,
  theme: 'light',
  font: 'serif',
  lineHeight: 1.7,
  mode: 'spread',
})

const STORAGE_KEY = 'pi-reader-settings'

const clampInt = (value, min, max, fallback) => {
  const number = Number(value)
  if (!Number.isFinite(number)) return fallback
  return Math.min(max, Math.max(min, Math.round(number)))
}

/**
 * Coerce anything (stored JSON, an API payload, a broken value) into a usable
 * settings object. Returns null when nothing usable is present so callers can
 * distinguish "no preferences yet" from "use the defaults".
 *
 * @param {unknown} raw
 * @returns {typeof DEFAULT_SETTINGS|null}
 */
export function normalizeSettings(raw) {
  if (!raw || typeof raw !== 'object' || Array.isArray(raw)) return null

  const hasAny = ['fontSize', 'theme', 'font', 'lineHeight', 'mode']
    .some((key) => key in raw)
  if (!hasAny) return null

  const settings = { ...DEFAULT_SETTINGS }

  if ('fontSize' in raw) {
    settings.fontSize = clampInt(raw.fontSize, 0, FONT_STEPS.length - 1, DEFAULT_SETTINGS.fontSize)
  }
  if (THEMES.includes(raw.theme)) settings.theme = raw.theme
  if (FONTS.includes(raw.font)) settings.font = raw.font
  if (MODES.includes(raw.mode)) settings.mode = raw.mode

  if ('lineHeight' in raw) {
    const value = Number(raw.lineHeight)
    if (Number.isFinite(value)) {
      // Snap to the nearest supported step so old values keep working.
      settings.lineHeight = LINE_HEIGHTS.reduce(
        (best, option) => (Math.abs(option - value) < Math.abs(best - value) ? option : best),
        LINE_HEIGHTS[0],
      )
    }
  }

  return settings
}

/**
 * @returns {typeof DEFAULT_SETTINGS}
 */
export function loadSettings() {
  if (typeof window === 'undefined') return { ...DEFAULT_SETTINGS }
  try {
    const stored = window.localStorage.getItem(STORAGE_KEY)
    if (!stored) return { ...DEFAULT_SETTINGS }
    return normalizeSettings(JSON.parse(stored)) || { ...DEFAULT_SETTINGS }
  } catch {
    return { ...DEFAULT_SETTINGS }
  }
}

/**
 * @param {typeof DEFAULT_SETTINGS} settings
 */
export function saveSettings(settings) {
  if (typeof window === 'undefined') return
  try {
    window.localStorage.setItem(STORAGE_KEY, JSON.stringify(settings))
  } catch {
    // Storage can be full or disabled in private mode; the reader still works.
  }
}

/**
 * Body size for the current language. Khmer script has taller stacks, so the
 * same type step is rendered two pixels larger to keep the x-height balanced.
 *
 * @param {typeof DEFAULT_SETTINGS} settings
 * @param {boolean} khmer
 * @returns {number}
 */
export function bodyFontSize(settings, khmer) {
  const step = FONT_STEPS[clampInt(settings.fontSize, 0, FONT_STEPS.length - 1, DEFAULT_SETTINGS.fontSize)]
  return step + (khmer ? 2 : 0)
}

/** Human label for a type step, e.g. "100%". */
export function fontStepLabel(settings) {
  const step = clampInt(settings.fontSize, 0, FONT_STEPS.length - 1, DEFAULT_SETTINGS.fontSize)
  return `${step + 1}/${FONT_STEPS.length}`
}
