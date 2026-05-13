import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
  plugins: [react()],
  base: '/spa/',
  server: {
    proxy: {
      '/api': { target: 'http://localhost:8000', changeOrigin: true },
      '/lang': { target: 'http://localhost:8000', changeOrigin: true },
      '/language': { target: 'http://localhost:8000', changeOrigin: true },
    },
  },
  build: {
    outDir: '../public/spa',
    emptyOutDir: true,
  },
});

