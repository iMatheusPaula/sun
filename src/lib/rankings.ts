// ldRankings: as consultas de cada ranking, sobre os dados de exemplo.
import { characters, type Character } from '@/data/characters'
import { gensContribution, type GensFamily } from '@/data/gens'
import { guilds } from '@/data/guilds'
import { RANKING_GENS_MANUFACTURER } from '@/config/settings'
import * as L from '@/lang/ldrankings'

// ?type= do original: 1 resets (period 0/1/2), 2 level, 3 guilds, 4 pk, 5 master resets,
// 6 perfil de personagem, 7 perfil de guild, 8/9 gens Duprian/Vanert
export type RankingType = 1 | 2 | 3 | 4 | 5 | 6 | 7 | 8 | 9
export type CharacterRankingType = 1 | 2 | 4 | 5
export type Period = 0 | 1 | 2

// cada combinação tem a sua rota (o nome da página do kit)
export const RANKING_PAGES: { path: string; type: RankingType; period?: Period }[] = [
  { path: '/rankings', type: 1, period: 0 },
  { path: '/rankings-semanal', type: 1, period: 1 },
  { path: '/rankings-mensal', type: 1, period: 2 },
  { path: '/rankings-level', type: 2 },
  { path: '/rankings-guilds', type: 3 },
  { path: '/rankings-pk', type: 4 },
  { path: '/rankings-master-resets', type: 5 },
  { path: '/rankings-personagem', type: 6 },
  { path: '/rankings-guild', type: 7 },
  { path: '/rankings-gens', type: 8 },
  { path: '/rankings-gens-vanert', type: 9 },
]

export function rankingPath(type: number, period = 0): string {
  const page = RANKING_PAGES.find((p) => p.type === type && (type !== 1 || p.period === period))
  return page?.path ?? '/rankings'
}

// $_GET['top']: 10, 50, 100 ou 200; qualquer outro valor vira 10
export const TOPS = [10, 50, 100, 200] as const
export function normalizeTop(value: unknown): number {
  const n = Number(value)
  return (TOPS as readonly number[]).includes(n) ? n : 10
}

function byKeysDesc<T>(keys: (item: T) => number[]) {
  return (a: T, b: T) => {
    const ka = keys(a)
    const kb = keys(b)
    for (let i = 0; i < ka.length; i++) if (ka[i] !== kb[i]) return kb[i]! - ka[i]!
    return 0
  }
}

// ORDER BY de cada gerateRanking*
const ORDER: Record<CharacterRankingType, (period: Period) => (c: Character) => number[]> = {
  1: (period) => [
    (c: Character) => [c.resets, c.level],
    (c: Character) => [c.resetsWeek, c.resets, c.level],
    (c: Character) => [c.resetsMonth, c.resets, c.level],
  ][period]!,
  2: () => (c) => [c.level, c.resets],
  4: () => (c) => [c.pk, c.resets],
  5: () => (c) => [c.masterResets, c.resets],
}

export function characterRanking(type: CharacterRankingType, period: Period, top: number): Character[] {
  return [...characters].sort(byKeysDesc(ORDER[type](period))).slice(0, top)
}

// coluna de valor: resets (ou resets da semana/mês) e master resets
export function rankingValue(c: Character, type: CharacterRankingType, period: Period): number {
  if (type === 5) return c.masterResets
  if (type === 1) return [c.resets, c.resetsWeek, c.resetsMonth][period]!
  return c.resets
}

export function guildRanking(top: number) {
  return [...guilds].sort((a, b) => b.score - a.score).slice(0, top)
}

// gerateRankingGens com GENS_MANUFACTURER 1 (X-Team): faixa pela contribuição; acima de 100000, pela posição
const GRADUATIONS = [
  '', L.RANKING_GENS_GRADUATION_GRAND_DUKE, L.RANKING_GENS_GRADUATION_DUKE, L.RANKING_GENS_GRADUATION_MARQUIS,
  L.RANKING_GENS_GRADUATION_COUNT, L.RANKING_GENS_GRADUATION_VISCOUNT, L.RANKING_GENS_GRADUATION_BARON,
  L.RANKING_GENS_GRADUATION_KNIGHT_COMMANDER, L.RANKING_GENS_GRADUATION_SUPERIOR_KNIGHT, L.RANKING_GENS_GRADUATION_KNIGHT,
  L.RANKING_GENS_GRADUATION_GUARD_PREFECT, L.RANKING_GENS_GRADUATION_OFFICER, L.RANKING_GENS_GRADUATION_LIEUTENANT,
  L.RANKING_GENS_GRADUATION_SERGEANT, L.RANKING_GENS_GRADUATION_PRIVATE,
]

function graduation(contribution: number, position: number): number {
  if (RANKING_GENS_MANUFACTURER !== 1) return 14 // ENC Team: GRADUATION viria da tabela T_GensSystem
  if (contribution > 100000) {
    if (position === 1) return 1
    if (position <= 5) return 2
    if (position <= 10) return 3
    if (position <= 30) return 4
    if (position <= 50) return 5
    if (position <= 100) return 6
    if (position <= 200) return 7
    if (position <= 300) return 8
    return 9
  }
  if (contribution <= 999) return 14
  if (contribution <= 4999) return 13
  if (contribution <= 14999) return 12
  if (contribution <= 49999) return 11
  if (contribution <= 99999) return 10
  return 9
}

export function gensRanking(family: GensFamily, top: number) {
  return [...gensContribution[family]]
    .sort((a, b) => b[1] - a[1])
    .slice(0, top)
    .map(([name, contribution], i) => {
      const grad = graduation(contribution, i + 1)
      return { name, contribution, grad, gradName: GRADUATIONS[grad]! }
    })
}
