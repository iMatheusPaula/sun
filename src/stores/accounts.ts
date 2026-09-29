import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { accounts as seed, type Account } from '@/data/accounts'

const STORAGE_KEY = 'sunonline:accounts'

function readStored(): Account[] {
  try {
    const raw = localStorage.getItem(STORAGE_KEY)
    return raw ? (JSON.parse(raw) as Account[]) : []
  } catch {
    return []
  }
}

// Contas de exemplo + as criadas no cadastro (guardadas no navegador, já que não há backend).
export const useAccountsStore = defineStore('accounts', () => {
  const registered = ref<Account[]>(readStored())
  const all = computed(() => [...seed, ...registered.value])

  // memb___id: o SQL Server compara sem diferenciar maiúsculas
  function find(login: string): Account | undefined {
    const key = login.toLowerCase()
    return all.value.find((a) => a.login.toLowerCase() === key)
  }

  function add(account: Account) {
    registered.value.push(account)
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(registered.value))
    } catch {
      // sem storage: a conta vale até recarregar a página
    }
  }

  return { all, find, add }
})
