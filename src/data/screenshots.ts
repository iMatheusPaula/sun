// Screenshots enviadas pelos jogadores (módulo screenshots; reference/_build/render.php: sampleShots).
export interface Screenshot {
  id: number
  author: string
  rate: number
  caption: string
  sentAt: Date
}

export const screenshots: Screenshot[] = [
  { id: 1, author: 'DarkLord', rate: 4.5, caption: 'Castle Siege de domingo — Legends defendendo o castelo', sentAt: new Date(2026, 8, 6, 21, 12, 40) },
  { id: 2, author: 'Kira', rate: 4.2, caption: 'Kalima 7 com a party', sentAt: new Date(2026, 8, 5, 19, 3, 11) },
  { id: 3, author: 'Zeus', rate: 4.0, caption: 'Set novo +13', sentAt: new Date(2026, 8, 4, 22, 45, 2) },
  { id: 4, author: 'Elfa', rate: 3.8, caption: 'Buff na praça de Lorencia', sentAt: new Date(2026, 8, 3, 18, 20, 9) },
  { id: 5, author: 'Mago', rate: 3.5, caption: 'Blood Castle 8 limpo', sentAt: new Date(2026, 8, 2, 20, 1, 55) },
  { id: 6, author: 'Summoner', rate: 2.0, caption: 'Primeiro reset!', sentAt: new Date(2026, 8, 1, 17, 30, 0) },
  { id: 7, author: 'Assassin', rate: 0, caption: 'PvP em Stadium', sentAt: new Date(2026, 7, 31, 23, 59, 59) },
]

export function findScreenshot(id: number): Screenshot | undefined {
  return screenshots.find((s) => s.id === id)
}

