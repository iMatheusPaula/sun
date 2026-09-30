<script setup lang="ts">
// paneluser[TRANSFER_CASH].tpl.php + ldPanelUser::loadOptionsTransferCash (?action=transfer)
import { reactive, ref } from 'vue'
import { usePanelAccount } from '@/lib/paneluser'
import { useAccountsStore } from '@/stores/accounts'
import { CASH_NAME, CASH_NAME2 } from '@/config/settings'
import { sprintf } from '@/lang/format'
import * as P from '@/lang/ldpaneluser'

const { account } = usePanelAccount()
const accounts = useAccountsStore()
const a = account.value!
const form = reactive({ usernameDestine: '', typeCash: '1', amount: '' })
const response = ref('')

// as exceções do original: código 2 = quadro vermelho, 1 = quadro azul
function transfer() {
  const fail = (m: string) => (response.value = `<div class='qdestaques'>${m}</div>`)
  const amountText = form.amount.trim()
  const amount = Number(amountText)
  if (form.usernameDestine === '') return fail(P.LDPU_TRANSFER_CASH_FILL_LOGIN_DESTINY)
  if (amountText === '' || Number.isNaN(amount) || amount < 1) return fail(P.LDPU_TRANSFER_CASH_INVALID_AMOUNT)
  if (form.typeCash !== '1' && form.typeCash !== '2') return fail(P.LDPU_TRANSFER_CASH_INVALID_TYPE)
  const target = accounts.find(form.usernameDestine)
  if (!target) return fail(sprintf(P.LDPU_TRANSFER_CASH_LOGIN_NOT_FOUND, form.usernameDestine))
  if (target.login === a.login) return fail(P.LDPU_TRANSFER_CASH_CANT_TRANSF_TO_YOU_LOGIN)
  if (!a.resale) return fail(P.LDPU_TRANSFER_CASH_NOT_HAVE_PREVILEGY)

  const field = form.typeCash === '1' ? 'cash' : 'cash2'
  const coin = form.typeCash === '1' ? CASH_NAME : CASH_NAME2
  // o UPDATE só debita com saldo MAIOR que a quantia: transferir o saldo inteiro também falha
  if (a[field] < amount || !(a[field] > amount)) return fail(sprintf(P.LDPU_TRANSFER_CASH_NOT_HAVE_THIS_AMOUNT, coin))
  a[field] -= amount
  target[field] += amount
  response.value = `<div class='qdestaques2'>${sprintf(P.LDPU_TRANSFER_CASH_TEXT_SUCCESS, amount, coin, target.login)}</div>`
}
</script>

<template>
  <h1 style="margin-top: 20px;">Transferir {{ CASH_NAME }}/{{ CASH_NAME2 }}</h1>
  <br />

  <div id="etc" style="margin-bottom: 26px;" v-html="response"></div>

  <form action="" method="post" @submit.prevent="transfer">
    <div class="legend" style="margin-bottom: 25px;">
      <h3 class="legend-title"><span style="font-size: 13px;">Dados da minha conta</span></h3>
      <div style="margin-bottom: 8px; margin-top: 10px;">
        <em>Meu login:</em><br /><input type="text" class="inputbox" :value="a.login" disabled /><br />
        <em>Quantidade de {{ CASH_NAME }}:</em><br /><input type="text" class="inputbox" :value="a.cash" disabled /><br />
        <em>Quantidade de {{ CASH_NAME2 }}:</em><br /><input type="text" class="inputbox" :value="a.cash2" disabled /><br />
      </div>
    </div>

    <div class="legend" style="margin-bottom: 1px;">
      <h3 class="legend-title"><span style="font-size: 13px;">Dados para trasferencia</span></h3>
      <div style="margin-bottom: 8px; margin-top: 10px;">
        <em>Login de destino:</em><br /><input v-model="form.usernameDestine" name="usernameDestine" type="text" class="inputbox" maxlength="10" /><br />
        <em>Tipo de moeda a transferir:</em><br /><select v-model="form.typeCash" name="typeCash"><option value="1">{{ CASH_NAME }}</option><option value="2">{{ CASH_NAME2 }}</option></select><br />
        <em>Quantidade a transferir:</em><br /><input v-model="form.amount" name="amount" type="text" class="inputbox" maxlength="10" /><br />
      </div>
    </div>

    <input type="submit" value="Transferir" class="button" style="margin-top: 6px;" /><br />
  </form>
</template>
