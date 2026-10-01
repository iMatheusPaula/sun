// ldRankings e os tops da home (ldHome), sobre o banco do front. O original lia as tabelas que o cron gerava
// (webRankingCharacters*: TOP 200 da Character sem a equipe, CtlCode < 8); aqui as consultas rodam direto nos
// personagens, então os rankings acompanham o painel na hora. Empates: ordem da tabela (sort estável).
import type { CharacterRow, Db, GuildMemberRow } from '@/data/db'
import { RANKING_GENS_MANUFACTURER } from '@/config/settings'
import * as L from '@/lang/ldrankings'

// ?type= do original: 1 resets (period 0/1/2), 2 level, 3 guilds, 4 pk, 5 master resets,
// 6 perfil de personagem, 7 perfil de guild, 8/9 gens Duprian/Vanert
export type RankingType = 1 | 2 | 3 | 4 | 5 | 6 | 7 | 8 | 9
export type CharacterRankingType = 1 | 2 | 4 | 5
export type Period = 0 | 1 | 2

// cada combinação tem a sua rota
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

// nomes e logins no SQL Server: comparação sem diferenciar maiúsculas
const same = (a: string, b: string) => a.toLowerCase() === b.toLowerCase()

function byKeysDesc<T>(keys: (item: T) => number[]) {
  return (a: T, b: T) => {
    const ka = keys(a)
    const kb = keys(b)
    for (let i = 0; i < ka.length; i++) if (ka[i] !== kb[i]) return kb[i]! - ka[i]!
    return 0
  }
}

// ORDER BY de cada gerateRanking* (a coluna de PK é a COLUMN_PK_RANKING, PkCountWeb)
const ORDER: Record<CharacterRankingType, (period: Period) => (c: CharacterRow) => number[]> = {
  1: (period) => [
    (c: CharacterRow) => [c.resets, c.level],
    (c: CharacterRow) => [c.resetsWeek, c.resets, c.level],
    (c: CharacterRow) => [c.resetsMonth, c.resets, c.level],
  ][period]!,
  2: () => (c) => [c.level, c.resets],
  4: () => (c) => [c.pkCountWeb, c.resets],
  5: () => (c) => [c.masterResets, c.resets],
}

export function characterRanking(db: Db, type: CharacterRankingType, period: Period, top: number): CharacterRow[] {
  return db.characters.filter((c) => c.ctlCode < 8).sort(byKeysDesc(ORDER[type](period))).slice(0, top)
}

// coluna de valor: resets (ou resets da semana/mês) e master resets
export function rankingValue(c: CharacterRow, type: CharacterRankingType, period: Period): number {
  if (type === 5) return c.masterResets
  if (type === 1) return [c.resets, c.resetsWeek, c.resetsMonth][period]!
  return c.resets
}

// ldHome::loadTop*: TOP 4 da tabela de cada ranking pela coluna do tipo — os 4 primeiros do ranking
export const HOME_TOPS: { title: string; ranking: string; type: CharacterRankingType; period: Period; value: (c: CharacterRow) => number }[][] = [
  [
    { title: 'Top Resets:', ranking: '/rankings', type: 1, period: 0, value: (c) => c.resets },
    { title: 'Top Rst. Semanal:', ranking: '/rankings-semanal', type: 1, period: 1, value: (c) => c.resetsWeek },
    { title: 'Top Rst. Mensal:', ranking: '/rankings-mensal', type: 1, period: 2, value: (c) => c.resetsMonth },
  ],
  [
    { title: 'Top Master Resets:', ranking: '/rankings-master-resets', type: 5, period: 0, value: (c) => c.masterResets },
    { title: 'Top Pk:', ranking: '/rankings-pk', type: 4, period: 0, value: (c) => c.pkCountWeb },
    { title: 'Top Level:', ranking: '/rankings-level', type: 2, period: 0, value: (c) => c.level },
  ],
]

// webVips.type da conta (sem conferir a validade, como o original)
export function accountVip(db: Db, login: string): number {
  return db.accounts.find((a) => same(a.login, login))?.vip ?? 0
}

export function findCharacter(db: Db, name: string): CharacterRow | undefined {
  return db.characters.find((c) => same(c.name, name))
}

// MEMB_STAT.ConnectStat da conta (ldPanelUser::checkOnlineAccount)
export function accountOnline(db: Db, login: string): boolean {
  return db.accounts.find((a) => same(a.login, login))?.online ?? false
}

// ORDER BY G_Score desc
export function guildRanking(db: Db, top: number) {
  return [...db.guilds].sort((a, b) => b.score - a.score).slice(0, top)
}

export function findGuild(db: Db, name: string) {
  return db.guilds.find((g) => same(g.name, name))
}

export function guildMembers(db: Db, guild: string): GuildMemberRow[] {
  return db.guildMembers.filter((m) => same(m.guild, guild))
}

// SELECT G_Name FROM GuildMember WHERE Name = …
export function guildOf(db: Db, character: string): string | undefined {
  return db.guildMembers.find((m) => same(m.name, character))?.guild
}

// gerateRankingGuilds: assistente = G_Status 64, battle master = G_Status 32 (vazio quando não há)
export function memberWithStatus(db: Db, guild: string, status: 32 | 64): string {
  return guildMembers(db, guild).find((m) => m.status === status)?.name ?? ''
}

// optionLoadDeleteAccount / optionLoadDeleteCharacter: o personagem que é mestre leva a guild inteira junto;
// os demais só saem da GuildMember
export function removeFromGuilds(db: Db, character: string) {
  const owned = db.guilds.find((g) => same(g.master, character))
  if (owned) {
    db.guildMembers = db.guildMembers.filter((m) => !same(m.guild, owned.name))
    db.guilds = db.guilds.filter((g) => g !== owned)
  } else db.guildMembers = db.guildMembers.filter((m) => !same(m.name, character))
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

// família 1 = Duprian, 2 = Vanert; ORDER BY Contribution DESC, Name ASC
export function gensRanking(db: Db, family: 1 | 2, top: number) {
  const name = (s: string) => s.toLowerCase()
  return db.gens
    .filter((g) => g.family === family)
    .sort((a, b) => b.contribution - a.contribution || (name(a.name) < name(b.name) ? -1 : name(a.name) > name(b.name) ? 1 : 0))
    .slice(0, top)
    .map(({ name, contribution }, i) => {
      const grad = graduation(contribution, i + 1)
      return { name, contribution, grad, gradName: GRADUATIONS[grad]! }
    })
}
