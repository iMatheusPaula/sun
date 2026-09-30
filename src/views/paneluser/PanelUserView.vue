<script setup lang="ts">
// ?page=paneluser[&option=X] → /paneluser e /paneluser-<opção>. Como ldPanelUser::__construct: sem sessão
// abre o loginerror; vence o plano vencido; opção desligada → "Opção desativada"; plano sem permissão →
// "Acesso negado"; opção desconhecida → "Minha Conta". Cada página aberta roda o sorteio do evento de
// perguntas, menos as de acesso negado/desativada e a de screenshots (módulo à parte no original).
import { computed, defineAsyncComponent, onUnmounted, ref, watch, type Component } from 'vue'
import { isNavigationFailure, NavigationFailureType, useRoute, useRouter } from 'vue-router'
import { useSessionStore } from '@/stores/session'
import { useQuestionsEventStore } from '@/stores/questionsEvent'
import { optionFromSlug, optionGate, usePanelAccount } from '@/lib/paneluser'
import PanelUserLayout from '@/layouts/PanelUserLayout.vue'
import LoginErrorView from '@/views/LoginErrorView.vue'
import HomeView from './HomeView.vue'

const props = defineProps<{ option: string }>()
const route = useRoute()
const session = useSessionStore()
const { account, checkVipDate } = usePanelAccount()

const lazy = (load: () => Promise<{ default: Component }>) => defineAsyncComponent(load)
const VIEWS: Record<string, Component> = {
  MODIFY_DATA: lazy(() => import('./ModifyDataView.vue')),
  CLEAN_VAULT: lazy(() => import('./CleanVaultView.vue')),
  DOUBLE_VAULT: lazy(() => import('./DoubleVaultView.vue')),
  LOG_BUYS: lazy(() => import('./LogBuysView.vue')),
  MODIFY_PERSONALID: lazy(() => import('./ModifyPersonalIdView.vue')),
  GAME_DISCONNECT: lazy(() => import('./GameDisconnectView.vue')),
  TRANSFER_CASH: lazy(() => import('./TransferCashView.vue')),
  COLLECTOR_POINTS: lazy(() => import('./CollectorPointsView.vue')),
  RESET: lazy(() => import('./ResetView.vue')),
  MASTER_RESET: lazy(() => import('./MasterResetView.vue')),
  RESET_TRANSFER: lazy(() => import('./ResetTransferView.vue')),
  CLEAN_PK: lazy(() => import('./CleanPkView.vue')),
  DISTRIBUTE_POINTS: lazy(() => import('./DistributePointsView.vue')),
  MOVE_CHARACTER: lazy(() => import('./MoveCharacterView.vue')),
  CHANGE_NICK: lazy(() => import('./ChangeNickView.vue')),
  CHANGE_CLASS: lazy(() => import('./ChangeClassView.vue')),
  REDISTRIBUTE_POINTS: lazy(() => import('./RedistributePointsView.vue')),
  CLEAN_INVENTORY: lazy(() => import('./CleanInventoryView.vue')),
  MANAGER_PHOTO: lazy(() => import('./ManagerPhotoView.vue')),
  BUY_VIPS: lazy(() => import('./BuyVipsView.vue')),
  CONFIRM_PAYMENT: lazy(() => import('./ConfirmPaymentView.vue')),
  OPEN_COMPLAINTS: lazy(() => import('./OpenComplaintsView.vue')),
  OPEN_TICKET: lazy(() => import('./OpenTicketView.vue')),
  READ_TICKET: lazy(() => import('./ReadTicketView.vue')),
  SEND_SMS: lazy(() => import('./SendSmsView.vue')),
  VIRTUAL_VAULT: lazy(() => import('./VirtualVaultView.vue')),
  AUCTIONS: lazy(() => import('./AuctionsView.vue')),
  GOLDEN_ARCHER: lazy(() => import('./GoldenArcherView.vue')),
  SCREENSHOT: lazy(() => import('./ScreenshotView.vue')),
}

const option = computed(() => optionFromSlug(props.option.replace(/^-/, '')))
const gate = computed(() => (option.value ? optionGate(option.value, account.value?.vip ?? 0) : 'ok'))
const view = computed<Component>(() => (option.value ? VIEWS[option.value] ?? HomeView : HomeView))

watch(() => route.fullPath, () => { if (session.isLogged) checkVipDate() }, { immediate: true })

// clicar de novo no link da tela aberta recarregava a página no original: aqui recria a tela
const reloads = ref(0)
const stop = useRouter().afterEach((_to, _from, failure) => {
  if (isNavigationFailure(failure, NavigationFailureType.duplicated)) reloads.value++
})
onUnmounted(stop)

const questions = useQuestionsEventStore()
watch([() => route.fullPath, reloads], () => {
  questions.pageLoad(session.isLogged && !!account.value && gate.value === 'ok' && option.value !== 'SCREENSHOT')
}, { immediate: true })
</script>

<template>
  <LoginErrorView v-if="!session.isLogged || !account" />
  <PanelUserLayout v-else>
    <template v-if="gate === 'desactive'">
      <h1 style="margin-top: 20px;">Opção desativada</h1>
      <div class="qdestaques" style="margin-bottom: 10px; margin-top: 10px;">Desculpe, mais essa opção está desativada.</div>
    </template>
    <template v-else-if="gate === 'accessdanied'">
      <h1 style="margin-top: 20px;">Acesso negado</h1>
      <div class="qdestaques" style="margin-bottom: 10px; margin-top: 10px;">Você não pode usar essas opções no seu plano atual.</div>
    </template>
    <component :is="view" v-else :key="`${route.fullPath}#${reloads}`" />
  </PanelUserLayout>
</template>
