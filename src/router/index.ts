import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '@/views/HomeView.vue'

// Rotas = nome da página do kit sem ".html" (reference/<rota>.html é o gabarito de cada uma).
// Parâmetros que eram querystring no original continuam na query (?id=, ?name=, ?room=).
const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', component: HomeView },
    { path: '/contact', component: () => import('@/views/ContactView.vue') },
    { path: '/:pathMatch(.*)*', component: () => import('@/views/NotMigratedView.vue') },
  ],
  scrollBehavior: () => ({ top: 0 }),
})

export default router
