// Dados de exemplo do "banco" do front: a fonte única do que o site mostra — páginas públicas (rankings, tops e
// chat da home, onlines, banidos, screenshots, notícias, contadores da lateral) e painéis. O kit de referência é
// gerado com os mesmos dados: `bun run kit:seed` grava reference/_build/seed.json, que o render.php lê.
// Datas: ou já passaram (antes de 29/09/2026) ou ficam em 2030, para o estado não mudar sozinho com o tempo.
import { blankAccount, type AccountRow, type CharacterRow, type Db, type VaultItem } from './db'

const at = (y: number, m: number, d: number, h = 0, i = 0, s = 0) => new Date(y, m - 1, d, h, i, s).getTime()

// contas de teste que entram no site (kira01, gm01 e admin; a senha é a mesma nas três)
const TEST_PASSWORD = '123456'

// conta de jogador sem login no front (senha vazia nunca confere)
const player = (login: string, a: Partial<AccountRow> = {}): AccountRow =>
  blankAccount({ login, password: '', privilege: 0, email: `${login}@example.com`, question: '', answer: '', ...a })
// MEMB_STAT: conectada agora / última conexão
const online = (connectTM: string, ip: string, server: string) => ({ online: true, connectTM, ip, server })
const offline = (connectTM: string, ip: string, server: string) => ({ online: false, connectTM, ip, server })
// webVips: tipo, início e fim
const vip = (type: number, begin: number, end: number) => ({ vip: type, vipBegin: begin, vipEnd: end })

// level, resets, resets da semana, resets do mês, master resets, mortes do ranking de PK
type Progress = [level: number, resets: number, week: number, month: number, masterResets: number, pkCountWeb: number]
const char = (account: string, name: string, cls: number, [level, resets, resetsWeek, resetsMonth, masterResets, pkCountWeb]: Progress, c: Partial<CharacterRow> = {}): CharacterRow => ({
  account, name, class: cls, level, resets, resetsWeek, resetsMonth, masterResets, pkCountWeb,
  experience: 0, money: 0, points: 0, strength: 0, dexterity: 0, vitality: 0, energy: 0, leadership: 0,
  pkCount: 0, pkLevel: 3, pkTime: 0, map: 0, x: 125, y: 125, ctlCode: 0, image: '', ...c,
})

const noExc = [false, false, false, false, false, false]
// durabilidade como o jogo grava (ldItemMake::ItemGetDurability pelo level/excelente); serial em hexa
const item = (slot: number, cat: number, idx: number, level: number, option: number, skill: boolean, luck: boolean, excellent: boolean[], durability: number, serial: number): VaultItem =>
  ({ slot, cat, idx, level, option, skill, luck, excellent, durability, serial })

export function seedDb(): Db {
  return {
    // ordem das tabelas = ordem de gravação (consultas sem ORDER BY saem nessa ordem)
    accounts: [
      blankAccount({
        login: 'kira01', password: TEST_PASSWORD, privilege: 0, email: 'kira01@example.com', question: 'Mascote da sua escola?', answer: 'Rex',
        name: 'Carlos', tel: '(11) 90000-0000', createdAt: '14/03/2025 20:12', birthday: '9/7/1998',
        ...offline('Sep 23 2026 7:40PM', '187.10.22.1', 'Server 1'),
        ...vip(2, at(2026, 9, 23, 20, 15), at(2030, 10, 23, 20, 15)), cash: 1500, cash2: 300, points: 85, lastCharacter: 'Kira',
      }),
      // zeus e elfa: liberadas para transferir SGolds; vips vencidos esperando o Sincronizar
      player('darklord', { name: 'Marcos', createdAt: '02/01/2025 18:40', ...online('Sep 23 2026 7:02PM', '198.51.100.23', 'Server 1'),
        ...vip(3, at(2026, 8, 1, 12), at(2030, 8, 1, 12)), cash: 2400, points: 540, lastCharacter: 'DarkLord' }),
      player('zeus', { name: 'Rafael', resale: true, ...online('Sep 23 2026 8:02PM', '177.8.140.3', 'Server 1'),
        ...vip(1, at(2026, 8, 11, 20), at(2026, 9, 10, 20)), cash: 800, points: 210, lastCharacter: 'Zeus' }),
      player('elfa', { name: 'Juliana', resale: true, ...online('Sep 23 2026 8:15PM', '189.44.12.7', 'Server 2'),
        ...vip(2, at(2026, 8, 26, 13, 30), at(2026, 9, 25, 13, 30)), cash: 150, cash2: 40, points: 320, lastCharacter: 'Elfa' }),
      player('mago', { ...online('Sep 23 2026 8:31PM', '200.18.4.99', 'Server 1'), lastCharacter: 'Mago' }),
      player('summoner', { ...online('Sep 23 2026 8:36PM', '203.0.113.54', 'Server 2'), ...vip(5, at(2026, 9, 1, 9), at(2030, 9, 1, 9)), cash: 90, lastCharacter: 'Summoner' }),
      player('assassin', { ...offline('Sep 22 2026 11:48PM', '203.0.113.7', 'Server 1'), lastCharacter: 'Assassin' }),
      player('gladius', { ...offline('Sep 21 2026 10:15PM', '198.51.100.40', 'Server 2'), lastCharacter: 'Gladius' }),
      player('lunar', { ...offline('Sep 23 2026 6:10PM', '198.51.100.61', 'Server 1'), ...vip(2, at(2026, 7, 15, 19), at(2030, 7, 15, 19)), lastCharacter: 'Lunar' }),
      player('thorn', { ...offline('Sep 20 2026 9:03PM', '203.0.113.88', 'Server 2'), lastCharacter: 'Thorn' }),
      player('raven', { ...offline('Sep 23 2026 5:47PM', '198.51.100.9', 'Server 1'), ...vip(4, at(2026, 9, 5, 14), at(2030, 9, 5, 14)), lastCharacter: 'Raven' }),
      player('wolf', { ...offline('Sep 19 2026 7:30PM', '203.0.113.21', 'Server 1'), ...vip(1, at(2026, 9, 12, 10), at(2030, 9, 12, 10)), lastCharacter: 'Wolf' }),
      player('nyx', { ...offline('Sep 18 2026 10:41PM', '198.51.100.77', 'Server 2'), lastCharacter: 'Nyx' }),
      player('drako', { ...offline('Sep 22 2026 8:05PM', '203.0.113.130', 'Server 1'), lastCharacter: 'Drako' }),
      player('lyra', { ...offline('Sep 17 2026 4:22PM', '198.51.100.102', 'Server 2'), ...vip(1, at(2026, 8, 20, 16), at(2030, 8, 20, 16)), lastCharacter: 'Lyra' }),
      player('orion', { ...offline('Sep 16 2026 9:58PM', '203.0.113.66', 'Server 1'), lastCharacter: 'Orion' }),
      player('vex', { ...offline('Sep 15 2026 11:12PM', '198.51.100.140', 'Server 2'), lastCharacter: 'Vex' }),
      player('iris', { ...offline('Sep 14 2026 3:35PM', '203.0.113.95', 'Server 1'), lastCharacter: 'Iris' }),
      player('brom', { ...offline('Sep 12 2026 8:47PM', '198.51.100.188', 'Server 1'), lastCharacter: 'Brom' }),
      player('kael', { ...offline('Sep 11 2026 7:19PM', '203.0.113.201', 'Server 2'), lastCharacter: 'Kael' }),
      // conta sem personagem (comenta notícia com o próprio login)
      player('novato7', offline('Sep 10 2026 8:40PM', '201.55.9.87', 'Server 1')),
      // donos dos personagens banidos
      player('h4ck3r', { ...offline('Sep 1 2026 0:12AM', '203.0.113.250', 'Server 2'), lastCharacter: 'H4ck3r' }),
      player('flooder', { ...offline('Sep 23 2026 6:25PM', '198.51.100.230', 'Server 1'), lastCharacter: 'Flooder' }),
      player('trapaceiro', { ...offline('Sep 25 2026 8:51AM', '203.0.113.240', 'Server 1'), lastCharacter: 'Trapaceiro' }),
      // contas banidas (bloc_code 1; o ban da spam_bot já venceu e espera o Sincronizar)
      player('vendedor01', { blocked: true }),
      player('spam_bot', { blocked: true }),
      player('bot_farm', { blocked: true }),
      // equipe: o nome (memb_name) é quem assina os banimentos
      blankAccount({ login: 'admin', password: TEST_PASSWORD, privilege: 2, email: 'admin@example.com', question: 'Nome da sua primeira escola?', answer: 'central',
        name: 'Admin', ...online('Sep 23 2026 8:20PM', '192.0.2.10', 'Server 1'), lastCharacter: 'Admin' }),
      blankAccount({ login: 'gm01', password: TEST_PASSWORD, privilege: 1, email: 'gm01@example.com', question: 'Nome da sua primeira escola?', answer: 'central',
        name: 'GM_Kira', ...offline('Sep 22 2026 10:30PM', '192.0.2.12', 'Server 1'), lastCharacter: 'GM_Kira' }),
      player('gm02', { privilege: 1, name: 'GM_Zeus', ...online('Sep 23 2026 7:55PM', '192.0.2.11', 'Server 2'), lastCharacter: 'GM_Zeus' }),
    ],
    characters: [
      // kira01 (a conta dos testes do painel): offline, Vip Gold, KiraMG banido
      char('kira01', 'Kira', 17, [400, 398, 19, 70, 5, 640], { money: 185000000, points: 1200, strength: 32767, dexterity: 18000, vitality: 25000, energy: 3000,
        pkCount: 640, pkLevel: 6 }),
      char('kira01', 'Kirinha', 32, [180, 12, 0, 0, 0, 0], { money: 2500000, strength: 1200, dexterity: 3500, vitality: 900, energy: 800, map: 3, x: 174, y: 111 }),
      char('kira01', 'KiraMG', 48, [95, 3, 0, 0, 0, 2], { money: 40000, strength: 400, dexterity: 300, vitality: 250, energy: 300, pkCount: 2, ctlCode: 1 }),
      char('darklord', 'DarkLord', 64, [390, 412, 25, 96, 6, 987], { money: 1850000000, points: 1250, strength: 32767, dexterity: 8500, vitality: 12000, energy: 4200,
        leadership: 6100, x: 135, y: 128 }),
      // foto enviada pelo painel (arquivo que não vem no repositório: placeholder)
      char('zeus', 'Zeus', 2, [388, 371, 17, 84, 7, 812], { money: 640000000, points: 460, strength: 1200, dexterity: 9000, vitality: 7000, energy: 32767,
        map: 3, x: 174, y: 111, image: 'zeus_1757541234.jpg' }),
      char('elfa', 'Elfa', 34, [400, 350, 15, 77, 3, -1], { money: 410000000, strength: 4000, dexterity: 32767, vitality: 8000, energy: 6500, x: 147, y: 127 }),
      char('mago', 'Mago', 2, [398, 342, 28, 90, 4, 120], { money: 275000000, points: 1500, strength: 900, dexterity: 7500, vitality: 6200, energy: 32767, map: 29 }),
      char('summoner', 'Summoner', 82, [400, 318, 22, 64, 3, 55], { money: 150000000, points: 300, strength: 1500, dexterity: 6000, vitality: 6000, energy: 30000,
        map: 33, x: 84, y: 13 }),
      char('assassin', 'Assassin', 17, [380, 305, 12, 51, 2, 1204], { money: 98000000, strength: 32767, dexterity: 12000, vitality: 7000, energy: 1500, map: 7, x: 24, y: 17 }),
      char('gladius', 'Gladius', 65, [377, 290, 9, 40, 1, 210], { money: 64000000, points: 2100, strength: 25000, dexterity: 9000, vitality: 8000, energy: 3000,
        leadership: 12000, map: 8, x: 194, y: 67 }),
      char('lunar', 'Lunar', 34, [386, 276, 6, 33, 1, -1], { money: 31000000, points: 40, strength: 3000, dexterity: 30000, vitality: 7000, energy: 5000, map: 51, x: 51, y: 216 }),
      char('thorn', 'Thorn', 48, [365, 251, 3, 21, 0, 33], { money: 12000000, strength: 20000, dexterity: 10000, vitality: 6000, energy: 9000, map: 4, x: 204, y: 77 }),
      char('raven', 'Raven', 64, [360, 240, 2, 18, 0, -1], { money: 8000000, strength: 18000, dexterity: 6000, vitality: 5000, energy: 2000, leadership: 20000,
        map: 34, x: 228, y: 41 }),
      char('wolf', 'Wolf', 17, [352, 222, 2, 15, 0, -1], { money: 5000000, points: 150, strength: 22000, dexterity: 9000, vitality: 7000, energy: 1000, map: 41, x: 28, y: 76 }),
      char('nyx', 'Nyx', 81, [347, 205, 1, 12, 0, -1], { money: 3200000, strength: 1200, dexterity: 5000, vitality: 5000, energy: 22000, map: 42, x: 97, y: 185 }),
      char('drako', 'Drako', 18, [340, 190, 1, 10, 0, -1], { money: 2500000, points: 75, strength: 20000, dexterity: 8000, vitality: 6000, energy: 900, map: 37, x: 20, y: 218 }),
      char('lyra', 'Lyra', 33, [331, 170, 1, 8, 0, -1], { money: 1900000, strength: 2000, dexterity: 21000, vitality: 5000, energy: 3000, map: 30, x: 93, y: 214 }),
      char('orion', 'Orion', 1, [320, 150, 0, 6, 0, -1], { money: 1200000, points: 200, strength: 800, dexterity: 4000, vitality: 4000, energy: 19000, map: 10 }),
      char('vex', 'Vex', 96, [312, 120, 0, 5, 0, -1], { money: 900000, strength: 15000, dexterity: 6000, vitality: 8000, energy: 1000, map: 56, x: 135, y: 105 }),
      char('iris', 'Iris', 34, [305, 90, 0, 3, 0, -1], { money: 600000, points: 30, strength: 1500, dexterity: 14000, vitality: 3000, energy: 2000, map: 63, x: 120, y: 129 }),
      char('brom', 'Brom', 16, [298, 60, 0, 2, 0, -1], { money: 300000, strength: 11000, dexterity: 4000, vitality: 3000, energy: 500, map: 1, x: 107, y: 247 }),
      char('kael', 'Kael', 49, [280, 30, 0, 1, 0, -1], { money: 120000, points: 90, strength: 8000, dexterity: 3000, vitality: 2000, energy: 4000, map: 6, x: 62, y: 115 }),
      // banidos (CtlCode 1; o ban do Ladrao já venceu e espera o Sincronizar)
      char('h4ck3r', 'H4ck3r', 33, [250, 40, 0, 4, 0, 45], { money: 50000, strength: 900, dexterity: 9000, vitality: 2000, energy: 1500, map: 4, x: 204, y: 77, ctlCode: 1 }),
      char('flooder', 'Flooder', 1, [150, 5, 0, 1, 0, 0], { money: 12000, strength: 300, dexterity: 900, vitality: 800, energy: 4000, ctlCode: 1 }),
      char('trapaceiro', 'Trapaceiro', 17, [200, 20, 0, 2, 0, 12], { money: 800000, strength: 6000, dexterity: 2500, vitality: 2000, energy: 400, map: 2, x: 197, y: 46, ctlCode: 1 }),
      char('trapaceiro', 'Ladrao', 96, [120, 8, 0, 0, 0, 3], { money: 90000, strength: 3000, dexterity: 1500, vitality: 1200, energy: 300, map: 3, x: 174, y: 111, ctlCode: 1 }),
      // equipe (CtlCode 8 ou 32: caixa EQUIPE, fora dos rankings)
      char('admin', 'Admin', 2, [400, 0, 0, 0, 0, 0], { ctlCode: 32 }),
      char('gm01', 'GM_Kira', 17, [400, 0, 0, 0, 0, 0], { ctlCode: 32 }),
      char('gm02', 'GM_Zeus', 34, [400, 0, 0, 0, 0, 0], { ctlCode: 8 }),
    ],
    guilds: [
      { name: 'Legends', master: 'DarkLord', score: 5320, mark: 0 },
      { name: 'Immortals', master: 'Assassin', score: 4875, mark: 1 },
      { name: 'Phoenix', master: 'Lunar', score: 4110, mark: 2 },
      { name: 'Valhalla', master: 'Raven', score: 3902, mark: 3 },
      { name: 'Tempest', master: 'Wolf', score: 2750, mark: 0 },
      { name: 'Nomads', master: 'Drako', score: 1180, mark: 1 },
    ],
    // guild, personagem, G_Level, G_Status
    guildMembers: ([
      ['Legends', 'DarkLord', 1, 128], ['Legends', 'Kira', 0, 64], ['Legends', 'Zeus', 0, 32], ['Legends', 'Elfa', 0, 0], ['Legends', 'Mago', 0, 0], ['Legends', 'Summoner', 0, 0],
      ['Immortals', 'Assassin', 1, 128], ['Immortals', 'Gladius', 0, 64],
      ['Phoenix', 'Lunar', 1, 128], ['Phoenix', 'Thorn', 0, 64],
      ['Valhalla', 'Raven', 1, 128], ['Valhalla', 'Lyra', 0, 0],
      ['Tempest', 'Wolf', 1, 128], ['Tempest', 'Nyx', 0, 64], ['Tempest', 'Orion', 0, 0],
      ['Nomads', 'Drako', 1, 128], ['Nomads', 'Vex', 0, 0],
    ] as const).map(([guild, name, level, status]) => ({ guild, name, level, status })),
    gens: [
      ...([['DarkLord', 245800], ['Kira', 180400], ['Zeus', 152000], ['Elfa', 121500], ['Mago', 104300],
        ['Summoner', 88200], ['Assassin', 41000], ['Gladius', 12750], ['Lunar', 3200], ['Thorn', 640]] as const)
        .map(([name, contribution]) => ({ name, family: 1 as const, contribution })),
      ...([['Raven', 212400], ['Wolf', 160900], ['Nyx', 131000], ['Drako', 99800], ['Lyra', 64200],
        ['Orion', 38500], ['Vex', 16100], ['Iris', 4999], ['Brom', 1500], ['Kael', 320]] as const)
        .map(([name, contribution]) => ({ name, family: 2 as const, contribution })),
    ],
    castle: { occupied: true, owner: 'Legends' },
    record: { online: 12, date: at(2026, 9, 6, 21) },
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
    // vigentes até 2030 e dois vencidos (spam_bot e Ladrao) para o Sincronizar
    bans: [
      { name: 'spam_bot', type: 1, begin: at(2026, 8, 25, 10), end: at(2026, 9, 1, 10), by: 'GM_Kira', description: 'Spam' },
      { name: 'vendedor01', type: 1, begin: at(2026, 9, 1), end: at(2030, 12, 31), by: 'Admin', description: 'Venda de conta' },
      { name: 'H4ck3r', type: 2, begin: at(2026, 9, 1), end: at(2030, 12, 31), by: 'Admin', description: 'Uso de hack' },
      { name: 'Ladrao', type: 2, begin: at(2026, 9, 8, 18), end: at(2026, 9, 15, 18), by: 'GM_Zeus', description: 'Roubo de itens' },
      { name: 'bot_farm', type: 1, begin: at(2026, 9, 15, 12), end: at(2030, 10, 15, 12), by: 'GM_Kira', description: 'Uso de bot' },
      { name: 'KiraMG', type: 2, begin: at(2026, 9, 20, 18), end: at(2030, 10, 1, 18), by: 'GM_Kira', description: 'Flood' },
      { name: 'Flooder', type: 2, begin: at(2026, 9, 23, 18, 30), end: at(2030, 9, 30, 18, 30), by: 'GM_Zeus', description: 'Flood no chat global' },
      { name: 'Trapaceiro', type: 2, begin: at(2026, 9, 25, 9), end: at(2030, 10, 2, 9), by: 'GM_Kira', description: 'Golpe em trade' },
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
    // screenshots: ids na ordem de envio; votos diferentes (ORDER BY rate sem empates)
    screenshots: ([
      [1, 'c4f1a2', 'summoner', at(2026, 9, 1, 17, 30), 2, 'Primeiro reset!'],
      [2, 'b9e3d7', 'mago', at(2026, 9, 2, 20, 1, 55), 5, 'Blood Castle 8 limpo'],
      [3, 'e2a6c0', 'elfa', at(2026, 9, 3, 18, 20, 9), 7, 'Buff na praça de Lorencia'],
      [4, 'f7d2b4', 'zeus', at(2026, 9, 4, 22, 45, 2), 9, 'Set novo +13'],
      [5, '9c8e1f', 'darklord', at(2026, 9, 6, 21, 12, 40), 15, 'Castle Siege: Legends defendendo o castelo'],
      [6, 'a1b2c3', 'kira01', at(2026, 9, 14, 21, 10), 12, 'Castle Siege de domingo'],
      [7, 'd4e5f6', 'kira01', at(2026, 9, 18, 18, 2), 4, 'Kira de set novo'],
      [8, 'a7b8c9', 'kira01', at(2026, 9, 20, 22, 40), 0, 'Evento Blood Castle'],
    ] as const).map(([id, image, account, date, rate, legend]) => ({ id, image, account, date, rate, legend, sw: 90, sy: 68, cw: 640, cy: 480 })),
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
    notices: [
      { id: 100, subject: 'Servidor atualizado para a versão 2.5.4', date: at(2026, 9, 10, 20, 15),
        content: 'Atualizamos o servidor para a versão 2.5.4 do MuSite.<br /><br />Principais mudanças:<br />'
          + '- Novo sistema de bônus no cadastro;<br />- Ranking de Gens;<br />- Correções no painel do usuário.<br /><br />'
          + 'Qualquer problema, abra um ticket pelo painel.' },
      { id: 99, subject: 'Castle Siege neste domingo às 20h', date: at(2026, 9, 8, 18, 40),
        content: 'O Castle Siege deste domingo começa às 20h.<br /><br />As guilds devem se registrar até sábado.' },
      { id: 98, subject: 'Novo sistema de VIP disponível', date: at(2026, 9, 5, 11, 2),
        content: 'Os planos Vip Plus e Vip Extreme já estão disponíveis no painel.<br /><br />Veja as vantagens na página de VIPs.' },
      { id: 97, subject: 'Manutenção programada: sexta 03h', date: at(2026, 9, 2, 21, 30),
        content: 'O servidor ficará fora do ar na sexta, das 03h às 05h, para manutenção.' },
      { id: 96, subject: 'Evento de drop de Jewels ativado', date: at(2026, 8, 30, 16, 12),
        content: 'O drop de Jewels está em dobro durante todo o fim de semana.' },
    ],
    // ids em ordem de gravação; a lista mostra do mais novo para o mais antigo (ORDER BY id DESC)
    noticeComments: [
      { id: 1, noticeId: 100, account: 'zeus', comment: 'Testado aqui, tudo ok. Valeu ADM.', date: at(2026, 9, 10, 20, 31, 2), ip: '177.8.140.3' },
      { id: 2, noticeId: 100, account: 'novato7', comment: 'O painel ainda dá erro pra mim ao resetar, alguém mais?', date: at(2026, 9, 10, 20, 48, 30), ip: '201.55.9.87' },
      { id: 3, noticeId: 100, account: 'kira01', comment: 'Boa! Finalmente o ranking de gens.\nParabéns pela atualização.', date: at(2026, 9, 10, 21, 5, 12), ip: '187.10.22.1' },
    ],
    // a enquete da lateral (1, ativa) e uma inativa
    polls: [{ id: 2, question: 'Qual o melhor horário para eventos?', active: false }, { id: 1, question: 'Qual evento você quer no fim de semana?', active: true }],
    pollAnswers: [
      { idPoll: 1, idAnswer: 1, answer: 'Castle Siege', votes: 58 }, { idPoll: 1, idAnswer: 2, answer: 'Blood Castle 2x', votes: 31 },
      { idPoll: 1, idAnswer: 3, answer: 'Drop de Jewels', votes: 17 },
      { idPoll: 2, idAnswer: 4, answer: 'Manhã', votes: 3 }, { idPoll: 2, idAnswer: 5, answer: 'Tarde', votes: 9 }, { idPoll: 2, idAnswer: 6, answer: 'Noite', votes: 22 },
    ],
    chat: ([
      [1, '20:41:05', 0, 'DarkLord', 'alguém pra BC 7?', ''], [2, '20:42:18', 2, 'Kira', 'reunião da guild às 21h', ''],
      [3, '20:43:02', 8, 'Zeus', 'vendo Bone Blade +13 @ Lorencia', ''], [4, '20:44:40', 1, 'Elfa', 'bora Kalima 6', ''],
      [5, '20:45:11', 0, 'Mago', 'gg', ''], [6, '20:46:30', 5, 'Kira', 'passa o set depois?', 'Zeus'],
      [7, '20:47:52', 4, 'Kira', 'Legends: CS domingo, todos online', ''], [8, '20:48:09', 6, 'Zeus', 'aliança fechada com a Legends', ''],
      [9, '20:50:00', 3, 'Admin', 'Castle Siege começa em 10 minutos!', ''], [10, '20:51:33', 7, 'GM_Kira', 'Evento de drop no Lorencia agora', ''],
    ] as const).map(([id, hour, type, name, message, destiny]) => ({ id, day: '23/09/2026', hour, server: 'Server 1', type, name, message, destiny })),
  }
}
