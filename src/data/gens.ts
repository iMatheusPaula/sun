// Contribuição de gens por família (reference/_build/render.php: rankingGens).
export type GensFamily = 'duprian' | 'vanert'

export const gensContribution: Record<GensFamily, [name: string, contribution: number][]> = {
  duprian: [
    ['DarkLord', 245800], ['Kira', 180400], ['Zeus', 152000], ['Elfa', 121500], ['Mago', 104300],
    ['Summoner', 88200], ['Assassin', 41000], ['Gladius', 12750], ['Lunar', 3200], ['Thorn', 640],
  ],
  vanert: [
    ['Raven', 212400], ['Wolf', 160900], ['Nyx', 131000], ['Drako', 99800], ['Lyra', 64200],
    ['Orion', 38500], ['Vex', 16100], ['Iris', 4999], ['Brom', 1500], ['Kael', 320],
  ],
}
