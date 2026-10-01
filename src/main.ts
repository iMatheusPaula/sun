// CSS global primeiro: os <style> dos componentes entram depois dele, como os <style> inline do original
import './styles/common.css'
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'
import { useDbStore } from './stores/db'

createApp(App).use(createPinia()).use(router).mount('#app')

// dev: __resetDb() no console volta o banco do front aos dados de exemplo
if (import.meta.env.DEV) Object.assign(window, { __resetDb: () => useDbStore().reset() })
