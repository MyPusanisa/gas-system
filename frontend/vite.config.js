import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [react()],
  server: {
    port: 5173,
    proxy: {
      '/Backend': {
        target: 'http://localhost',
        changeOrigin: true,
        secure: false,
      }
    }
  }
})