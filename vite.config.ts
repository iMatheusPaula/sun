import { fileURLToPath, URL } from 'node:url'
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  // whitespace 'preserve': o espaço entre elementos em linha (ex.: <strong>Período: </strong> <select>) faz
  // parte do layout, como no HTML original; o padrão do Vue ('condense') o remove e desloca os elementos.
  plugins: [vue({ template: { compilerOptions: { whitespace: 'preserve' } } })],
  resolve: {
    alias: { '@': fileURLToPath(new URL('./src', import.meta.url)) },
  },
  // reference/ tem 144 .html do kit estático: sem isso o Vite varre todos em busca de dependências.
  // No dev eles continuam acessíveis em /reference/<pagina>.html (gabarito de paridade).
  optimizeDeps: { entries: ['index.html'] },
})
