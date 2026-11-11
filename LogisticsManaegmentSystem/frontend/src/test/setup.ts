/**
 * Vitest global setup.
 *
 * Loaded through the `test.setupFiles` entry in vite.config.ts, so it runs once
 * per test worker before any spec is imported.
 */
import '@testing-library/jest-dom/vitest'

// jsdom does not implement matchMedia, which TanStack Query and a few layout
// components probe on mount. Provide a spec-compliant inert stub.
if (!window.matchMedia) {
  window.matchMedia = (query: string): MediaQueryList =>
    ({
      matches: false,
      media: query,
      onchange: null,
      addListener: () => {},
      removeListener: () => {},
      addEventListener: () => {},
      removeEventListener: () => {},
      dispatchEvent: () => false,
    }) as MediaQueryList
}
