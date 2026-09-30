import { defineStore } from 'pinia'
import { computed } from 'vue'
import type { AccountRow } from '@/data/db'
import { useDbStore } from './db'

// Contas (MEMB_INFO e afins) no banco do front: as de exemplo e as criadas no cadastro.
export const useAccountsStore = defineStore('accounts', () => {
  const store = useDbStore()
  const all = computed(() => store.db.accounts)

  // memb___id: o SQL Server compara sem diferenciar maiúsculas
  function find(login: string): AccountRow | undefined {
    const key = login.toLowerCase()
    return store.db.accounts.find((a) => a.login.toLowerCase() === key)
  }

  function add(account: AccountRow) {
    store.db.accounts.push(account)
  }

  return { all, find, add }
})
