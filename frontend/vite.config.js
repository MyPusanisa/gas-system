import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

export default defineConfig({
  plugins: [react()],
  server: {
    host: true,
    proxy: {
      '/Backend': {
        target: 'http://suchat-gas.com',
        changeOrigin: true,
        secure: false
      }
    }
  }
})