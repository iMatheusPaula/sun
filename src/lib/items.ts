// Itens do jogo: o que as classes ldItemClass do MuSite calculavam (ldItemMake, ldItemParse, ldVault,
// ldItemOptionsDatabase) sobre o banco de itens (@/data/itemdb).
import { itemInfo, itemName } from '@/data/itemdb'
import type { Db, ItemData, VaultItem } from '@/data/db'

// ldItemMake::ItemGetDurability: durabilidade pela base do item, o level e se é excelente/ancient
const NO_EXC_BONUS = new Set(['12:3', '12:4', '12:5', '12:6', '12:36', '12:37', '12:38', '12:39', '12:40', '12:43', '0:19', '4:18', '5:10', '2:13', '13:30'])
export function itemDurability(cat: number, idx: number, base: number, level: number, excellent: boolean, ancient = 0): number {
  if (cat === 14 && idx === 27 && level === 3) level = 0
  if (cat === 14 && idx === 29) return 1
  let durability: number
  if (level < 5) durability = base + level
  else if (level === 10) durability = base + level * 2 - 3
  else if (level === 11) durability = base + level * 2 - 1
  else if (level === 12) durability = base + level * 2 + 2
  else if (level === 13) durability = base + level * 2 + 6
  else durability = base + level * 2 - 4
  if (!NO_EXC_BONUS.has(`${cat}:${idx}`)) {
    if (ancient !== 0) durability += 20
    else if (excellent) durability += 15
  }
  return Math.min(durability, 255)
}

// ldItemOptionsDatabase::getExcellents: os nomes das 6 opções excelentes pela família do item
const EXC_NAMES: Record<number, string[]> = {
  0: ['No effect', 'No effect', 'No effect', 'No effect', 'No effect', 'No effect'],
  1: ['Increases recovery rate of mana (Mana / 8)', 'Increases recovery rate of life (Life / 8)', 'Increase the speed of magical damage +7',
    'Increase magical damage +1%', 'Increase magic damage + level/20', 'Success in excellent damage +10%'],
  2: ['Increased rate of acquisition of Zen +40%', 'Success of Defense +10%', 'Mirrors in 5% Damage Received', 'Lowers the damage by +4%',
    'Increase mana +4%', 'Increases in life +4%'],
  3: ['Life increased by +125', 'Mana increased by +125', 'Defense of the opponent ignored by 3%', 'Stamina increased by +50',
    'Increase the speed of magic damage to +5', 'No effect'],
  4: ['+Damage', '+Defense', '+Illusion', 'No effect', 'No effect', 'No effect'],
  5: ['Increases zens who fall into +40%', '+10% Defensive success rank', 'Returns the blow +5%', 'Received low blow +4%',
    'Increase mana +4%', 'Increases in life +4%'],
  6: ['Increase mana after killing monster + mana / 8', 'Increases life after killing monster + life / 8', 'Increases attack speed +7',
    'Adds +2% damage', '+ Increases damage level/20', '+10% Defensive success rank'],
  7: ['Ignore the Power of Defensive Opponent 5%', '5% Chance to return the damage', '5% Chance of a lifetime to recover',
    '5% Chance to recover all mana', 'No effect', 'No effect'],
}

function excellentType(cat: number, idx: number): number {
  if (cat <= 5) return 1
  if (cat <= 11) return 2
  let type = 0
  if (cat === 12) {
    if (idx >= 3 && idx <= 6) type = 3
    if (idx >= 36 && idx <= 43) type = 7
    if (idx >= 130 && idx <= 134) type = 3
  } else if (cat === 13) {
    if (idx >= 8 && idx <= 9) type = 5
    if (idx >= 12 && idx <= 13) type = 6
    if (idx >= 20 && idx <= 24) type = 5
    if (idx >= 25 && idx <= 28) type = 6
    if (idx >= 33) type = 3
    if (idx >= 37) type = 4
  }
  return type
}

export function excellentNames(item: Pick<ItemData, 'cat' | 'idx' | 'excellent'>): string[] {
  const names = EXC_NAMES[excellentType(item.cat, item.idx)]!
  return item.excellent.flatMap((on, i) => (on ? [names[i]!] : []))
}

// como a lista do baú mostra o item: nome +level +adicional
export function itemLabel(item: ItemData): string {
  return `${itemName(item.cat, item.idx)} +${item.level} +${item.option * 4}`
}

export const serialHex = (serial: number) => serial.toString(16).toUpperCase().padStart(8, '0')

// ldVault: 120 casas de 8 × 15; o item ocupa largura × altura a partir da sua casa
export const VAULT_COLS = 8
export const VAULT_ROWS = 15

export function itemSize(item: Pick<ItemData, 'cat' | 'idx'>): [w: number, h: number] {
  const info = itemInfo(item.cat, item.idx)
  return [info?.[1] ?? 1, info?.[2] ?? 1]
}

function occupied(items: VaultItem[]): boolean[] {
  const cells = new Array<boolean>(VAULT_COLS * VAULT_ROWS).fill(false)
  for (const it of items) {
    const [w, h] = itemSize(it)
    const x0 = it.slot % VAULT_COLS
    const y0 = Math.floor(it.slot / VAULT_COLS)
    for (let y = y0; y < Math.min(y0 + h, VAULT_ROWS); y++) {
      for (let x = x0; x < Math.min(x0 + w, VAULT_COLS); x++) cells[y * VAULT_COLS + x] = true
    }
  }
  return cells
}

// ldVault::searchSlotsInVault: a primeira casa (linha por linha) onde cabe um item w × h; -1 se não couber
export function findSlot(items: VaultItem[], w: number, h: number): number {
  const cells = occupied(items)
  for (let y = 0; y < VAULT_ROWS; y++) {
    for (let x = 0; x < VAULT_COLS; x++) {
      if (y + h > VAULT_ROWS || x + w > VAULT_COLS) continue
      let free = true
      for (let cy = 0; cy < h && free; cy++) for (let cx = 0; cx < w && free; cx++) if (cells[(y + cy) * VAULT_COLS + x + cx]) free = false
      if (free) return y * VAULT_COLS + x
    }
  }
  return -1
}

// WZ_GetItemSerial: o próximo serial do servidor (o maior já usado + 1)
export function nextItemSerial(db: Db): number {
  const items: ItemData[] = [...db.warehouses.flatMap((w) => [...w.items, ...w.items2]), ...db.virtualVault]
  return Math.max(0, ...items.map((i) => i.serial)) + 1
}
