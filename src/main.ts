// CSS global primeiro: os <style> dos componentes entram depois dele, como os <style> inline do original
import './styles/common.css'
import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import router from './router'

createApp(App).use(createPinia()).use(router).mount('#app')

if (import.meta.env.DEV) import('./dev/parity')
