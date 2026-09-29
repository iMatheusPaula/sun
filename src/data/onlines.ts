// Contas conectadas (ldOnlines: MEMB_STAT + AccountCharacter + Character; reference/_build/render.php: onlinesRows).
// ConnectTM no formato em que a extensão mssql do PHP devolvia o datetime.
export const onlines: { character: string; connectedAt: string; resets: number; vip: number; server: string }[] = [
  { character: 'DarkLord', connectedAt: 'Sep 23 2026 7:02PM', resets: 412, vip: 3, server: 'Server 1' },
  { character: 'Kira', connectedAt: 'Sep 23 2026 7:40PM', resets: 398, vip: 2, server: 'Server 1' },
  { character: 'Zeus', connectedAt: 'Sep 23 2026 8:05PM', resets: 371, vip: 0, server: 'Server 2' },
  { character: 'Elfa', connectedAt: 'Sep 23 2026 8:11PM', resets: 350, vip: 1, server: 'Server 1' },
  { character: 'Mago', connectedAt: 'Sep 23 2026 8:26PM', resets: 96, vip: 0, server: 'Server 2' },
  { character: 'Summoner', connectedAt: 'Sep 23 2026 8:31PM', resets: 22, vip: 5, server: 'Server 1' },
]
