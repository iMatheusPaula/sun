// Notícias e comentários de exemplo (reference/_build/render.php: sampleNotices / noticeComments).

export interface Notice {
  id: number
  subject: string
  date: Date
  content: string // texto; cada \n vira <br /> (o original gravava HTML em base64)
}

export interface NoticeComment {
  id: number
  noticeId: number
  account: string // login de quem comentou
  comment: string
  date: Date
  ip: string
}

export const notices: Notice[] = [
  {
    id: 100, subject: 'Servidor atualizado para a versão 2.5.4', date: new Date(2026, 8, 10, 20, 15, 0),
    content: 'Atualizamos o servidor para a versão 2.5.4 do MuSite.\n\nPrincipais mudanças:\n'
      + '- Novo sistema de bônus no cadastro;\n- Ranking de Gens;\n- Correções no painel do usuário.\n\n'
      + 'Qualquer problema, abra um ticket pelo painel.',
  },
  {
    id: 99, subject: 'Castle Siege neste domingo às 20h', date: new Date(2026, 8, 8, 18, 40, 0),
    content: 'O Castle Siege deste domingo começa às 20h.\n\nAs guilds devem se registrar até sábado.',
  },
  {
    id: 98, subject: 'Novo sistema de VIP disponível', date: new Date(2026, 8, 5, 11, 2, 0),
    content: 'Os planos Vip Plus e Vip Extreme já estão disponíveis no painel.\n\nVeja as vantagens na página de VIPs.',
  },
  {
    id: 97, subject: 'Manutenção programada: sexta 03h', date: new Date(2026, 8, 2, 21, 30, 0),
    content: 'O servidor ficará fora do ar na sexta, das 03h às 05h, para manutenção.',
  },
  {
    id: 96, subject: 'Evento de drop de Jewels ativado', date: new Date(2026, 7, 30, 16, 12, 0),
    content: 'O drop de Jewels está em dobro durante todo o fim de semana.',
  },
]

// ids em ordem de gravação; a lista mostra do mais novo para o mais antigo (ORDER BY id DESC)
export const noticeComments: NoticeComment[] = [
  { id: 1, noticeId: 100, account: 'zeus', comment: 'Testado aqui, tudo ok. Valeu ADM.', date: new Date(2026, 8, 10, 20, 31, 2), ip: '177.8.140.3' },
  { id: 2, noticeId: 100, account: 'novato7', comment: 'O painel ainda dá erro pra mim ao resetar, alguém mais?', date: new Date(2026, 8, 10, 20, 48, 30), ip: '201.55.9.87' },
  { id: 3, noticeId: 100, account: 'kira01', comment: 'Boa! Finalmente o ranking de gens.\nParabéns pela atualização.', date: new Date(2026, 8, 10, 21, 5, 12), ip: '187.10.22.1' },
]

// AccountCharacter.GameIDC das contas que comentam e não estão em @/data/accounts
export const lastCharacterOf: Record<string, string> = { zeus: 'Zeus', kira01: 'Kira' }
