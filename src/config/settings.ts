// Configuração do site — chaves de exibição exportadas uma vez de modules/settings.php (MuSite).
// Ficaram de fora banco, SMTP, senhas, links do servidor original e dados pessoais.

export const TITLE_SITE = 'MuOnline Season 6'
export const SERVER_NAME = 'MuOnline Server'
export const SERVER_SLOGAN = 'Servidor 24 horas'
export const SERVER_VERSION = '1.0X Season 6'
export const SERVER_DROP = '70%'
export const SERVER_XP = '4500x'
export const SERVER_BUGBLESS = 'Online'
export const CASH_NAME = 'SGolds'
export const CASH_NAME2 = 'Golds'
export const POINTS_NAME = 'Pontos'

// downloads: os links da settings eram do servidor original (mortos); no app levam a '#'
export const DOWNLOAD_GAMEFULL_LINK = '#'
export const DOWNLOAD_GAMEFULL_MB = '468 mb'
export const DOWNLOAD_PATCH_LINK = '#'
export const DOWNLOAD_PATCH_MB = '0.25 mb'

export const USE_MD5 = false
// $Config_SMTP['LimitTime']: minutos entre dois pedidos de recuperação de senha do mesmo login
export const RECOVERY_LIMIT_TIME = 0

// $CLASS_CHARACTERS['CLASSCODES'] (VESION_MUSERVER 2)
export const CLASS_CODES: [code: number, name: string][] = [
  [0, 'Dark Wizard'], [1, 'Soul Master'], [2, 'Grand Master'],
  [16, 'Dark Knight'], [17, 'Blade Knight'], [18, 'Blade Master'],
  [32, 'Fairy Elf'], [33, 'Muse Elf'], [34, 'High Elf'],
  [48, 'Magic Gladiator'], [49, 'Duel Master'],
  [64, 'Dark Lord'], [65, 'Lord Emperor'],
  [80, 'Summoner'], [81, 'Blood Summoner'], [82, 'Dimension Master'],
  [96, 'Rage Fighter'], [98, 'Fist Master'],
]

// ldPanelUser::classNameDefine: código sem nome cadastrado sai como o próprio número
export function className(code: number): string {
  return CLASS_CODES.find(([c]) => c === code)?.[1] ?? String(code)
}

// as classes das listas da VESION_MUSERVER 2 (Season 2): DW, SM, DK, BK, FE, ME, MG, DL (Mudar classe, Editar personagem)
export const SEASON_CLASSES = [0, 1, 16, 17, 32, 33, 48, 64].map((code) => CLASS_CODES.find(([c]) => c === code)!)

// $PANELUSER_MODULE['BUY_VIPS']['NAME_FLATS'] — índice = tipo de vip (0 = Free)
export const VIP_NAMES = ['Free', 'Vip Silver', 'Vip Gold', 'Vip Diamante', 'Vip Plus', 'Vip Extreme'] as const

// $PANELUSER_MODULE['BUY_VIPS']['ACTIVES'] — planos à venda (VIP_1..VIP_5)
export const VIP_ACTIVES: Record<1 | 2 | 3 | 4 | 5, boolean> = { 1: true, 2: true, 3: true, 4: true, 5: true }
export const ACTIVE_VIPS = ([1, 2, 3, 4, 5] as const).filter((v) => VIP_ACTIVES[v])

// $PANELUSER_MODULE['BUY_VIPS']['PRICE'] — VIP_1..VIP_5 por 30 / 90 / 180 / 365 dias
export const VIP_DAYS = [30, 90, 180, 365] as const
export const VIP_PRICES: Record<1 | 2 | 3 | 4 | 5, [number, number, number, number]> = {
  1: [5, 15, 30, 60],
  2: [10, 30, 60, 120],
  3: [10, 30, 60, 120],
  4: [10, 30, 60, 120],
  5: [10, 30, 60, 120],
}

// $PANELUSER_MODULE['MOVE_CHARACTER']['MAPS']: número, nome, x, y (Karutan repetido como na settings)
export const MAPS: [number: number, name: string, x: number, y: number][] = [
  [0, 'Lorencia', 147, 127], [1, 'Dungeon', 107, 247], [2, 'Davias', 197, 46], [3, 'Noria', 174, 111],
  [4, 'Losttower', 204, 77], [6, 'Stadium', 62, 115], [7, 'Atlans', 24, 17], [8, 'Tarkan', 194, 67],
  [10, 'Icarus', 125, 125], [24, 'Kalima 1', 125, 125], [25, 'Kalima 2', 125, 125], [26, 'Kalima 3', 125, 125],
  [27, 'Kalima 4', 125, 125], [28, 'Kalima 5', 125, 125], [29, 'Kalima 6', 125, 125], [30, 'Valey of Loren', 93, 214],
  [31, 'Land of Trial', 93, 214], [33, 'Aida', 84, 13], [34, 'CryWolf', 228, 41], [37, 'KantruLand', 20, 218],
  [38, 'KantruRuin', 71, 105], [41, 'Barracks', 28, 76], [42, 'Refuge', 97, 185], [51, 'Elbeland', 51, 216],
  [56, 'Swamp of Peace', 135, 105], [63, 'Vulcanus', 120, 129], [80, 'Karutan', 124, 123], [79, 'LorenMarket', 126, 142],
  [80, 'Karutan', 124, 123],
]

export function mapName(number: number): string | undefined {
  return MAPS.find(([n]) => n === number)?.[1]
}

// $PANELUSER_PREMISSIONS: [ligado, Free, Vip 1, Vip 2, Vip 3, Vip 4, Vip 5]
export type PanelOption =
  | 'MODIFY_DATA' | 'CLEAN_VAULT' | 'DOUBLE_VAULT' | 'VIRTUAL_VAULT' | 'GAME_DISCONNECT' | 'GOLDEN_ARCHER'
  | 'COLLECTOR_POINTS' | 'AUCTIONS' | 'LOG_BUYS' | 'MODIFY_PERSONALID' | 'RESET' | 'MASTER_RESET'
  | 'RESET_TRANSFER' | 'CLEAN_PK' | 'DISTRIBUTE_POINTS' | 'MOVE_CHARACTER' | 'CHANGE_NICK' | 'CHANGE_CLASS'
  | 'REDISTRIBUTE_POINTS' | 'CLEAN_INVENTORY' | 'MANAGER_PHOTO' | 'BUY_VIPS' | 'CONFIRM_PAYMENT'
  | 'OPEN_COMPLAINTS' | 'OPEN_TICKET' | 'READ_TICKET' | 'SEND_SMS' | 'SCREENSHOT'

type Flags = [on: number, free: number, vip1: number, vip2: number, vip3: number, vip4: number, vip5: number]
export const PANEL_PERMISSIONS: Record<PanelOption, Flags> = {
  MODIFY_DATA: [1, 1, 1, 1, 1, 1, 1],
  CLEAN_VAULT: [1, 0, 1, 1, 1, 1, 1],
  DOUBLE_VAULT: [1, 0, 1, 1, 1, 1, 1],
  VIRTUAL_VAULT: [1, 1, 1, 1, 1, 1, 1],
  GAME_DISCONNECT: [1, 1, 1, 1, 1, 1, 1],
  GOLDEN_ARCHER: [1, 1, 1, 1, 1, 1, 1],
  COLLECTOR_POINTS: [1, 1, 1, 1, 1, 1, 1],
  AUCTIONS: [1, 1, 1, 1, 1, 1, 1],
  LOG_BUYS: [1, 1, 1, 1, 1, 1, 1],
  MODIFY_PERSONALID: [1, 1, 1, 1, 1, 1, 1],
  RESET: [1, 1, 1, 1, 1, 1, 1],
  MASTER_RESET: [1, 1, 1, 1, 1, 1, 1],
  RESET_TRANSFER: [1, 0, 1, 1, 1, 1, 1],
  CLEAN_PK: [1, 1, 1, 1, 1, 1, 1],
  DISTRIBUTE_POINTS: [1, 1, 1, 1, 1, 1, 1],
  MOVE_CHARACTER: [1, 0, 1, 1, 1, 1, 1],
  CHANGE_NICK: [1, 0, 1, 1, 1, 1, 1],
  CHANGE_CLASS: [1, 0, 1, 1, 1, 1, 1],
  REDISTRIBUTE_POINTS: [1, 0, 1, 1, 1, 1, 1],
  CLEAN_INVENTORY: [1, 0, 1, 1, 1, 1, 1],
  MANAGER_PHOTO: [1, 1, 1, 1, 1, 1, 1],
  BUY_VIPS: [1, 1, 1, 1, 1, 1, 1],
  CONFIRM_PAYMENT: [1, 1, 1, 1, 1, 1, 1],
  OPEN_COMPLAINTS: [1, 1, 1, 1, 1, 1, 1],
  OPEN_TICKET: [1, 1, 1, 1, 1, 1, 1],
  READ_TICKET: [1, 1, 1, 1, 1, 1, 1],
  SEND_SMS: [1, 1, 1, 1, 1, 1, 1],
  SCREENSHOT: [1, 1, 1, 1, 1, 1, 1],
}

// $PANELUSER_MODULE['RESET'] (RESET_MODE 1, sem limite de resets): uma faixa por quantidade de resets,
// cada campo com os valores de [Free, Vip 1..Vip 5]
export interface ResetTier {
  LevelReset: number[]
  LevelAfter: number[]
  ZenRequire: number[]
  Points: number[]
  CleanItens: boolean[]
  CleanMagics: boolean[]
  CleanQuests: boolean[]
}

const upper: ResetTier = {
  LevelReset: [300, 290, 280, 280, 280, 280],
  LevelAfter: [1, 6, 12, 12, 12, 12],
  ZenRequire: [50000000, 30000000, 10000000, 10000000, 10000000, 10000000],
  Points: [350, 550, 800, 800, 800, 800],
  CleanItens: [true, false, false, false, false, false],
  CleanMagics: [true, false, false, false, false, false],
  CleanQuests: [true, false, false, false, false, false],
}

export const RESET_TIERS: Record<string, ResetTier> = {
  '0-10': {
    LevelReset: [280, 240, 230, 230, 230, 230],
    LevelAfter: [6, 6, 12, 12, 12, 12],
    ZenRequire: [500000, 300000, 100000, 100000, 100000, 100000],
    Points: [600, 800, 1000, 1000, 1000, 1000],
    CleanItens: [false, false, false, false, false, false],
    CleanMagics: [false, false, false, false, false, false],
    CleanQuests: [false, false, false, false, false, false],
  },
  '11-50': {
    LevelReset: [290, 250, 240, 240, 240, 240],
    LevelAfter: [1, 6, 12, 12, 12, 12],
    ZenRequire: [5000000, 3000000, 1000000, 1000000, 1000000, 1000000],
    Points: [350, 550, 800, 800, 800, 800],
    CleanItens: [true, false, false, false, false, false],
    CleanMagics: [true, false, false, false, false, false],
    CleanQuests: [true, false, false, false, false, false],
  },
  '51-100': upper,
  '101-150': upper,
  '151-200': upper,
  '201-300': upper,
  '301-XXX': upper,
}

// $RANKING_CONFIGS
export const RANKING_STATS = true
export const RANKING_GENS = true
export const RANKING_GENS_MANUFACTURER = 1 // 0 = MuServer ENC Team, 1 = X-Team

// $REGISTER_SETTINGS
export const REGISTER = {
  EMAIL_ACTIVE: true,
  FORCELOWER: true,
  EMAIL_REPEAT: true,
  BONUS_VIP: { ACTIVE: true, TYPE: 1, DAYS: 5 },
  BONUS_ITEM_ACTIVE: true,
  // kits com os nomes dos itens resolvidos no banco de itens do MuSite (item.serialize.txt)
  KITS: [
    { name: 'Kit de Blade Knight', items: ['Dark Phoenix Helm', 'Dark Phoenix Armor', 'Dark Phoenix Pants', 'Dark Phoenix Gloves', 'Dark Phoenix Boots', 'Wings of Dragon', 'Bone Blade'] },
    { name: 'Kit de Soul Master', items: ['Dark Soul Helm', 'Dark Soul Armor', 'Dark Soul Pants', 'Dark Soul Gloves', 'Dark Soul Boots', 'Wings of Soul', 'Platina Staff'] },
    { name: 'Kit de Muse Elf', items: ['Red Spirit Helm', 'Red Sprit Armor', 'Red Spirit Pants', 'Red Spirit Gloves', 'Red Spirit Boots', 'Wings of Spirits', 'Skull Shield'] },
  ],
}

// $SCREENSHOTS
export const SCREENSHOTS = { MAX_WIDTH: 640, HOME: true, TOP_HOME: 5 }

// $CASTLE_SIEGE
export const CASTLE_SIEGE = { ENABLE: true, CONFRONTATION: 'Domingo as 19:00h' }
