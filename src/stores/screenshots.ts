import { defineStore } from 'pinia'
import { ref } from 'vue'
import { screenshots as seed } from '@/data/screenshots'

const ONE_DAY = 24 * 60 * 60 * 1000

// Módulo screenshots: votos (voteScreenshot) com trava de 24 h por IP — aqui, por navegador.
export const useScreenshotsStore = defineStore('screenshots', () => {
  const list = ref(seed.map((s) => ({ ...s })))
  let voteLockedUntil = 0

  function find(id: number) {
    return list.value.find((s) => s.id === id)
  }

  // ORDER BY rate DESC
  function ranked() {
    return [...list.value].sort((a, b) => b.rate - a.rate)
  }

  function vote(id: number): { ok: boolean; message: string } | null {
    const shot = find(id)
    if (!shot) return null
    if (voteLockedUntil > Date.now()) return { ok: false, message: 'Você só pode votar novamente nessa screenshot em 24 horas.' }
    shot.rate++
    voteLockedUntil = Date.now() + ONE_DAY
    return { ok: true, message: 'Voto computado com sucesso.' }
  }

  return { list, find, ranked, vote }
})
