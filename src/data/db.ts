// Tabelas do "banco" do front: as do MuSite/MuServer que o site lê e altera (o nome original está em cada uma).
// Os dados de exemplo ficam em ./seed. Datas em milissegundos (Date.getTime()), para o estado ir para JSON.

// webPrevilegy: 0 = usuário, 1 = game master, 2 = admin
export type Privilege = 0 | 1 | 2

// MEMB_INFO + MEMB_STAT + webVips + webCash + webPrevilegy + AccountCharacter (uma linha por conta)
export interface AccountRow {
  login: string
  password: string
  privilege: Privilege // webPrevilegy
  name: string
  tel: string
  email: string
  question: string
  answer: string
  personalId: string // sno__numb
  blocked: boolean // bloc_code
  activationHash: string | null
  resale: boolean // liberada para transferir SGolds/Golds
  createdAt: string
  birthday: string
  online: boolean // MEMB_STAT.ConnectStat
  connectTM: string
  ip: string
  server: string
  vip: number
  vipBegin: number
  vipEnd: number
  cash: number
  cash2: number
  points: number
  lastCharacter?: string // AccountCharacter.GameIDC: último personagem usado
}

export interface CharacterRow {
  account: string
  name: string
  class: number
  level: number
  experience: number
  resets: number
  masterResets: number
  resetsWeek: number
  resetsMonth: number
  money: number
  points: number // LevelUpPoint
  strength: number
  dexterity: number
  vitality: number
  energy: number
  leadership: number
  pkCount: number
  pkLevel: number
  pkTime: number
  pkCountWeb: number // COLUMN_PK_RANKING: mortes do ranking de PK (negativo = herói)
  map: number
  x: number
  y: number
  ctlCode: number // 1 = banido; 8 e 32 = equipe (fora dos rankings)
  image: string // foto enviada no painel
}

// Guild
export interface GuildRow {
  name: string
  master: string
  score: number
  mark: number // G_Mark: índice do logo de exemplo (@/data/placeholders guildLogos)
}

// GuildMember. G_Status: 128 = Guild Master, 64 = Assistente, 32 = Battle Master, 0 = membro
export interface GuildMemberRow {
  guild: string
  name: string
  level: number // G_Level (1 = mestre em servidores sem G_Status)
  status: 0 | 32 | 64 | 128
}

// Gens_Rank (X-Team): família 1 = Duprian, 2 = Vanert
export interface GensRow {
  name: string
  family: 1 | 2
  contribution: number
}

// item do jogo: categoria/índice do item.txt + opções
export interface ItemOptions {
  cat: number
  idx: number
  level: number
  option: number
  skill: boolean
  luck: boolean
  excellent: boolean[]
  // opções que só os seriais criados pelo admin (Golden Archer) trazem
  ancient?: number // 0 não, 1 ou 2
  refine?: boolean
  harmony?: [type: number, level: number]
  sockets?: number[] // 5 slots; 255 sem socket, 254 vazio
}

export interface ItemData extends ItemOptions {
  durability: number
  serial: number
}

export interface VaultItem extends ItemData {
  slot: number // 0..119 (8 colunas × 15 linhas)
}

export interface Warehouse {
  account: string
  money: number
  items: VaultItem[]
  items2: VaultItem[] // baú alternativo (Baú Duplo)
  dbVersion: number
}

export interface VirtualVaultItem extends ItemData {
  account: string
  number: number
}

// webBanneds
export interface BanRow {
  name: string
  type: 1 | 2 // 1 conta, 2 personagem
  begin: number
  end: number
  by: string
  description: string
}

export interface Ticket {
  id: number
  account: string
  character: string
  sector: string
  subject: string
  description: string
  date: number
  ip: string
  status: 0 | 1 // 0 aberto, 1 encerrado
}

export interface TicketAnswer {
  ticketId: number
  description: string
  answerBy: string // "Suporte" ou "Player"
  date: number
}

export interface Complaint {
  id: number
  account: string
  image: string
  description: string
  date: number
  ip: string
  status: number
}

// webLogBuyCash: comprovantes de depósito (Confirmar Pagamento / Log de compras de SGolds)
export interface Deposit {
  id: number
  account: string
  cash: number
  bank: string
  fields: Record<string, string> // dados do comprovante, que variam por banco
  date: string
  hour: string
  paidAt: string
  attachment: string
  comment: string
  amount: string
  adminComment: string
  status: 0 | 1 | 2 // 0 em andamento, 1 concluído, 2 rejeitado
}

// webLogBuyVips
export interface VipPurchase {
  id: number
  account: string
  type: number
  days: number
  cashAmount: number
  date: number
}

// webScreenshots: sw × sy e cw × cy são os tamanhos gravados das duas versões (miniatura e "common")
export interface PanelScreenshot {
  id: number
  image: string
  account: string // uploadBy
  date: number
  rate: number
  legend: string
  sw: number
  sy: number
  cw: number
  cy: number
  // enviada pelo painel: as duas versões que o módulo gravava (JPEG em data URL); sem elas, placeholders
  files?: { small: string; common: string }
}

export interface Auction {
  id: number
  name: string
  premium: string
  start: number
  end: number
  active: boolean
  minimumBid: number
  closed: boolean
  winner: string
  winnerText: string
  photo?: { small: string; large: string } // enviada pelo admin (data URL); sem ela, public/images/auctions/<id>.*.jpg
}

export interface AuctionBid {
  auction: number
  account: string
  amount: number
  lastBidDay: string
  lastBidHour: string
}

export interface GoldenArcherSerial {
  id: number
  account: string
  serial: [string, string, string]
  status: 0 | 1 // 1 = já registrado
  item: ItemOptions
}

export interface Question {
  number: number
  question: string
  answers: [string, string, string, string]
  correct: number
  type: 'A' | 'B' | 'C' | 'D'
}

// webNotices: conteúdo em HTML (o admin escreve HTML, como no original)
export interface NoticeRow {
  id: number
  subject: string
  content: string
  date: number
}

// comentários das notícias (ajax noticecomment)
export interface NoticeCommentRow {
  id: number
  noticeId: number
  account: string
  comment: string
  date: number
  ip: string
}

// webPollQuestions / webPollAnswers
export interface PollRow {
  id: number
  question: string
  active: boolean
}

export interface PollAnswerRow {
  idPoll: number
  idAnswer: number
  answer: string
  votes: number
}

// webChatServer (chat da home e Ver Chat em tempo real do admin): mensagem já decifrada
export interface ChatRow {
  id: number
  day: string // CONVERT(varchar, dateSay, 103)
  hour: string // CONVERT(varchar, dateSay, 108)
  server: string
  type: number // 0 chat, 1 party, 2 guild, 3 global, 4 anúncio de guild, 5 sussurro, 6 aliança, 7 GM, 8 post
  name: string
  message: string
  destiny: string
}

export interface Db {
  accounts: AccountRow[]
  characters: CharacterRow[]
  guilds: GuildRow[]
  guildMembers: GuildMemberRow[]
  gens: GensRow[]
  castle: { occupied: boolean; owner: string } // MuCastle_DATA: CASTLE_OCCUPY e OWNER_GUILD
  record: { online: number; date: number } // webRecord: recorde de contas online
  warehouses: Warehouse[]
  virtualVault: VirtualVaultItem[]
  bans: BanRow[]
  tickets: Ticket[]
  ticketAnswers: TicketAnswer[]
  complaints: Complaint[]
  deposits: Deposit[]
  vipPurchases: VipPurchase[]
  screenshots: PanelScreenshot[]
  auctions: Auction[]
  bids: AuctionBid[]
  goldenArcher: GoldenArcherSerial[]
  questions: Question[]
  notices: NoticeRow[]
  noticeComments: NoticeCommentRow[]
  polls: PollRow[]
  pollAnswers: PollAnswerRow[]
  chat: ChatRow[]
}

// conta sem os dados opcionais (as de exemplo e as criadas no cadastro)
export function blankAccount(a: Pick<AccountRow, 'login' | 'password' | 'privilege' | 'email' | 'question' | 'answer'> & Partial<AccountRow>): AccountRow {
  return {
    name: '', tel: '', personalId: '', blocked: false, activationHash: null, resale: false, createdAt: '', birthday: '',
    online: false, connectTM: '', ip: '', server: '', vip: 0, vipBegin: 0, vipEnd: 0, cash: 0, cash2: 0, points: 0,
    ...a,
  }
}
