import tailwindcss from '@tailwindcss/vite'
import react from '@vitejs/plugin-react'
import path from 'node:path'
import { fileURLToPath } from 'node:url'
import { loadEnv } from 'vite'
// Imported from vitest/config rather than vite so the `test` block is typed.
import { defineConfig } from 'vitest/config'

const rootDir = path.dirname(fileURLToPath(import.meta.url))

/**
 * Longest patterns first: `react` would otherwise swallow `react-dom`,
 * `react-router-dom` and `recharts`.
 */
const VENDOR_CHUNKS: { name: string; pattern: string }[] = [
  { name: 'charts', pattern: 'recharts|victory-vendor|d3-.*' },
  { name: 'query', pattern: '@tanstack/.*' },
  { name: 'forms', pattern: 'react-hook-form|@hookform/.*|zod' },
  { name: 'react', pattern: 'react|scheduler' },
]

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, rootDir, 'VITE_')

  // In development the SPA is served by Vite and the API by Laravel, so the two
  // live on different ports. Proxying /api keeps the browser on a single
  // origin, which means no CORS preflight and cookies behave normally.
  const apiTarget = env.VITE_DEV_API_PROXY ?? 'http://127.0.0.1:8000'

  return {
    plugins: [react(), tailwindcss()],
    resolve: {
      alias: {
        '@': path.resolve(rootDir, 'src'),
      },
    },
    server: {
      // Bind IPv4 explicitly. Left to its own devices Vite listens on ::1 only,
      // so http://127.0.0.1:5173 refuses the connection while localhost works.
      host: '127.0.0.1',
      port: 5173,
      strictPort: true,
      proxy: {
        '/api': {
          target: apiTarget,
          changeOrigin: true,
          // Uploads (bill of lading scans) can be large; do not cut them off.
          timeout: 120_000,
          proxyTimeout: 120_000,
        },
        '/sanctum': {
          target: apiTarget,
          changeOrigin: true,
        },
      },
    },
    build: {
      outDir: 'dist',
      sourcemap: mode !== 'production',
      // Split the vendor libraries out of the app bundle so that a UI-only
      // deploy does not invalidate megabytes of chart/table/router code.
      // Vite 8 bundles with Rolldown, whose `manualChunks` hook is a function
      // that returns a chunk name, so match on the package name instead of
      // using the legacy object map.
      rollupOptions: {
        output: {
          manualChunks(id) {
            if (!id.includes('node_modules')) return undefined

            const match = VENDOR_CHUNKS.find((chunk) => new RegExp(`[/\\\\]${chunk.pattern}[/\\\\]`).test(id))
            return match?.name
          },
        },
      },
    },
    test: {
      environment: 'jsdom',
      globals: true,
      setupFiles: ['./src/test/setup.ts'],
      css: false,
    },
  }
})
