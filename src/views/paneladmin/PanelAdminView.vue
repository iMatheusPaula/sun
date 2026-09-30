<script setup lang="ts">
// ?page=panelgamemaster|paneladmin[&option=X] → /panelgamemaster[-<opção>] e /paneladmin[-<opção>]. Como os
// construtores de ldPanelGameMaster e ldPanelAdmin: checkPermission (GM: privilégio 1; admin: 2) vem antes do teste
// de sessão, então sem login também abre o loginaccessdanied; opção desconhecida → AdminCenter.
import { computed, defineAsyncComponent, onUnmounted, provide, ref, type Component } from 'vue'
import { isNavigationFailure, NavigationFailureType, useRoute, useRouter } from 'vue-router'
import { useSessionStore } from '@/stores/session'
import { ADMIN_PAGE, REQUIRED_PRIVILEGE, adminOptionFromSlug, type AdminPage } from '@/lib/paneladmin'
import PanelAdminLayout from '@/layouts/PanelAdminLayout.vue'
import AccessDeniedView from '@/views/AccessDeniedView.vue'
import LoginErrorView from '@/views/LoginErrorView.vue'
import AdminCenterView from './AdminCenterView.vue'

const props = defineProps<{ page: AdminPage; option: string }>()
const route = useRoute()
const session = useSessionStore()
provide(ADMIN_PAGE, computed(() => props.page))

const lazy = (load: () => Promise<{ default: Component }>) => defineAsyncComponent(load)
const Tickets = lazy(() => import('./TicketsView.vue'))
const Vip = lazy(() => import('./ManageVipView.vue'))
const Notices = lazy(() => import('./NoticesAdminView.vue'))
const Polls = lazy(() => import('./PollsAdminView.vue'))
const Cash = lazy(() => import('./CashAdminView.vue'))
const Deposits = lazy(() => import('./DepositsView.vue'))
const GameTools = lazy(() => import('./GameToolsView.vue'))
const Auctions = lazy(() => import('./AuctionsAdminView.vue'))
// opção → tela [+ props]
const VIEWS: Record<string, [Component, Record<string, unknown>?]> = {
  // grupo C: backup, ferramentas do jogo, leilões, atualização e Golden Archer
  GERATE_BACKUPS: [lazy(() => import('./GerateBackupsView.vue'))],
  GAME_MSG_ALL: [GameTools, { tool: 'all' }],
  GAME_MSG_SPECIFIC: [GameTools, { tool: 'specific' }],
  GAME_DISCONNECT: [GameTools, { tool: 'disconnect' }],
  GAME_CHAT_SERVER: [lazy(() => import('./ChatServerView.vue'))],
  ADD_AUCTIONS: [Auctions, { mode: 'add' }],
  EDIT_AUCTIONS: [Auctions, { mode: 'edit' }],
  DELETE_AUCTIONS: [Auctions, { mode: 'remove' }],
  CLOSE_AUCTIONS: [Auctions, { mode: 'close' }],
  VERIFY_UPDATE: [lazy(() => import('./VerifyUpdateView.vue'))],
  GOLDEN_ARCHER: [lazy(() => import('./GoldenArcherAdminView.vue'))],
  // grupo B: vips, notícias, enquetes, SGolds e depósitos
  ADD_VIP: [Vip, { type: 1 }],
  DELETE_VIP: [Vip, { type: 2 }],
  TRANSFORM_VIP: [Vip, { type: 3 }],
  ADD_NOTICE: [Notices, { mode: 'add' }],
  REMOVE_NOTICE: [Notices, { mode: 'remove' }],
  MODIFY_NOTICE: [Notices, { mode: 'modify' }],
  ADD_POLL: [Polls, { mode: 'add' }],
  REMOVE_POLL: [Polls, { mode: 'remove' }],
  MODIFY_POLL: [Polls, { mode: 'modify' }],
  ADD_CASH: [Cash, { type: 1 }],
  REMOVE_CASH: [Cash, { type: 2 }],
  DEPOSITS_IN_OPERATION: [Deposits, { status: 0 }],
  DEPOSITS_COMPLETING: [Deposits, { status: 1 }],
  DEPOSITS_FALSE: [Deposits, { status: 2 }],
  // grupo A: contas, personagens, sincronizador e suporte
  SINCRONIZE: [lazy(() => import('./SincronizeView.vue'))],
  EDIT_ACCOUNT: [lazy(() => import('./EditAccountView.vue'))],
  DELETE_ACCOUNT: [lazy(() => import('./DeleteAccountView.vue'))],
  MANAGER_ACCOUNTS_TRANSFER_CASH: [lazy(() => import('./AccountsTransferCashView.vue'))],
  EDIT_CHARACTER: [lazy(() => import('./EditCharacterView.vue'))],
  DELETE_CHARACTER: [lazy(() => import('./DeleteCharacterView.vue'))],
  MANAGER_BAN_ACCOUNT: [lazy(() => import('./BanAccountView.vue'))],
  MANAGER_BAN_CHARACTER: [lazy(() => import('./BanCharacterView.vue'))],
  COMPLAINTS: [lazy(() => import('./ComplaintsView.vue'))],
  TICKETS_OPERATION: [Tickets, { type: 0 }],
  TICKETS_COMPLETING: [Tickets, { type: 1 }],
}

const option = computed(() => adminOptionFromSlug(props.page, props.option.replace(/^-/, '')))
const view = computed<[Component, Record<string, unknown>?]>(() => (option.value && VIEWS[option.value]) || [AdminCenterView])

// clicar de novo no link da tela aberta recarregava a página no original: aqui recria a tela
const reloads = ref(0)
const stop = useRouter().afterEach((_to, _from, failure) => {
  if (isNavigationFailure(failure, NavigationFailureType.duplicated)) reloads.value++
})
onUnmounted(stop)
</script>

<template>
  <AccessDeniedView v-if="session.privilege < REQUIRED_PRIVILEGE[page]" />
  <LoginErrorView v-else-if="!session.isLogged" />
  <PanelAdminLayout v-else :page="page">
    <component :is="view[0]" v-bind="view[1]" :key="`${route.fullPath}#${reloads}`" />
  </PanelAdminLayout>
</template>
