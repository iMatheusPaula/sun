<script setup lang="ts">
// paneladmin[ADD_CASH|REMOVE_CASH].tpl.php + ldPanelAdmin::optionLoadAddCash(1|2): soma ou tira SGolds, Golds ou
// Pontos (webCash) de uma conta.
import { reactive, ref } from 'vue'
import { useAccountsStore } from '@/stores/accounts'
import { isEmpty, isNumeric } from '@/lib/paneladmin'
import { CASH_NAME, CASH_NAME2, POINTS_NAME } from '@/config/settings'
import { sprintf } from '@/lang/format'
import * as PA from '@/lang/ldpaneladmin'

const props = defineProps<{ type: 1 | 2 }>()
const accounts = useAccountsStore()
const blank = () => ({ account: '', type: '1', amount: '0' })
const form = reactive(blank())
const response = ref('')
const CURRENCIES: Record<string, ['cash' | 'cash2' | 'points', string]> = { 1: ['cash', CASH_NAME], 2: ['cash2', CASH_NAME2], 3: ['points', POINTS_NAME] }

// o POST recarregava a página: a resposta aparece com o formulário vazio
function submit() {
  const { account, type, amount } = form
  Object.assign(form, blank())
  const red = (m: string) => `<div class="qdestaques">${m}</div>`
  if (isEmpty(account)) return (response.value = red(PA.LDPA_FILL_LOGIN))
  if (!isNumeric(amount)) return (response.value = red(PA.LDPA_ADD_CASH_TEXT_INVALID_AMOUNT))
  const currency = CURRENCIES[type]
  if (!currency) return (response.value = red(PA.LDPA_ADD_CASH_TEXT_INVALID_TYPE))
  const a = accounts.find(account)
  if (!a) return (response.value = red(PA.LDPA_INVALID_LOGIN))
  const [column, name] = currency
  const value = Math.trunc(Number(amount)) // a coluna é inteira
  a[column] += props.type === 1 ? value : -value
  response.value = `<div class='qdestaques2'>${sprintf(props.type === 1 ? PA.LDPA_ADD_CASH_TEXT_ADD_SUCCESS : PA.LDPA_ADD_CASH_TEXT_REMOVE_SUCCESS, value, name)}</div>`
}
</script>

<template>
  <h1 style="margin-top: 20px;">{{ type === 1 ? 'Adcionar' : 'Remover' }} {{ CASH_NAME }}</h1>

  <div class="legend" style="margin-top: 25px; padding: 10px;">
    <h3 class="legend-title"><span style="font-size: 13px;">Preencha os dados abaixo:</span></h3>
    <form action="" method="post" @submit.prevent="submit">
      <em>Login:</em><br /><input v-model="form.account" name="account" type="text" class="inputbox" /> <br />
      <em>Moeda a ser alterada:</em><br /><select v-model="form.type" name="type"> <option value="1">{{ CASH_NAME }}</option> <option value="2">{{ CASH_NAME2 }}</option> <option value="3">{{ POINTS_NAME }}</option> </select> <br />
      <em>Valor:</em><br /><input v-model="form.amount" name="amount" type="text" class="inputbox" /><br />
      <input type="submit" :value="type === 1 ? 'Adicionar.' : 'Remover.'" class="button" :style="type === 1 ? 'margin-top: 6px;' : 'margin-top: 10px;'" />
    </form>
    <div class="quadrosOut" v-html="response"></div>
  </div>
</template>
