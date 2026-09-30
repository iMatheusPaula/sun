import { defineStore } from 'pinia'
import { computed } from 'vue'
import type { PanelScreenshot } from '@/data/db'
import { screenshotLarge, screenshotThumb } from '@/data/placeholders'
import { useDbStore } from './db'

const ONE_DAY = 24 * 60 * 60 * 1000

// imagem de uma screenshot: a enviada pelo painel (data URL) ou o placeholder das de exemplo
export function screenshotImage(s: PanelScreenshot, size: 'small' | 'common'): string {
  return s.files?.[size] ?? (size === 'small' ? screenshotThumb : screenshotLarge)
}

// Módulo screenshots (screenshots.module.php) sobre a webScreenshots do banco do front — a mesma que o painel
// altera. Votos (voteScreenshot) com trava de 24 h por IP — aqui, por navegador.
export const useScreenshotsStore = defineStore('screenshots', () => {
  const store = useDbStore()
  let voteLockedUntil = 0

  function find(id: number) {
    return store.db.screenshots.find((s) => s.id === id)
  }

  // ORDER BY [rate] DESC
  const ranked = computed(() => [...store.db.screenshots].sort((a, b) => b.rate - a.rate))

  // quem enviou: o último personagem usado pela conta (AccountCharacter.GameIDC); sem ele, o login
  function author(s: PanelScreenshot): string {
    const name = store.db.accounts.find((a) => a.login.toLowerCase() === s.account.toLowerCase())?.lastCharacter
    return !name || name.length < 2 ? s.account : name
  }

  function vote(id: number): { ok: boolean; message: string } | null {
    const shot = find(id)
    if (!shot) return null
    if (voteLockedUntil > Date.now()) return { ok: false, message: 'Você só pode votar novamente nessa screenshot em 24 horas.' }
    shot.rate++
    voteLockedUntil = Date.now() + ONE_DAY
    return { ok: true, message: 'Voto computado com sucesso.' }
  }

  return { find, ranked, author, vote }
})
