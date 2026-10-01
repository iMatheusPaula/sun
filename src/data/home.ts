// Conteúdo fixo da home. Tops, chat, castle siege e screenshots vêm do banco do front (@/data/seed).

// Últimas do fórum: o fórum é outro sistema (banco MySQL/MSSQL do IPB/vBulletin/phpBB); tópicos de exemplo.
// $FORUM_CONFIGS['LINKS_TOPICS'] de exemplo: '#topic-%d'
export const forumTopics = [
  { id: 912, title: 'Guia de reset para iniciantes' },
  { id: 910, title: 'Recrutamento guild Legends' },
  { id: 905, title: 'Bug no Chaos Castle? [resolvido]' },
  { id: 899, title: 'Sugestão: evento de drop no sábado' },
  { id: 897, title: 'Vendo set Dark Phoenix +13' },
]
export const forumLink = '#'

export const events = [
  { image: '/images/event/siege.gif', name: 'Castle Siege' },
  { image: '/images/event/blood.gif', name: 'Blood Castle' },
  { image: '/images/event/devil.gif', name: 'Devil Square' },
  { image: '/images/event/chaos.gif', name: 'Chaos Castle' },
  { image: '/images/event/kalima.gif', name: 'Kalima' },
]

export const trailerUrl = 'https://www.youtube-nocookie.com/embed/-Ct9UxNDHGQ'
