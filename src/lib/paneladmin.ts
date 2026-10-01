// Painéis do game master (ldPanelGameMaster) e do admin (ldPanelAdmin): opções, rotas e o que as telas
// compartilham. O painel do GM usa as mesmas telas do admin para banimentos, denúncias e tickets.
import { computed, inject, type ComputedRef, type InjectionKey } from 'vue'

export type AdminPage = 'panelgamemaster' | 'paneladmin'

// ?page=paneladmin&option=X → /paneladmin-<slug>
export const ADMIN_SLUGS: Record<string, string> = {
  GERATE_BACKUPS: 'gerate-backups', EDIT_ACCOUNT: 'edit-account', DELETE_ACCOUNT: 'delete-account',
  MANAGER_BAN_ACCOUNT: 'manager-ban-account', EDIT_CHARACTER: 'edit-character', DELETE_CHARACTER: 'delete-character',
  MANAGER_BAN_CHARACTER: 'manager-ban-character', SINCRONIZE: 'sincronize', ADD_VIP: 'add-vip', DELETE_VIP: 'delete-vip',
  TRANSFORM_VIP: 'transform-vip', ADD_NOTICE: 'add-notice', REMOVE_NOTICE: 'remove-notice', MODIFY_NOTICE: 'modify-notice',
  ADD_CASH: 'add-cash', REMOVE_CASH: 'remove-cash', DEPOSITS_IN_OPERATION: 'deposits-in-operation',
  DEPOSITS_COMPLETING: 'deposits-completing', DEPOSITS_FALSE: 'deposits-false', COMPLAINTS: 'complaints',
  TICKETS_OPERATION: 'tickets-operation', TICKETS_COMPLETING: 'tickets-completing', VERIFY_UPDATE: 'verify-update',
  ADD_POLL: 'add-poll', REMOVE_POLL: 'remove-poll', MODIFY_POLL: 'modify-poll', GAME_DISCONNECT: 'game-disconnect',
  GAME_MSG_SPECIFIC: 'game-msg-specific', GAME_MSG_ALL: 'game-msg-all', GAME_CHAT_SERVER: 'game-chat-server',
  MANAGER_ACCOUNTS_TRANSFER_CASH: 'manager-accounts-transfer-cash', GOLDEN_ARCHER: 'golden-archer',
  ADD_AUCTIONS: 'add-auctions', EDIT_AUCTIONS: 'edit-auctions', DELETE_AUCTIONS: 'delete-auctions', CLOSE_AUCTIONS: 'close-auctions',
}

// as opções do ldPanelGameMaster (as demais caem no AdminCenter)
export const GM_OPTIONS = ['MANAGER_BAN_ACCOUNT', 'MANAGER_BAN_CHARACTER', 'COMPLAINTS', 'TICKETS_OPERATION', 'TICKETS_COMPLETING']

// checkPermission: GM a partir do privilégio 1, admin só com 2
export const REQUIRED_PRIVILEGE: Record<AdminPage, 1 | 2> = { panelgamemaster: 1, paneladmin: 2 }

export function adminPath(page: AdminPage, option?: string): string {
  return option ? `/${page}-${ADMIN_SLUGS[option]}` : `/${page}`
}

export function adminOptionFromSlug(page: AdminPage, slug: string): string | undefined {
  const option = Object.keys(ADMIN_SLUGS).find((k) => ADMIN_SLUGS[k] === slug)
  return option && (page === 'paneladmin' || GM_OPTIONS.includes(option)) ? option : undefined
}

// qual painel abriu a tela (as telas compartilhadas montam os links com ?page= do painel)
export const ADMIN_PAGE: InjectionKey<ComputedRef<AdminPage>> = Symbol('adminPage')
export const useAdminPage = (): ComputedRef<AdminPage> => inject(ADMIN_PAGE, computed(() => 'paneladmin'))

// is_numeric do PHP
export const isNumeric = (value: string) => /^\s*[+-]?(\d+\.?\d*|\.\d+)(e[+-]?\d+)?\s*$/i.test(value)
// empty() do PHP para os campos de formulário
export const isEmpty = (value: string | undefined | null) => !value || value === '0'

// Arquivos enviados no painel do usuário (denúncia, comprovante) ficam em data URL no banco; os de exemplo,
// em public/images/<pasta>/.
export function uploadUrl(folder: 'complaints' | 'deposits', file: string): string {
  return file.startsWith('data:') ? file : `/images/${folder}/${file}`
}

// target="_blank" não abre data URL (o navegador bloqueia): abre como blob
export function openUpload(e: MouseEvent, url: string) {
  if (!url.startsWith('data:')) return
  e.preventDefault()
  void fetch(url).then((r) => r.blob()).then((blob) => window.open(URL.createObjectURL(blob), '_blank'))
}
