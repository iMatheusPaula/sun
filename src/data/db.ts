// "Banco" de exemplo do painel: as tabelas do MuSite/MuServer que o painel lê e altera, com os dados do
// FakeDB do kit (reference/_build/pumock.php e paneluser.php) — conta kira01 com Kira, Kirinha e KiraMG.
// Datas em milissegundos (Date.getTime()), para o estado poder ser guardado em JSON.
import { accounts as publicAccounts, type Privilege } from './accounts'

const at = (y: number, m: number, d: number, h = 0, i = 0, s = 0) => new Date(y, m - 1, d, h, i, s).getTime()

// MEMB_INFO + MEMB_STAT + webVips + webCash + AccountCharacter (uma linha por conta)
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
  lastCharacter?: string
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
  map: number
  x: number
  y: number
  ctlCode: number // 1 = banido
  image: string // foto enviada no painel
}

// item do jogo: categoria/índice do item.txt + opções
export interface ItemData {
  cat: number
  idx: number
  level: number
  option: number
  skill: boolean
  luck: boolean
  excellent: boolean[]
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

export interface PanelScreenshot {
  id: number
  image: string
  account: string
  date: number
  rate: number
  legend: string
  // enviada pelo painel: as duas versões que o módulo gravava (JPEG em data URL) e os tamanhos (sw/sy, cw/cy)
  files?: { small: string; common: string; sw: number; sy: number; cw: number; cy: number }
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
  item: Omit<ItemData, 'durability' | 'serial'>
}

export interface Question {
  number: number
  question: string
  answers: [string, string, string, string]
  correct: number
  type: 'A' | 'B' | 'C' | 'D'
}

export interface Db {
  accounts: AccountRow[]
  characters: CharacterRow[]
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
}

// conta sem dados do kit (as de exemplo do site e as criadas no cadastro)
export function blankAccount(a: Pick<AccountRow, 'login' | 'password' | 'privilege' | 'email' | 'question' | 'answer'> & Partial<AccountRow>): AccountRow {
  return {
    name: '', tel: '', personalId: '', blocked: false, activationHash: null, resale: false, createdAt: '', birthday: '',
    online: false, connectTM: '', ip: '', server: '', vip: 0, vipBegin: 0, vipEnd: 0, cash: 0, cash2: 0, points: 0,
    ...a,
  }
}

const noExc = [false, false, false, false, false, false]
// durabilidade como o jogo grava (ldItemMake::ItemGetDurability pelo level/excelente); serial em hexa
const item = (slot: number, cat: number, idx: number, level: number, option: number, skill: boolean, luck: boolean, excellent: boolean[], durability: number, serial: number): VaultItem =>
  ({ slot, cat, idx, level, option, skill, luck, excellent, durability, serial })

// kira01 no plano Vip Gold até 23/10/2026 20:15 (compra de 30 dias em 23/09/2026 20:15)
const KIRA_VIP_END = at(2026, 10, 23, 20, 15)

export function seedDb(): Db {
  const kira = blankAccount({
    login: 'kira01', password: '123456', privilege: 0, email: 'kira01@example.com', question: 'Mascote da sua escola?', answer: 'Rex',
    name: 'Carlos', tel: '(11) 90000-0000', createdAt: '14/03/2025 20:12', birthday: '9/7/1998',
    connectTM: 'Sep 23 2026 7:40PM', ip: '187.10.22.1', server: 'Server 1',
    vip: 2, vipBegin: KIRA_VIP_END - 30 * 86400_000, vipEnd: KIRA_VIP_END, cash: 1500, cash2: 300, points: 85, lastCharacter: 'Kira',
  })
  const others = publicAccounts.filter((a) => a.login !== 'kira01').map((a) => blankAccount(a))

  const char = (c: Partial<CharacterRow> & Pick<CharacterRow, 'name' | 'class' | 'level' | 'resets'>): CharacterRow => ({
    account: 'kira01', experience: 0, masterResets: 0, resetsWeek: 0, resetsMonth: 0, money: 0, points: 0,
    strength: 0, dexterity: 0, vitality: 0, energy: 0, leadership: 0, pkCount: 0, pkLevel: 3, pkTime: 0,
    map: 0, x: 125, y: 125, ctlCode: 0, image: '', ...c,
  })

  return {
    accounts: [
      kira,
      // outras contas do servidor (painel do admin: liberadas para transferir SGolds; online no jogo)
      blankAccount({ login: 'zeus', password: '', privilege: 0, email: 'zeus@example.com', question: '', answer: '', name: 'Rafael', resale: true,
        online: true, connectTM: 'Sep 23 2026 8:02PM', ip: '177.8.140.3', server: 'Server 1', vip: 1, vipEnd: at(2026, 9, 10, 20), cash: 800, points: 210, lastCharacter: 'Zeus' }),
      blankAccount({ login: 'elfa', password: '', privilege: 0, email: 'elfa@example.com', question: '', answer: '', name: 'Juliana', resale: true,
        online: true, connectTM: 'Sep 23 2026 8:15PM', ip: '189.44.12.7', server: 'Server 2', vip: 2, vipEnd: at(2026, 9, 25, 13, 30), cash: 150, cash2: 40, points: 320, lastCharacter: 'Elfa' }),
      ...others,
    ],
    characters: [
      char({ name: 'Kira', class: 17, level: 400, resets: 398, masterResets: 5, money: 185000000, strength: 32767, dexterity: 18000, vitality: 25000,
        energy: 3000, pkCount: 640, pkLevel: 6, points: 1200, resetsWeek: 19, resetsMonth: 70 }),
      char({ name: 'Kirinha', class: 32, level: 180, resets: 12, money: 2500000, strength: 1200, dexterity: 3500, vitality: 900, energy: 800,
        map: 3, x: 174, y: 111 }),
      char({ name: 'KiraMG', class: 48, level: 95, resets: 3, money: 40000, strength: 400, dexterity: 300, vitality: 250, energy: 300, pkCount: 2, ctlCode: 1 }),
    ],
    warehouses: [{
      account: 'kira01', money: 12000000, dbVersion: 3, items2: [],
      items: [
        item(0, 0, 19, 13, 4, true, true, [true, false, true, false, true, false], 92, 0x3a1f1), // Sword of Archangel
        item(1, 7, 1, 11, 3, false, true, [false, true, false, true, false, false], 96, 0x3a1f2), // Dragon Helm
        item(3, 8, 1, 11, 3, false, true, noExc, 81, 0x3a1f3), // Dragon Armor
        item(5, 12, 5, 9, 2, false, true, noExc, 214, 0x3a1f4), // Wings of Dragon
        item(40, 14, 13, 0, 0, false, false, noExc, 1, 0x3a1f5), // Jewel of Bless
        item(41, 14, 14, 0, 0, false, false, noExc, 1, 0x3a1f6), // Jewel of Soul
        item(42, 14, 16, 0, 0, false, false, noExc, 1, 0x3a1f7), // Jewel of Life
        item(43, 14, 16, 0, 0, false, false, noExc, 1, 0x3a1f8), // Jewel of Life
        item(44, 12, 15, 0, 0, false, false, noExc, 1, 0x3a1f9), // Jewel of Chaos
        item(48, 6, 14, 9, 2, true, false, noExc, 74, 0x3a1fa), // Legendary Shield
        item(51, 0, 0, 7, 1, true, false, noExc, 30, 0x3a1fb), // Kris
      ],
    }],
    virtualVault: [
      { account: 'kira01', number: 7, cat: 8, idx: 26, level: 7, option: 2, skill: false, luck: true, excellent: noExc, durability: 70, serial: 0x3b011 }, // Adamantine Armor
      { account: 'kira01', number: 6, cat: 5, idx: 10, level: 15, option: 4, skill: true, luck: true, excellent: [true, true, false, false, true, true], durability: 86, serial: 0x3b012 }, // Staff of Archangel
      { account: 'kira01', number: 3, cat: 14, idx: 22, level: 0, option: 0, skill: false, luck: false, excellent: noExc, durability: 1, serial: 0x3b013 }, // Jewel of Creation
    ],
    bans: [
      { name: 'vendedor01', type: 1, begin: at(2026, 9, 1), end: at(2030, 12, 31), by: 'Admin', description: 'Venda de conta' },
      { name: 'spam_bot', type: 1, begin: at(2026, 8, 25, 10), end: at(2026, 9, 1, 10), by: 'GM_Kira', description: 'Spam' },
      { name: 'H4ck3r', type: 2, begin: at(2026, 9, 1), end: at(2030, 12, 31), by: 'Admin', description: 'Uso de hack' },
      { name: 'Ladrao', type: 2, begin: at(2026, 9, 8, 18), end: at(2026, 9, 15, 18), by: 'GM_Zeus', description: 'Golpe em trade' },
      // KiraMG (CtlCode 1): o painel mostra o aviso com o fim do ban
      { name: 'KiraMG', type: 2, begin: at(2026, 9, 20, 18), end: at(2026, 10, 1, 18), by: 'GM_Kira', description: 'Flood' },
    ],
    tickets: [
      { id: 14, account: 'kira01', character: 'Kira', sector: 'Suporte', subject: 'Personagem preso após o reset',
        description: 'Depois do reset o Kira ficou preso numa parede em Lorencia.\nJá tentei mover pelo painel e não resolveu.', date: at(2026, 9, 21, 19, 5), ip: '187.10.22.1', status: 0 },
      { id: 9, account: 'kira01', character: 'Kirinha', sector: 'Financeiro', subject: 'Doação não creditada',
        description: 'Fiz o depósito ontem e os SGolds ainda não entraram.', date: at(2026, 9, 2, 10, 40), ip: '187.10.22.1', status: 1 },
      { id: 15, account: 'zeus', character: 'Zeus', sector: 'Suporte', subject: 'Item sumiu do baú',
        description: 'Meu Dragon Helm +11 sumiu do baú depois da manutenção.', date: at(2026, 9, 23, 9, 12), ip: '177.8.140.3', status: 0 },
      { id: 12, account: 'elfa', character: 'Elfa', sector: 'Financeiro', subject: 'VIP não ativou',
        description: 'Comprei o Vip Gold e a conta continua Free.', date: at(2026, 9, 12, 16, 45), ip: '189.44.12.7', status: 1 },
    ],
    ticketAnswers: [
      { ticketId: 14, description: 'Olá! Movemos o personagem para Lorencia.\nConfira e responda se continuar.', answerBy: 'Suporte', date: at(2026, 9, 21, 21, 30) },
      { ticketId: 14, description: 'Ainda aparece preso quando entro no jogo.', answerBy: 'Player', date: at(2026, 9, 22, 8, 12) },
    ],
    complaints: [
      { id: 2, account: 'elfa', image: 'elfa[21-09-2026~20-14-02].jpg', description: 'Jogador usando speed hack em Lost Tower.\nPersonagem: H4ck3r.', date: at(2026, 9, 21, 20, 14, 2), ip: '189.44.12.7', status: 0 },
      { id: 1, account: 'kira01', image: 'kira01[18-09-2026~22-30-10].jpg', description: 'Flood no chat global o dia todo.', date: at(2026, 9, 18, 22, 30, 10), ip: '187.10.22.1', status: 0 },
    ],
    deposits: [
      { id: 32, account: 'zeus', cash: 300, bank: 'Loterica', fields: { ncontrole: '778899', nterminal: '12' }, date: '23/09/2026', hour: '10:05',
        paidAt: 'Atendente', attachment: '', comment: 'Paguei na lotérica perto de casa.', amount: 'R$ 0,00', adminComment: '', status: 0 },
      { id: 31, account: 'kira01', cash: 500, bank: 'Banco Bradesco ou Banco Postal', fields: { nterminal: '004512', ntransferencia: '000123', agencia_acolhedora: '1234', nsequencia: '0098' },
        date: '20/09/2026', hour: '14:32', paidAt: 'Auto Atendimento', attachment: 'kira01[20-09-2026~14-32-05].jpg', comment: 'Depósito no banco.\nComprovante número 000123.', amount: 'R$ 50,00', adminComment: '', status: 0 },
      { id: 27, account: 'kira01', cash: 1000, bank: 'Banco Itau', fields: { ctr: '5566', caixa_eletronico: '123456/1234' }, date: '02/09/2026', hour: '09:10',
        paidAt: 'Trans. Eletrônica', attachment: 'kira01[02-09-2026~09-12-40].jpg', comment: 'Transferência feita pelo app.', amount: 'R$ 100,00', adminComment: 'Pagamento confirmado.', status: 1 },
      { id: 19, account: 'kira01', cash: 200, bank: 'Loterica', fields: { ncontrole: '445566', nterminal: '07' }, date: '15/08/2026', hour: '21:47',
        paidAt: 'Atendente', attachment: 'kira01[15-08-2026~21-50-13].jpg', comment: 'Depósito em lotérica.', amount: 'R$ 20,00', adminComment: 'Comprovante ilegível, envie de novo.', status: 2 },
    ],
    vipPurchases: [
      { id: 12, account: 'kira01', type: 2, days: 30, cashAmount: 50, date: at(2026, 9, 23, 20, 15) },
      { id: 9, account: 'kira01', type: 1, days: 30, cashAmount: 30, date: at(2026, 7, 20, 18, 2) },
    ],
    screenshots: [
      { id: 3, image: 'a1b2c3', account: 'kira01', date: at(2026, 9, 14, 21, 10), rate: 12, legend: 'Castle Siege de domingo' },
      { id: 5, image: 'd4e5f6', account: 'kira01', date: at(2026, 9, 18, 18, 2), rate: 4, legend: 'Kira de set novo' },
      { id: 7, image: 'a7b8c9', account: 'kira01', date: at(2026, 9, 20, 22, 40), rate: 0, legend: 'Evento Blood Castle' },
    ],
    auctions: [
      { id: 4, name: 'Leilão de Wings', premium: 'Wings of Satan +11 +16 +Luck, excelente (Absorve 5% do dano)', start: at(2026, 9, 1, 20), end: at(2030, 12, 31, 20), active: true, minimumBid: 50, closed: false, winner: '', winnerText: '' },
      { id: 3, name: 'Set Dragon completo', premium: 'Dragon Helm, Armor, Pants, Gloves e Boots +9 +8', start: at(2030, 1, 10, 20), end: at(2030, 1, 17, 20), active: true, minimumBid: 80, closed: false, winner: '', winnerText: '' },
      { id: 2, name: 'Espada do Arcanjo', premium: 'Sword of Archangel +13 +16 +Luck +Skill', start: at(2026, 9, 5, 20), end: at(2026, 9, 20, 20), active: true, minimumBid: 70, closed: false, winner: '', winnerText: '' },
      { id: 1, name: 'Jóias da semana', premium: '10 Jewel of Bless + 10 Jewel of Soul', start: at(2026, 8, 20, 20), end: at(2026, 8, 27, 20), active: true, minimumBid: 20, closed: true, winner: 'kira01', winnerText: 'As jóias foram entregues no baú da sua conta.' },
    ],
    bids: [
      { auction: 4, account: 'zeus', amount: 75, lastBidDay: '22/09/2026', lastBidHour: '21:14:05' },
      { auction: 4, account: 'kira01', amount: 60, lastBidDay: '21/09/2026', lastBidHour: '19:02:44' },
      { auction: 2, account: 'elfa', amount: 140, lastBidDay: '19/09/2026', lastBidHour: '23:50:10' },
      { auction: 1, account: 'kira01', amount: 45, lastBidDay: '26/08/2026', lastBidHour: '18:31:00' },
    ],
    goldenArcher: [
      { id: 1, account: 'kira01', serial: ['4E6C', '9713', '01F7'], status: 0, item: { cat: 0, idx: 8, level: 5, option: 3, skill: false, luck: true, excellent: noExc } },
      { id: 2, account: 'kira01', serial: ['7F21', 'B0C4', '9E3A'], status: 1, item: { cat: 14, idx: 13, level: 0, option: 0, skill: false, luck: false, excellent: noExc } },
      { id: 3, account: 'zeus', serial: ['55D0', '1A2B', 'C3E9'], status: 0, item: { cat: 12, idx: 15, level: 0, option: 0, skill: false, luck: false, excellent: noExc } },
    ],
    questions: [
      { number: 1, question: 'Qual é o level máximo de um personagem no MU Online?', answers: ['350', '400', '450', '500'], correct: 2, type: 'A' },
    ],
  }
}
