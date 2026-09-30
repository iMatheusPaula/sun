<script setup lang="ts">
// paneladmin[SINCRONIZE].tpl.php + ldPanelAdmin::optionLoadSincronize (links [Sincronizar] do AdminCenter):
// ?type=1 volta a Free as contas com o vip vencido; ?type=2 desfaz os banimentos vencidos.
import { useRoute } from 'vue-router'
import { useDbStore } from '@/stores/db'
import { sprintf } from '@/lang/format'
import * as PA from '@/lang/ldpaneladmin'

const route = useRoute()
const store = useDbStore()
const now = Date.now()
let result = ''

if (route.query.type === '1') {
  result = "<div class='qdestaques2'>"
  for (const a of store.db.accounts.filter((x) => x.vip > 0)) {
    if (now > a.vipEnd) {
      result += `${sprintf(PA.LDPA_SINCRONIZE_TEXT_VIP_LOGIN, a.login)}<br />`
      a.vip = 0
    }
  }
  result += `<br /><strong>${PA.LDPA_SINCRONIZE_TEXT_SUCCESS}</strong></div>`
} else if (route.query.type === '2') {
  result = "<div class='qdestaques2'>"
  for (const b of [...store.db.bans]) {
    if (now <= b.end) continue
    const same = (x: typeof b) => x.type === b.type && x.name.toLowerCase() === b.name.toLowerCase()
    if (b.type === 1) {
      result += `${sprintf(PA.LDPA_SINCRONIZE_TEXT_BAN_LOGIN, b.name)} <br />`
      const account = store.db.accounts.find((a) => a.login.toLowerCase() === b.name.toLowerCase())
      if (account) account.blocked = false
    } else {
      result += `${sprintf(PA.LDPA_SINCRONIZE_TEXT_BAN_CHARACTER, b.name)} <br />`
      const char = store.db.characters.find((c) => c.name.toLowerCase() === b.name.toLowerCase())
      if (char) char.ctlCode = 0
    }
    store.db.bans = store.db.bans.filter((x) => !same(x))
  }
  result += `<br /><strong>${PA.LDPA_SINCRONIZE_TEXT_SUCCESS}</strong></div>`
}
</script>

<template>
  <h1 style="margin-top: 20px;">Sincronizador</h1>

  <div class="legend" style="margin-top: 25px;">
    <h3 class="legend-title"><span style="font-size: 13px;">Sincronizando...</span></h3>
    <br />
    <div class="quadrosOut" v-html="result"></div>
  </div>
</template>
