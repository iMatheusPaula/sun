// Banimentos (ldBanned: webBans; reference/_build/render.php: bannedTags).
export interface Ban {
  name: string
  until: Date
  by: string
  reason: string
}

export const bannedCharacters: Ban[] = [
  { name: 'H4ck3r', until: new Date(2030, 11, 31, 0, 0, 0), by: 'Admin', reason: 'Uso de hack' },
  { name: 'Flooder', until: new Date(2026, 8, 30, 18, 30, 0), by: 'GM_Zeus', reason: 'Flood no chat global' },
  { name: 'Trapaceiro', until: new Date(2026, 9, 2, 9, 0, 0), by: 'GM_Kira', reason: 'Golpe em trade' },
]

export const bannedAccounts: Ban[] = [
  { name: 'vendedor01', until: new Date(2030, 11, 31, 0, 0, 0), by: 'Admin', reason: 'Venda de conta' },
  { name: 'bot_farm', until: new Date(2026, 9, 15, 12, 0, 0), by: 'GM_Kira', reason: 'Uso de bot' },
]
