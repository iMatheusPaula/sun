// Parâmetros de reset de ldPanelUser::loadOptionsReset / loadOptionsMasterReset, pelos 4 RESET_MODE da settings.
import { CLASS_CODES, RESET_TIERS, type ResetTier } from '@/config/settings'
import { RESET_LIMIT, RESET_MODE } from '@/config/panel'
import type { PlanIndex } from './paneluser'

const TIERS: [from: number, to: number, key: string][] = [
  [0, 10, '0-10'], [11, 50, '11-50'], [51, 100, '51-100'], [101, 150, '101-150'],
  [151, 200, '151-200'], [201, 300, '201-300'], [301, Infinity, '301-XXX'],
]

function tierOf(resets: number): string {
  return TIERS.find(([from, to]) => resets >= from && resets <= to)?.[2] ?? '0-10'
}

// RESET_MODE 4: os pontos somam as faixas até a da quantidade de resets
function cumulativePoints(resets: number, plan: PlanIndex): number {
  let total = 0
  for (const [from, , key] of TIERS) {
    if (resets < from) break
    total += resets * RESET_TIERS[key]!.Points[plan]!
  }
  return Math.trunc(total)
}

export interface ResetParams {
  LevelReset: number
  LevelAfter: number
  ZenRequire: number
  Points: number
  CleanItens: boolean
  CleanMagics: boolean
  CleanQuests: boolean
  LimitResets: number
  ResetPoints: boolean
}

// resets = quantidade já com o reset que vai ser dado (o original faz $resets++ antes);
// pointsBase = de quantos resets o modo 4 calcula os pontos (no master reset, resets - exigidos)
export function resetParams(resets: number, plan: PlanIndex, pointsBase = resets): ResetParams {
  const tier: ResetTier = RESET_TIERS[RESET_MODE === 2 || RESET_MODE === 4 ? tierOf(resets) : '0-10']!
  return {
    LevelReset: tier.LevelReset[plan]!,
    LevelAfter: tier.LevelAfter[plan]!,
    ZenRequire: tier.ZenRequire[plan]!,
    Points: RESET_MODE === 4 ? cumulativePoints(pointsBase, plan) : tier.Points[plan]!,
    CleanItens: tier.CleanItens[plan]!,
    CleanMagics: tier.CleanMagics[plan]!,
    CleanQuests: tier.CleanQuests[plan]!,
    LimitResets: RESET_LIMIT[plan]!,
    ResetPoints: RESET_MODE !== 3,
  }
}

// ldPanelUser::resetClassCode: classe evoluída volta à básica
const BASE_CLASS: Record<number, number> = {
  0: 0, 1: 0, 2: 0, 16: 16, 17: 16, 18: 16, 32: 32, 33: 32, 34: 32, 48: 48, 49: 48, 64: 64, 65: 64, 80: 80, 81: 80, 96: 96, 98: 96,
}
export function resetClassCode(cls: number): number | undefined {
  return BASE_CLASS[cls]
}

// elfas (Fairy Elf a High Elf) voltam para Noria; as outras classes, para Lorencia
export function resetSpawn(cls: number): { map: number; x: number; y: number } {
  const elf = cls >= 32 && cls <= 34
  return elf ? { map: 3, x: 174, y: 111 } : { map: 0, x: 125, y: 125 }
}

export const isDarkLord = (cls: number) => cls >= 64 && cls <= 65

export function classOptions(): [code: number, name: string][] {
  return CLASS_CODES
}
