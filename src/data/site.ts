// Dados de exemplo da moldura (header e sidebar) — os mesmos do kit (reference/_build/render.php).
import { SERVER_BUGBLESS, SERVER_DROP, SERVER_VERSION, SERVER_XP, VIP_NAMES } from '@/config/settings'

export interface MenuItem {
  label: string
  to: string
  external?: boolean
}

// header.tpl.php: SHOPPING_LINK da settings já é "#LinkShop"
export const mainMenu: MenuItem[] = [
  { label: 'Inicio', to: '/' },
  { label: 'Painel', to: '/paneluser' },
  { label: 'Cadastro', to: '/register' },
  { label: 'Downloads', to: '/downloads' },
  { label: 'Rankings', to: '/rankings' },
  { label: 'Vips', to: '/vips' },
  { label: 'Shop', to: '#LinkShop', external: true },
  { label: 'Fale Conosco', to: '/contact' },
]

// caixa SERVIDORES ($Config_Find_Rooms_ConnectServer ligado no kit)
export const servers = [
  { name: 'Server 1', percent: 63 },
  { name: 'Server 2', percent: 21 },
]
export const totalOnline = 184

// caixa CONFIGURAÇÕES
export const serverConfigs: [label: string, value: string][] = [
  ['Versão', SERVER_VERSION],
  ['Experiência', SERVER_XP],
  ['Drops', SERVER_DROP],
  ['Bug Bless', SERVER_BUGBLESS],
]

// caixa INFORMAÇÕES
export const serverInfos = {
  accounts: 3412,
  characters: 7905,
  recordOnline: 412,
  // contas por plano (Vip 1..Vip 5)
  vips: ([87, 34, 12, 9, 4] as const).map((count, i) => [VIP_NAMES[i + 1], count]) as [plan: string, count: number][],
  bannedCharacters: 12,
  bannedAccounts: 5,
}

// caixa EQUIPE (ajax membersstaff)
export const staff = [
  { name: 'Admin', online: true },
  { name: 'GM_Kira', online: false },
  { name: 'GM_Zeus', online: true },
]

// enquete ativa (id 1)
export const poll = {
  id: 1,
  question: 'Qual evento você quer no fim de semana?',
  answers: [
    { id: 1, answer: 'Castle Siege', votes: 58 },
    { id: 2, answer: 'Blood Castle 2x', votes: 31 },
    { id: 3, answer: 'Drop de Jewels', votes: 17 },
  ],
}
