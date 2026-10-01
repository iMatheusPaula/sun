// Painel do usuário: opções, permissões e as consultas comuns a várias telas (ldPanelUser).
import { computed } from 'vue'
import { useDbStore } from '@/stores/db'
import { useSessionStore } from '@/stores/session'
import { PANEL_PERMISSIONS, type PanelOption } from '@/config/settings'

// ?page=paneluser&option=X → /paneluser-<slug>
export const OPTION_SLUGS: Record<string, string> = {
  MODIFY_DATA: 'modify-data', CLEAN_VAULT: 'clean-vault', DOUBLE_VAULT: 'double-vault', VIRTUAL_VAULT: 'virtual-vault',
  GAME_DISCONNECT: 'game-disconnect', GOLDEN_ARCHER: 'golden-archer', COLLECTOR_POINTS: 'collector-points', AUCTIONS: 'auctions',
  TRANSFER_CASH: 'transfer-cash', LOG_BUYS: 'log-buys', MODIFY_PERSONALID: 'modify-personalid', RESET: 'reset',
  MASTER_RESET: 'master-reset', RESET_TRANSFER: 'reset-transfer', CLEAN_PK: 'clean-pk', DISTRIBUTE_POINTS: 'distribute-points',
  MOVE_CHARACTER: 'move-character', CHANGE_NICK: 'change-nick', CHANGE_CLASS: 'change-class', REDISTRIBUTE_POINTS: 'redistribute-points',
  CLEAN_INVENTORY: 'clean-inventory', MANAGER_PHOTO: 'manager-photo', BUY_VIPS: 'buy-vips', CONFIRM_PAYMENT: 'confirm-payment',
  OPEN_COMPLAINTS: 'open-complaints', OPEN_TICKET: 'open-ticket', READ_TICKET: 'read-ticket', SEND_SMS: 'send-sms',
  SCREENSHOT: 'screenshot',
}

export function optionPath(option: string): string {
  return `/paneluser-${OPTION_SLUGS[option]}`
}

// links ?page=paneluser&option=X (&chave=valor…) dentro das mensagens do .lang.php → rotas do app
export function panelLinks(html: string): string {
  return html.replace(/\?page=paneluser(?:&(?:amp;)?option=(\w+))?((?:&(?:amp;)?\w+=[^'"&]*)*)/g, (_, option: string | undefined, rest: string) => {
    const query = rest.replace(/&(?:amp;)?/g, '&').replace(/^&/, '')
    return (option ? optionPath(option) : '/paneluser') + (query ? `?${query}` : '')
  })
}

export function optionFromSlug(slug: string): string | undefined {
  return Object.keys(OPTION_SLUGS).find((k) => OPTION_SLUGS[k] === slug)
}

// índice do plano nos arrays da settings: [Free, Vip 1..Vip 5]
export type PlanIndex = 0 | 1 | 2 | 3 | 4 | 5

// ldPanelUser::__construct: opção desligada → DESACTIVE; plano sem permissão → ACCESSDANIED.
// TRANSFER_CASH não passa por essas checagens no original.
export function optionGate(option: string, vip: number): 'ok' | 'desactive' | 'accessdanied' {
  if (option === 'TRANSFER_CASH' || !(option in PANEL_PERMISSIONS)) return 'ok'
  const flags = PANEL_PERMISSIONS[option as PanelOption]
  if (flags[0] === 0) return 'desactive'
  if (flags[vip + 1] !== 1) return 'accessdanied'
  return 'ok'
}

// dados da conta logada e as operações de apoio que as telas usam
export function usePanelAccount() {
  const store = useDbStore()
  const session = useSessionStore()
  const login = computed(() => session.account?.login ?? '')
  const account = computed(() => store.db.accounts.find((a) => a.login === login.value))
  const characters = computed(() => store.db.characters.filter((c) => c.account === login.value))
  const warehouse = computed(() => store.db.warehouses.find((w) => w.account === login.value))
  const plan = computed(() => (account.value?.vip ?? 0) as PlanIndex)

  // exec dbo.webVerifyLogin
  function checkPassword(password: string): boolean {
    return !!account.value && password !== '' && account.value.password === password
  }

  // ldPanelUser::checkVipDate: plano vencido volta a Free
  function checkVipDate() {
    const a = account.value
    if (a && Date.now() > a.vipEnd) a.vip = 0
  }

  return { store, login, account, characters, warehouse, plan, checkPassword, checkVipDate }
}
