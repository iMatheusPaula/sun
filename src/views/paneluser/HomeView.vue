<script setup lang="ts">
// paneluser.tpl.php + ldPanelUser::loadOptionsDefault — "Minha Conta"
import { computed } from 'vue'
import { usePanelAccount } from '@/lib/paneluser'
import { CASH_NAME, CASH_NAME2, POINTS_NAME, VIP_NAMES, className } from '@/config/settings'
import { phpDate } from '@/lib/date'
import { sprintf } from '@/lang/format'
import * as P from '@/lang/ldpaneluser'

const { store, account, characters } = usePanelAccount()

const plan = computed(() => {
  const a = account.value!
  return VIP_NAMES[a.vip] + (a.vip > 0 ? `, ${P.LDPU_OPTIONS_DEFAULT_FLAT_EXPIRE}: ${phpDate('d/m/Y g:i a', new Date(a.vipEnd))}` : '')
})

// checkBanAccount / checkBanCharacter: devolvem o fim do ban e, se já passou, desbanem
function banEnd(name: string, type: 1 | 2): number {
  const bans = store.db.bans
  const i = bans.findIndex((b) => b.name === name && b.type === type)
  const end = i === -1 ? 0 : bans[i]!.end
  if (Date.now() > end) {
    if (type === 1) account.value!.blocked = false
    else {
      const c = store.db.characters.find((ch) => ch.name === name)
      if (c) c.ctlCode = 0
    }
    if (i !== -1) bans.splice(i, 1)
  }
  return end
}

// calculado uma vez ao abrir a tela, como a página do original
const a = account.value!
const accountBan = a.blocked && a.activationHash === null
  ? sprintf(P.LDPU_OPTIONS_DEFAULT_BANNED_ACCOUNT, phpDate('d/m/Y g:i a', new Date(banEnd(a.login, 1))))
  : a.activationHash !== null ? sprintf(P.LDPU_OPTIONS_DEFAULT_NOT_ACTIVE_ACCOUNT, a.login) : ''
const rows = characters.value.map((c) => ({ ...c }))
const characterBans = rows.filter((c) => c.ctlCode === 1)
  .map((c) => sprintf(P.LDPU_OPTIONS_DEFAULT_BANNED_CHARACTER, c.name, phpDate('d/m/Y g:i a', new Date(banEnd(c.name, 2)))))
</script>

<template>
  <h1 style="margin-top: 20px;">Minha Conta</h1>

  <div class="legend" style="margin-top: 25px;">
    <h3 class="legend-title"><span style="font-size: 13px;">Status da minha conta</span></h3>
    <ul style="margin: 10px 0px 10px 0px;">
      <li>Conta no plano: <strong>{{ plan }}</strong></li>
      <li>Saldo de {{ CASH_NAME }}: <strong>{{ Math.trunc(a.cash) }}</strong></li>
      <li>Saldo de {{ CASH_NAME2 }}: <strong>{{ Math.trunc(a.cash2) }}</strong></li>
      <li>Saldo de {{ POINTS_NAME }}: <strong>{{ Math.trunc(a.points) }}</strong></li>
      <li>Ultima conexão em: <strong>{{ a.connectTM }} {{ P.LDPU_OPTIONS_DEFAULT_LAST_CONNECTION }} {{ a.server }}</strong></li>
      <li>Ultima conexão pelo IP: <strong>{{ a.ip }}</strong></li>
      <div v-if="accountBan" class="qdestaques" v-html="accountBan"></div>
    </ul>
  </div>

  <div class="legend" style="margin-top: 25px;">
    <h3 class="legend-title"><span style="font-size: 13px;">Status dos meus personagens</span></h3>
    <div style="margin-bottom: 8px; margin-top: 10px;">
      <table border="0" width="50%">
        <tbody>
          <tr>
            <td align="center"><strong>Personagem</strong></td>
            <td align="center"><strong>Classe</strong></td>
            <td align="center"><strong>Resets</strong></td>
            <td align="center"><strong>PK</strong></td>
          </tr>
          <tr v-if="rows.length === 0"><td colspan="4" align="center">{{ P.LDPU_OPTIONS_DEFAULT_YOU_NOT_HAVE_CHARACTERS }}</td></tr>
          <!-- "NÃ£o": o "Não" do .class.php original está gravado em UTF-8 numa página iso-8859-1; mantido como aparecia -->
          <tr v-for="c in rows" :key="c.name"><td>{{ c.name }}</td><td>{{ className(c.class) }}</td><td>{{ c.resets }}</td><td>{{ c.pkCount > 0 ? 'Sim' : 'NÃ£o' }}</td></tr>
        </tbody>
      </table>
      <div v-for="(ban, i) in characterBans" :key="i" class="qdestaques" v-html="ban"></div>
    </div>
  </div>
</template>
