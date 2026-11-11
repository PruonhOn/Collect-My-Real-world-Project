import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import tailwindcss from '@tailwindcss/vite'
import path from 'node:path'

// https://vite.dev/config/
export default defineConfig({
  plugins: [react(), tailwindcss()],
  resolve: {
    alias: {
      '@': path.resolve(import.meta.dirname, './src'),
    },
  },
  server: {
    port: 5173,
    strictPort: false,
    proxy: {
      // The SPA talks to the Laravel API through the dev server so that the
      // browser never deals with cross-origin requests during development.
      '/api': {
        target: process.env.VITE_API_PROXY_TARGET || 'http://127.0.0.1:8100',
        changeOrigin: true,
      },
    },
  },
  build: {
    outDir: 'dist',
    sourcemap: false,
    rollupOptions: {
      output: {
        manualChunks(id) {
          if (!id.includes('node_modules')) {
            return undefined
          }

          if (id.includes('recharts') || id.includes('d3-')) {
            return 'charts'
          }

          if (id.includes('react-hook-form') || id.includes('zod') || id.includes('@hookform')) {
            return 'forms'
          }

          if (id.includes('react-router') || id.includes('/react-dom/') || id.includes('/react/')) {
            return 'react'
          }

          return 'vendor'
        },
      },
    },
  },
})
