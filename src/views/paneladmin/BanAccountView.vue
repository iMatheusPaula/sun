<script setup lang="ts">
// panelgamemaster/paneladmin[MANAGER_BAN_ACCOUNT].tpl.php + ldPanelAdmin::optionLoadManagerBanAccounts: bane
// (bloc_code 1 + linha em webBanneds, autor = nome de quem baniu) ou desbane a conta, pelo login ou pelo personagem.
// O original validava o login antes de buscar a conta pelo personagem, então banir pelo personagem sempre dava
// "Login inválido"; aqui a conta do personagem é buscada primeiro.
import { reactive, ref } from 'vue'
import { useDbStore } from '@/stores/db'
import { useSessionStore } from '@/stores/session'
import { useAccountsStore } from '@/stores/accounts'
import { isEmpty, isNumeric } from '@/lib/paneladmin'
import { sprintf } from '@/lang/format'
import * as PA from '@/lang/ldpaneladmin'

const store = useDbStore()
const session = useSessionStore()
const accounts = useAccountsStore()
const blank = () => ({ account: '', character: '', action: '1', days: '0', description: '' })
const form = reactive(blank())
const response = ref('')

// o POST recarregava a página: a resposta aparece com o formulário vazio
function save() {
  response.value = check()
  Object.assign(form, blank())
}

function check(): string {
  const red = (m: string) => `<div class="qdestaques">${m}</div>`
  const blue = (m: string) => `<div class='qdestaques2'>${m}</div>`
  if (isEmpty(form.account) && isEmpty(form.character)) return red(PA.LDPA_MBAN_ACCOUNT_TEXT_FILL_ACCOUNT_OR_CHARACTER)
  if (!isEmpty(form.account) && !isEmpty(form.character)) return red(PA.LDPA_MBAN_ACCOUNT_TEXT_FILL_ONLY_ACCOUNT_OR_CHARACTER)
  if (!isNumeric(form.days)) return red(PA.LDPA_MBAN_ACCOUNT_TEXT_INVALID_DAYS)
  let login = form.account
  if (isEmpty(form.account)) {
    const char = store.db.characters.find((c) => c.name.toLowerCase() === form.character.toLowerCase())
    if (!char) return `<div class='qdestaques'>${PA.LDPA_MBAN_ACCOUNT_TEXT_ACCOUNT_NOT_FOUND_BY_CHARACTER}</div>`
    login = char.account
  }
  const account = accounts.find(login)
  if (!account) return red(PA.LDPA_INVALID_LOGIN)

  const days = Math.trunc(Number(form.days))
  if (form.action === '1') {
    account.blocked = true
    const now = Date.now()
    store.db.bans.push({ name: login, type: 1, begin: now, end: now + days * 86400_000, by: accounts.find(session.login ?? '')?.name ?? '', description: form.description })
    return blue(sprintf(PA.LDPA_MBAN_ACCOUNT_TEXT_SUCCESS, days))
  } else if (form.action === '2') {
    account.blocked = false
    store.db.bans = store.db.bans.filter((b) => !(b.type === 1 && b.name.toLowerCase() === login.toLowerCase()))
    return blue(`${PA.LDPA_MBAN_ACCOUNT_TEXT_SUCCESS_UNBAN}.`)
  }
  return ''
}
</script>

<template>
  <h1 style="margin-top: 20px;">Banir / Desbanir conta</h1>

  <div class="legend" style="margin-top: 25px; padding: 10px;">
    <h3 class="legend-title"><span style="font-size: 13px;">Preencha os dados abaixo:</span></h3>
    <form action="" method="post" @submit.prevent="save">
      <em>Login:</em><br /><input v-model="form.account" name="account" type="text" class="inputbox" /> <br />
      <em>Ou Personagem:</em><br /><input v-model="form.character" name="character" type="text" class="inputbox" /> <br />
      <em>Ação:</em><br /><select v-model="form.action" name="action" class="inputbox"><option value="1">Banir</option><option value="2">Desbanir</option></select> <br />
      <em>Banir em:</em><br /><input v-model="form.days" name="days" type="text" class="inputbox" /> dias.<br />
      <em>Motivo:</em><br /><input v-model="form.description" name="description" type="text" maxlength="50" class="inputbox" /><br />
      <input type="submit" value="Salvar." class="button" style="margin-top: 10px;" />
    </form>
    <div class="quadrosOut" v-html="response"></div>
  </div>
</template>
