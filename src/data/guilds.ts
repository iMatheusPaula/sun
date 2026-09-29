// Guilds de exemplo (reference/_build/render.php: rankingGuilds / rankingGuildProfile).
import { guildLogos } from './placeholders'

// G_Status do MU: 128 = Guild Master, 64 = Assistente, 32 = Battle Master, 0 = membro
export interface GuildMember {
  name: string
  level: number // G_Level (1 = mestre em servidores sem G_Status)
  status: 0 | 32 | 64 | 128
}

export interface Guild {
  name: string
  logo: string
  score: number
  master: string
  members: GuildMember[]
}

export const guilds: Guild[] = [
  {
    name: 'Legends', logo: guildLogos[0]!, score: 5320, master: 'DarkLord',
    members: [
      { name: 'DarkLord', level: 1, status: 128 }, { name: 'Kira', level: 0, status: 64 }, { name: 'Zeus', level: 0, status: 32 },
      { name: 'Elfa', level: 0, status: 0 }, { name: 'Mago', level: 0, status: 0 }, { name: 'Summoner', level: 0, status: 0 },
    ],
  },
  { name: 'Immortals', logo: guildLogos[1]!, score: 4875, master: 'Assassin', members: [{ name: 'Assassin', level: 1, status: 128 }, { name: 'Gladius', level: 0, status: 64 }] },
  { name: 'Phoenix', logo: guildLogos[2]!, score: 4110, master: 'Lunar', members: [{ name: 'Lunar', level: 1, status: 128 }, { name: 'Thorn', level: 0, status: 64 }] },
  { name: 'Valhalla', logo: guildLogos[3]!, score: 3902, master: 'Raven', members: [{ name: 'Raven', level: 1, status: 128 }, { name: 'Lyra', level: 0, status: 0 }] },
  { name: 'Tempest', logo: guildLogos[0]!, score: 2750, master: 'Wolf', members: [{ name: 'Wolf', level: 1, status: 128 }, { name: 'Nyx', level: 0, status: 64 }, { name: 'Orion', level: 0, status: 0 }] },
  { name: 'Nomads', logo: guildLogos[1]!, score: 1180, master: 'Drako', members: [{ name: 'Drako', level: 1, status: 128 }, { name: 'Vex', level: 0, status: 0 }] },
]

export function findGuild(name: string): Guild | undefined {
  return guilds.find((g) => g.name === name)
}

export function guildOf(character: string): Guild | undefined {
  return guilds.find((g) => g.members.some((m) => m.name === character))
}

// gerateRankingGuilds: assistente = G_Status 64, battle master = G_Status 32 (vazio quando não há)
export function memberWithStatus(guild: Guild, status: 32 | 64): string {
  return guild.members.find((m) => m.status === status)?.name ?? ''
}
