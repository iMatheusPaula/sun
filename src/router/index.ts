import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '@/views/HomeView.vue'
import { RANKING_PAGES } from '@/lib/rankings'

// Rotas = nome da página do kit sem ".html" (reference/<rota>.html é o gabarito de cada uma).
// Parâmetros que eram querystring no original continuam na query (?id=, ?name=, ?room=, ?top=).
const RankingsView = () => import('@/views/RankingsView.vue')
const NoticesView = () => import('@/views/NoticesView.vue')
const ScreenshotsView = () => import('@/views/ScreenshotsView.vue')
const VipsView = () => import('@/views/VipsView.vue')
const BannedView = () => import('@/views/BannedView.vue')
const PanelGateView = () => import('@/views/PanelGateView.vue')

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', component: HomeView },
    { path: '/contact', component: () => import('@/views/ContactView.vue') },
    { path: '/downloads', component: () => import('@/views/DownloadsView.vue') },
    ...RANKING_PAGES.map((p) => ({ path: p.path, component: RankingsView, props: { type: p.type, period: p.period } })),
    { path: '/register', component: () => import('@/views/RegisterView.vue') },
    { path: '/noticias', component: NoticesView },
    { path: '/noticia', component: NoticesView },
    { path: '/screenshots', component: ScreenshotsView, props: { mode: 'all' } },
    { path: '/screenshot', component: ScreenshotsView, props: { mode: 'view' } },
    { path: '/vips', component: VipsView, props: { option: 'index' } },
    { path: '/vips-vantagens', component: VipsView, props: { option: 'advantages' } },
    { path: '/vips-como-comprar', component: VipsView, props: { option: 'howToBuy' } },
    { path: '/vips-como-comprar-vip', component: VipsView, props: { option: 'howToBuyVips' } },
    { path: '/onlines', component: () => import('@/views/OnlinesView.vue') },
    { path: '/banned-personagens', component: BannedView, props: { type: 'characters' } },
    { path: '/banned-contas', component: BannedView, props: { type: 'accounts' } },
    { path: '/recovery', component: () => import('@/views/RecoveryView.vue') },
    { path: '/loginerror', component: () => import('@/views/LoginErrorView.vue') },
    { path: '/loginaccessdanied', component: () => import('@/views/AccessDeniedView.vue') },
    // painéis: /paneluser, /paneluser-<opção>… (ainda não migrados)
    { path: '/paneluser:option(.*)', component: PanelGateView, props: { required: 0 } },
    { path: '/panelgamemaster:option(.*)', component: PanelGateView, props: { required: 1 } },
    { path: '/paneladmin:option(.*)', component: PanelGateView, props: { required: 2 } },
    { path: '/:pathMatch(.*)*', component: () => import('@/views/NotMigratedView.vue') },
  ],
  scrollBehavior: () => ({ top: 0 }),
})

export default router
