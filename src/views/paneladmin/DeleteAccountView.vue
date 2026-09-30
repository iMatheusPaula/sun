<script setup lang="ts">
// paneladmin[DELETE_ACCOUNT].tpl.php + ldPanelAdmin::optionLoadDeleteAccount: apaga a conta e o que é dela
// (personagens, baú, saldo, denúncias, comprovantes, compras de vip, tickets e banimentos da conta e dos personagens;
// personagem mestre de guild leva a guild junto).
import { ref } from 'vue'
import { useDbStore } from '@/stores/db'
import { useAccountsStore } from '@/stores/accounts'
import { isEmpty } from '@/lib/paneladmin'
import { removeFromGuilds } from '@/lib/rankings'
import * as PA from '@/lang/ldpaneladmin'

const store = useDbStore()
const accounts = useAccountsStore()
const login = ref('')
const response = ref('')

function remove() {
  const typed = login.value
  login.value = '' // o POST recarregava a página com o campo vazio
  if (isEmpty(typed)) return (response.value = `<div class="qdestaques">${PA.LDPA_FILL_LOGIN}</div>`)
  const a = accounts.find(typed)
  if (!a) return (response.value = `<div class="qdestaques">${PA.LDPA_INVALID_LOGIN}</div>`)
  const id = a.login
  const db = store.db
  const chars = db.characters.filter((c) => c.account === id).map((c) => c.name)
  for (const name of chars) removeFromGuilds(db, name)
  db.accounts = db.accounts.filter((x) => x.login !== id)
  db.characters = db.characters.filter((c) => c.account !== id)
  db.warehouses = db.warehouses.filter((w) => w.account !== id)
  db.complaints = db.complaints.filter((c) => c.account !== id)
  db.deposits = db.deposits.filter((d) => d.account !== id)
  db.vipPurchases = db.vipPurchases.filter((v) => v.account !== id)
  db.tickets = db.tickets.filter((t) => t.account !== id)
  db.bans = db.bans.filter((b) => !(b.type === 1 && b.name.toLowerCase() === id.toLowerCase()) && !(b.type === 2 && chars.includes(b.name)))
  response.value = `<div class='qdestaques2'>${PA.LDPA_DELETE_ACCOUNT_TEXT_SECCESS}</div>`
}
</script>

<template>
  <h1 style="margin-top: 20px;">Deletar conta</h1>

  <div class="legend" style="margin-top: 25px; padding: 10px;">
    <h3 class="legend-title"><span style="font-size: 13px;">Preencha os dados abaixo:</span></h3>
    <form action="" method="post" @submit.prevent="remove">
      <em>Login:</em> <input v-model="login" name="account" type="text" class="inputbox" /> <input type="submit" value="Deletar contas." class="button" />
    </form>
    <div class="quadrosOut" v-html="response"></div>
  </div>
</template>
