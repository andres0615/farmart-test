import react from '@vitejs/plugin-react'
import { defineConfig } from 'vite'
import tailwindcss from '@tailwindcss/vite'
import path from 'path'
import { fileURLToPath } from 'url'

const __dirname = path.dirname(fileURLToPath(import.meta.url))

// https://vite.dev/config/
export default defineConfig({
  resolve: {
    alias: {
      '@': path.resolve(__dirname, 'src'),
    },
  },
  plugins: [tailwindcss(), react()],
  server: {
    proxy: {
      '/api': {
        target: 'http://api:8080',
        changeOrigin: true,
      },
    },
    host: true, // escucha en todas las interfaces, no solo localhost
    allowedHosts: true,
  },
})
