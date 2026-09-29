import { uploadedPhoto } from './placeholders'

// Personagens de exemplo. Os 10 primeiros são o $RANK_CHARS do kit (reference/_build/render.php): formam o
// top 10 de todos os rankings. Os demais aparecem nas guilds e no gens Vanert; têm valores abaixo do 10º
// colocado em todo ranking, então só surgem com top 50/100/200 — como aconteceria com o banco real.

export interface Character {
  name: string
  vip: number // tipo de vip da conta (VIP_NAMES)
  class: number
  level: number
  pk: number // < 0 = herói
  resets: number
  resetsWeek: number
  resetsMonth: number
  masterResets: number
  online: boolean
  // perfil (?page=rankings&type=6)
  points: number
  map: number
  x: number
  y: number
  zen: number
  strength: number
  dexterity: number
  vitality: number
  energy: number
  leadership: number
  photo?: string // Character.image: foto enviada no painel (sem ela, a foto padrão)
}

type Stats = Pick<Character, 'points' | 'map' | 'x' | 'y' | 'zen' | 'strength' | 'dexterity' | 'vitality' | 'energy' | 'leadership' | 'photo'>

function char(
  name: string,
  [vip, cls, level, pk, resets, resetsWeek, resetsMonth, masterResets]: number[],
  online: boolean,
  stats: Stats,
): Character {
  return { name, vip: vip!, class: cls!, level: level!, pk: pk!, resets: resets!, resetsWeek: resetsWeek!, resetsMonth: resetsMonth!, masterResets: masterResets!, online, ...stats }
}

// vip, classe, level, pk, resets, resets semana, resets mês, master resets
export const characters: Character[] = [
  char('DarkLord', [2, 48, 390, 987, 412, 25, 96, 6], true, { points: 1250, map: 0, x: 135, y: 128, zen: 1850000000, strength: 32767, dexterity: 8500, vitality: 12000, energy: 4200, leadership: 6100 }),
  char('Kira', [1, 17, 395, 640, 398, 19, 70, 5], true, { points: 820, map: 2, x: 197, y: 46, zen: 920000000, strength: 30500, dexterity: 14000, vitality: 9800, energy: 2800, leadership: 0 }),
  char('Zeus', [0, 2, 388, 812, 371, 17, 84, 7], true, { points: 460, map: 3, x: 174, y: 111, zen: 640000000, strength: 1200, dexterity: 9000, vitality: 7000, energy: 32767, leadership: 0, photo: uploadedPhoto }),
  char('Elfa', [2, 34, 400, -1, 350, 15, 77, 3], true, { points: 0, map: 0, x: 147, y: 127, zen: 410000000, strength: 4000, dexterity: 32767, vitality: 8000, energy: 6500, leadership: 0 }),
  char('Mago', [0, 2, 398, 120, 342, 28, 90, 4], true, { points: 1500, map: 29, x: 125, y: 125, zen: 275000000, strength: 900, dexterity: 7500, vitality: 6200, energy: 32767, leadership: 0 }),
  char('Summoner', [1, 82, 400, 55, 318, 22, 64, 3], true, { points: 300, map: 33, x: 84, y: 13, zen: 150000000, strength: 1500, dexterity: 6000, vitality: 6000, energy: 30000, leadership: 0 }),
  char('Assassin', [0, 17, 380, 1204, 305, 12, 51, 2], false, { points: 0, map: 7, x: 24, y: 17, zen: 98000000, strength: 32767, dexterity: 12000, vitality: 7000, energy: 1500, leadership: 0 }),
  char('Gladius', [0, 66, 377, 210, 290, 9, 40, 1], false, { points: 2100, map: 8, x: 194, y: 67, zen: 64000000, strength: 25000, dexterity: 9000, vitality: 8000, energy: 3000, leadership: 12000 }),
  char('Lunar', [2, 34, 386, -1, 276, 6, 33, 1], false, { points: 40, map: 51, x: 51, y: 216, zen: 31000000, strength: 3000, dexterity: 30000, vitality: 7000, energy: 5000, leadership: 0 }),
  char('Thorn', [0, 48, 365, 33, 251, 3, 21, 0], false, { points: 0, map: 4, x: 204, y: 77, zen: 12000000, strength: 20000, dexterity: 10000, vitality: 6000, energy: 9000, leadership: 0 }),
  char('Raven', [3, 64, 360, -1, 240, 2, 18, 0], false, { points: 0, map: 34, x: 228, y: 41, zen: 8000000, strength: 18000, dexterity: 6000, vitality: 5000, energy: 2000, leadership: 20000 }),
  char('Wolf', [1, 17, 352, -1, 222, 2, 15, 0], false, { points: 150, map: 41, x: 28, y: 76, zen: 5000000, strength: 22000, dexterity: 9000, vitality: 7000, energy: 1000, leadership: 0 }),
  char('Nyx', [0, 81, 347, -1, 205, 1, 12, 0], false, { points: 0, map: 42, x: 97, y: 185, zen: 3200000, strength: 1200, dexterity: 5000, vitality: 5000, energy: 22000, leadership: 0 }),
  char('Drako', [0, 18, 340, -1, 190, 1, 10, 0], false, { points: 75, map: 37, x: 20, y: 218, zen: 2500000, strength: 20000, dexterity: 8000, vitality: 6000, energy: 900, leadership: 0 }),
  char('Lyra', [1, 33, 331, -1, 170, 1, 8, 0], false, { points: 0, map: 30, x: 93, y: 214, zen: 1900000, strength: 2000, dexterity: 21000, vitality: 5000, energy: 3000, leadership: 0 }),
  char('Orion', [0, 1, 320, -1, 150, 0, 6, 0], false, { points: 200, map: 10, x: 125, y: 125, zen: 1200000, strength: 800, dexterity: 4000, vitality: 4000, energy: 19000, leadership: 0 }),
  char('Vex', [0, 96, 312, -1, 120, 0, 5, 0], false, { points: 0, map: 56, x: 135, y: 105, zen: 900000, strength: 15000, dexterity: 6000, vitality: 8000, energy: 1000, leadership: 0 }),
  char('Iris', [0, 34, 305, -1, 90, 0, 3, 0], false, { points: 30, map: 63, x: 120, y: 129, zen: 600000, strength: 1500, dexterity: 14000, vitality: 3000, energy: 2000, leadership: 0 }),
  char('Brom', [0, 16, 298, -1, 60, 0, 2, 0], false, { points: 0, map: 1, x: 107, y: 247, zen: 300000, strength: 11000, dexterity: 4000, vitality: 3000, energy: 500, leadership: 0 }),
  char('Kael', [0, 49, 280, -1, 30, 0, 1, 0], false, { points: 90, map: 6, x: 62, y: 115, zen: 120000, strength: 8000, dexterity: 3000, vitality: 2000, energy: 4000, leadership: 0 }),
]

export function findCharacter(name: string): Character | undefined {
  return characters.find((c) => c.name === name)
}
