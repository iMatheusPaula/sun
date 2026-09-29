import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import type { Privilege } from '@/data/accounts'
import { LOGIN_ERROR, LOGIN_ERROR_LOCK, LOGIN_FILL_ALL_FIELDS, LOGIN_SUCCESS } from '@/lang/ldajax'
import { useAccountsStore } from './accounts'

const STORAGE_KEY = 'sunonline:session'
// ldAjax::loginCheck: 5 erros seguidos bloqueiam o login por 5 minutos (webPasswordBruteForceLock)
const LOCK_ERRORS = 5
const LOCK_MINUTES = 5

function readStored(): string | null {
  try {
    return localStorage.getItem(STORAGE_KEY)
  } catch {
    return null
  }
}

function writeStored(login: string | null) {
  try {
    if (login) localStorage.setItem(STORAGE_KEY, login)
    else localStorage.removeItem(STORAGE_KEY)
  } catch {
    // sem storage (janela privada etc.): a sessão vale só até recarregar
  }
}

export const useSessionStore = defineStore('session', () => {
  const accounts = useAccountsStore()
  const login = ref<string | null>(readStored())
  const locks = new Map<string, { errors: number; date: number }>()

  const account = computed(() => (login.value ? accounts.find(login.value) ?? null : null))
  const isLogged = computed(() => account.value !== null)
  const privilege = computed<Privilege | -1>(() => account.value?.privilege ?? -1)

  // Confere o login como o ajax require=login e devolve a mensagem que ele mostrava (HTML das
  // constantes do .lang.php). Não abre a sessão: o original mostrava "Aguarde..." e só então
  // recarregava a página já logado — quem chama decide quando chamar startSession().
  function authenticate(userName: string, userPwd: string): { ok: boolean; message: string; login?: string } {
    if (!userName || !userPwd) return { ok: false, message: LOGIN_FILL_ALL_FIELDS }

    const key = userName.toLowerCase()
    const lock = locks.get(key)
    if (lock && lock.errors >= LOCK_ERRORS) {
      if (Date.now() - lock.date < LOCK_MINUTES * 60_000) return { ok: false, message: LOGIN_ERROR_LOCK }
      locks.delete(key)
    }

    const found = accounts.find(key)
    if (!found || found.password !== userPwd) {
      locks.set(key, { errors: (locks.get(key)?.errors ?? 0) + 1, date: Date.now() })
      return { ok: false, message: LOGIN_ERROR }
    }

    locks.delete(key)
    return { ok: true, message: LOGIN_SUCCESS, login: found.login }
  }

  function startSession(accountLogin: string) {
    login.value = accountLogin
    writeStored(accountLogin)
  }

  // ?page=logout: session_destroy e volta para a home
  function signOut() {
    login.value = null
    writeStored(null)
  }

  return { login, account, isLogged, privilege, authenticate, startSession, signOut }
})
