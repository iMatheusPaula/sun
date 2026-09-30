// Conteúdo fixo da moldura (header e sidebar) — o mesmo do kit (reference/_build/render.php). Contadores,
// enquete e equipe vêm do banco do front (@/data/seed).
import { SERVER_BUGBLESS, SERVER_DROP, SERVER_VERSION, SERVER_XP } from '@/config/settings'

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

// caixa SERVIDORES ($Config_Find_Rooms_ConnectServer ligado no kit): a lotação de cada sala vinha do
// ConnectServer do jogo (ldCS, por socket), não do banco — valores de exemplo
export const servers = [
  { name: 'Server 1', percent: 63 },
  { name: 'Server 2', percent: 21 },
]

// caixa CONFIGURAÇÕES
export const serverConfigs: [label: string, value: string][] = [
  ['Versão', SERVER_VERSION],
  ['Experiência', SERVER_XP],
  ['Drops', SERVER_DROP],
  ['Bug Bless', SERVER_BUGBLESS],
]

// AdminCenter: o que optionLoadAdminCenter lia do servidor web ($_ENV['OS'], SERVER_SOFTWARE, SERVER_ADMIN).
// Sem servidor, os valores de exemplo do kit.
export const serverEnv = { os: 'Windows_NT', software: 'Apache/2.2.17 (Win32) PHP/5.2.17', admin: 'admin@example.com' }
