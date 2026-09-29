// Dados de exemplo da home — os mesmos do kit (reference/_build/render.php, ldHome/ldAjax).

// Imagens que o servidor gerava (logo de guild, thumbs de screenshot) → SVG inline, como no kit (A10)
export function svg(body: string, w: number, h: number): string {
  return 'data:image/svg+xml;utf8,' + encodeURIComponent(
    `<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 ${w} ${h}' width='${w}' height='${h}'>${body}</svg>`,
  )
}

export const guildLogos = [
  svg("<rect width='8' height='8' fill='#e8e2cf'/><path fill='#b4322e' d='M1 1h6v1H1zM1 6h6v1H1z'/><path fill='#333' d='M3 2h2v4H3z'/>", 8, 8),
  svg("<rect width='8' height='8' fill='#222'/><path fill='#fcda96' d='M2 2h4v4H2z'/><path fill='#222' d='M3 3h2v2H3z'/>", 8, 8),
  svg("<rect width='8' height='8' fill='#4682b4'/><path fill='#fff' d='M0 0h4v4H0zM4 4h4v4H4z'/>", 8, 8),
  svg("<rect width='8' height='8' fill='#76f6c5'/><path fill='#291f12' d='M1 1h2v2H1zM5 1h2v2H5zM1 5h2v2H1zM5 5h2v2H5z'/>", 8, 8),
]

export const screenshotThumb = svg("<rect width='90' height='68' fill='#343336'/><text x='45' y='38' font-family='Arial' font-size='9' fill='#c5c0ad' text-anchor='middle'>screenshot</text>", 90, 68)

export const latestNews = [
  { id: 100, title: 'Servidor atualizado para a versão 2.5.4', date: '10/09/2026 8:15 pm' },
  { id: 99, title: 'Castle Siege neste domingo às 20h', date: '08/09/2026 6:40 pm' },
  { id: 98, title: 'Novo sistema de VIP disponível', date: '05/09/2026 11:02 am' },
  { id: 97, title: 'Manutenção programada: sexta 03h', date: '02/09/2026 9:30 pm' },
  { id: 96, title: 'Evento de drop de Jewels ativado', date: '30/08/2026 4:12 pm' },
]

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
  { type: 'chat', label: 'Chat', time: '20:41', name: 'DarkLord', text: 'alguém pra BC 7?' },
  { type: 'guild', label: 'Guild', time: '20:42', name: 'Kira', text: 'reunião da guild às 21h' },
  { type: 'post', label: 'Post', time: '20:43', name: 'Zeus', text: 'vendo Bone Blade +13 @ Lorencia' },
  { type: 'party', label: 'Party', time: '20:44', name: 'Elfa', text: 'bora Kalima 6' },
  { type: 'chat', label: 'Chat', time: '20:45', name: 'Mago', text: 'gg' },
]

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

export const topGuilds = [
  { name: 'Legends', logo: guildLogos[0], score: 5320 },
  { name: 'Immortals', logo: guildLogos[1], score: 4875 },
  { name: 'Phoenix', logo: guildLogos[2], score: 4110 },
  { name: 'Valhalla', logo: guildLogos[3], score: 3902 },
]

export const castleSiege = { owner: 'Legends', schedule: 'Domingo as 19:00h' }

export const screenshots = [
  { id: 1, author: 'DarkLord', rate: 4.5 },
  { id: 2, author: 'Kira', rate: 4.2 },
  { id: 3, author: 'Zeus', rate: 4.0 },
  { id: 4, author: 'Elfa', rate: 3.8 },
  { id: 5, author: 'Mago', rate: 3.5 },
]

export const events = [
  { image: '/images/event/siege.gif', name: 'Castle Siege' },
  { image: '/images/event/blood.gif', name: 'Blood Castle' },
  { image: '/images/event/devil.gif', name: 'Devil Square' },
  { image: '/images/event/chaos.gif', name: 'Chaos Castle' },
  { image: '/images/event/kalima.gif', name: 'Kalima' },
]

export const trailerUrl = 'https://www.youtube-nocookie.com/embed/-Ct9UxNDHGQ'
