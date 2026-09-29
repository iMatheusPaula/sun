// Dados de exemplo da home — os mesmos do kit (reference/_build/render.php, ldHome/ldAjax).
import { CHAT_MSG_CHAT, CHAT_MSG_GUILD, CHAT_MSG_PARTY, CHAT_MSG_POST } from '@/lang/ldajax'

// $FORUM_CONFIGS['LINKS_TOPICS'] = '#topic-%d' no kit
export const forumTopics = [
  { id: 912, title: 'Guia de reset para iniciantes' },
  { id: 910, title: 'Recrutamento guild Legends' },
  { id: 905, title: 'Bug no Chaos Castle? [resolvido]' },
  { id: 899, title: 'Sugestão: evento de drop no sábado' },
  { id: 897, title: 'Vendo set Dark Phoenix +13' },
]
export const forumLink = '#'

export type ChatType = 'chat' | 'guild' | 'post' | 'party' | 'whisper' | 'aliance'
export const chatMessages: { type: ChatType; label: string; time: string; name: string; text: string }[] = [
  { type: 'chat', label: CHAT_MSG_CHAT, time: '20:41', name: 'DarkLord', text: 'alguém pra BC 7?' },
  { type: 'guild', label: CHAT_MSG_GUILD, time: '20:42', name: 'Kira', text: 'reunião da guild às 21h' },
  { type: 'post', label: CHAT_MSG_POST, time: '20:43', name: 'Zeus', text: 'vendo Bone Blade +13 @ Lorencia' },
  { type: 'party', label: CHAT_MSG_PARTY, time: '20:44', name: 'Elfa', text: 'bora Kalima 6' },
  { type: 'chat', label: CHAT_MSG_CHAT, time: '20:45', name: 'Mago', text: 'gg' },
]

// ldHome::loadTop*: ORDER BY de uma coluna só (empates em ordem indefinida) — fixos como no kit.
// Quando o painel passar a alterar os personagens, derivar de @/lib/rankings.
export interface TopList {
  title: string
  ranking: string
  entries: [name: string, value: number][]
}

// 2 linhas de 3 tops (ldHome::loadTops)
export const topLists: TopList[][] = [
  [
    { title: 'Top Resets:', ranking: '/rankings', entries: [['DarkLord', 412], ['Kira', 398], ['Zeus', 371], ['Elfa', 350]] },
    { title: 'Top Rst. Semanal:', ranking: '/rankings-semanal', entries: [['Mago', 28], ['DarkLord', 25], ['Summoner', 22], ['Kira', 19]] },
    { title: 'Top Rst. Mensal:', ranking: '/rankings-mensal', entries: [['DarkLord', 96], ['Mago', 90], ['Zeus', 84], ['Elfa', 77]] },
  ],
  [
    { title: 'Top Master Resets:', ranking: '/rankings-master-resets', entries: [['Zeus', 7], ['DarkLord', 6], ['Kira', 5], ['Mago', 4]] },
    { title: 'Top Pk:', ranking: '/rankings-pk', entries: [['Assassin', 1204], ['DarkLord', 987], ['Zeus', 812], ['Kira', 640]] },
    { title: 'Top Level:', ranking: '/rankings-level', entries: [['Summoner', 400], ['Elfa', 400], ['Mago', 398], ['Kira', 395]] },
  ],
]

// ldHome::loadCastleSiege: guild dona do castelo (MuCastle_DATA)
export const castleOwner = 'Legends'

export const events = [
  { image: '/images/event/siege.gif', name: 'Castle Siege' },
  { image: '/images/event/blood.gif', name: 'Blood Castle' },
  { image: '/images/event/devil.gif', name: 'Devil Square' },
  { image: '/images/event/chaos.gif', name: 'Chaos Castle' },
  { image: '/images/event/kalima.gif', name: 'Kalima' },
]

export const trailerUrl = 'https://www.youtube-nocookie.com/embed/-Ct9UxNDHGQ'
