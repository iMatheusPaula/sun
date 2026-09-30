<script setup lang="ts">
// panelgamemaster/paneladmin[MANAGER_BAN_CHARACTER].tpl.php + ldPanelAdmin::optionLoadManagerBanCharacters:
// bane (CtlCode 1 + linha em webBanneds) ou desbane o personagem.
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
const blank = () => ({ character: '', action: '1', days: '0', description: '' })
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
  if (isEmpty(form.character)) return red(PA.LDPA_FILL_CHARACTER)
  if (!isNumeric(form.days)) return red(PA.LDPA_MBAN_CHARACTERS_TEXT_INVALID_DAYS)
  const char = store.db.characters.find((c) => c.name.toLowerCase() === form.character.toLowerCase())
  if (!char) return red(PA.LDPA_INVALID_CHARACTER)

  const days = Math.trunc(Number(form.days))
  if (form.action === '1') {
    char.ctlCode = 1
    const now = Date.now()
    store.db.bans.push({ name: form.character, type: 2, begin: now, end: now + days * 86400_000, by: accounts.find(session.login ?? '')?.name ?? '', description: form.description })
    return blue(sprintf(PA.LDPA_MBAN_CHARACTERS_TEXT_SUCCESS, days))
  } else if (form.action === '2') {
    char.ctlCode = 0
    store.db.bans = store.db.bans.filter((b) => !(b.type === 2 && b.name.toLowerCase() === form.character.toLowerCase()))
    return blue(PA.LDPA_MBAN_CHARACTERS_TEXT_SUCCESS_UNBAN)
  }
  return ''
}
</script>

<template>
  <h1 style="margin-top: 20px;">Banir / Desbanir personagem</h1>

  <div class="legend" style="margin-top: 25px; padding: 10px;">
    <h3 class="legend-title"><span style="font-size: 13px;">Preencha os dados abaixo:</span></h3>
    <form action="" method="post" @submit.prevent="save">
      <em>Personagem:</em><br /><input v-model="form.character" name="character" type="text" class="inputbox" /> <br />
      <em>Ação:</em><br /><select v-model="form.action" name="action" class="inputbox"><option value="1">Banir</option><option value="2">Desbanir</option></select> <br />
      <em>Banir em:</em><br /><input v-model="form.days" name="days" type="text" class="inputbox" /> dias.<br />
      <em>Motivo:</em><br /><input v-model="form.description" name="description" type="text" maxlength="50" class="inputbox" /><br />
      <input type="submit" value="Salvar." class="button" style="margin-top: 10px;" />
    </form>
    <div class="quadrosOut" v-html="response"></div>
  </div>
</template>
