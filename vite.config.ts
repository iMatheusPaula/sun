import { fileURLToPath, URL } from 'node:url'
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: { '@': fileURLToPath(new URL('./src', import.meta.url)) },
  },
  // reference/ tem 144 .html do kit estático: sem isso o Vite varre todos em busca de dependências.
  // No dev eles continuam acessíveis em /reference/<pagina>.html (gabarito de paridade).
  optimizeDeps: { entries: ['index.html'] },
})
