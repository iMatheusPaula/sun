import { defineStore } from 'pinia'
import { ref, watch } from 'vue'
import { seedDb, type Db } from '@/data/db'

// O "banco" do front: começa com os dados de exemplo e guarda as alterações no navegador.
// Mudou a estrutura/seed? Suba a versão — o estado salvo antigo é descartado.
const STORAGE_KEY = 'sunonline:db'
const VERSION = 2

function load(): Db {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    if (raw) {
      const saved = JSON.parse(raw) as { version: number; db: Db }
      if (saved.version === VERSION) return saved.db
    }
  } catch {
    // storage indisponível ou corrompido: volta ao seed
  }
  return seedDb()
}

export const useDbStore = defineStore('db', () => {
  const db = ref<Db>(load())

  watch(db, (value) => {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify({ version: VERSION, db: value }))
    } catch {
      // sem storage: as alterações valem até recarregar a página
    }
  }, { deep: true })

  // volta aos dados de exemplo (em dev: __resetDb() no console)
  function reset() {
    db.value = seedDb()
  }

  return { db, reset }
})
