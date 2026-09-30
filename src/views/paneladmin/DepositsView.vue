<script setup lang="ts">
// paneladmin[DEPOSITS_IN_OPERATION|DEPOSITS_COMPLETING|DEPOSITS_FALSE].tpl.php + ldPanelAdmin::optionLoadManagerDeposits
// (0 em andamento, 1 concluídos, 2 falsos): os comprovantes do Confirmar Pagamento, um formulário por depósito.
// Salvar muda o status (Concluído credita os SGolds no login do formulário; Rejeitado / Removido tira) e grava o
// comentário do admin; o campo Valor é só de conferência (o original não o gravava). O original mostrava o comentário
// do admin com nl2br dentro do <textarea>, e cada salvamento acumulava <br /> no texto; aqui ele volta como foi escrito.
import { computed, reactive, ref, watch } from 'vue'
import { useDbStore } from '@/stores/db'
import { useAccountsStore } from '@/stores/accounts'
import { openUpload, uploadUrl } from '@/lib/paneladmin'
import { CASH_NAME } from '@/config/settings'
import { sprintf } from '@/lang/format'
import type { Deposit } from '@/data/db'
import * as PA from '@/lang/ldpaneladmin'

const props = defineProps<{ status: 0 | 1 | 2 }>()
const store = useDbStore()
const accounts = useAccountsStore()
const result = ref('')

const FIELDS: [key: string, label: string][] = [
  ['nterminal', PA.LDPA_MDEPOSITS_TEXT_TERMINAL_NUMBER], ['ntransferencia', PA.LDPA_MDEPOSITS_TEXT_TRASNFER_NUMBER],
  ['agencia_acolhedora', PA.LDPA_MDEPOSITS_TEXT_AGENCY_HOST], ['nsequencia', PA.LDPA_MDEPOSITS_TEXT_NUMBER_SEQ], ['ctr', PA.LDPA_MDEPOSITS_TEXT_CTR],
  ['caixa_eletronico', PA.LDPA_MDEPOSITS_TEXT_CASH_MACHINE], ['nenvelope', PA.LDPA_MDEPOSITS_TEXT_ENVELOPE_NUMBER],
  ['ndocumento', PA.LDPA_MDEPOSITS_TEXT_DOC_NUMBER], ['ncontrole', PA.LDPA_MDEPOSITS_TEXT_CONTROL_NUMBER], ['noperador', PA.LDPA_MDEPOSITS_TEXT_OPERATOR_NUMBER],
]
const STATUS = [PA.LDPA_MDEPOSITS_STAT_IN_PROGRESS, PA.LDPA_MDEPOSITS_STAT_COMPLETED, PA.LDPA_MDEPOSITS_STAT_REJECTED]

// "R$ 50,00" → "50.00"
const plainValue = (amount: string) => amount.replace(/R\$/g, '').replace(/ /g, '').replace(/,/g, '.')

// os campos editáveis de cada formulário (o que o POST mandaria), refeitos a cada "recarga"
type DepositForm = { login: string; cash: string; valor: string; comentario_adm: string; status: string }
const forms = reactive<Record<number, DepositForm>>({})
const list = computed(() => store.db.deposits.filter((d) => d.status === props.status).sort((a, b) => b.id - a.id))
watch(list, (deposits) => {
  for (const d of deposits) {
    forms[d.id] ??= { login: d.account, cash: String(d.cash), valor: plainValue(d.amount), comentario_adm: d.adminComment, status: '0' }
  }
}, { immediate: true })

// Write=true&Id=N: optionLoadManagerDepositsModifyStats
function save(d: Deposit) {
  const f = forms[d.id]!
  delete forms[d.id] // o POST recarregava a página
  const cash = Math.trunc(Number(f.cash)) || 0
  const status = Math.trunc(Number(f.status)) || 0
  const account = accounts.find(f.login)
  if (status >= 0 && status <= 3) {
    d.adminComment = f.comentario_adm
    d.status = status === 3 ? 2 : (status as 0 | 1 | 2)
    if (status === 1 && account) account.cash += cash
    if (status === 3 && account) account.cash -= cash
  }
  const label = status === 3 ? `${PA.LDPA_MDEPOSITS_STAT_REJECTED_AND_REMOVE} ${cash}&nbsp;${CASH_NAME}` : STATUS[status] ?? String(status)
  result.value = `${sprintf(PA.LDPA_MDEPOSITS_TEXT_SUCCESS, d.id)} <em>${label}</em>.<br />${CASH_NAME}: ${cash}.`
  // se o depósito continua nesta lista, o formulário volta com os dados gravados
  if (d.status === props.status) forms[d.id] = { login: d.account, cash: String(d.cash), valor: plainValue(d.amount), comentario_adm: d.adminComment, status: '0' }
}

const lines = (text: string) => text.split('\n')
const title = ['Depósitos em andamento', 'Depósitos concluidos', 'Depósitos falsos'][props.status]
</script>

<template>
  <h1 style="margin-top: 20px;">{{ title }}</h1>
  <br /><br />
  <div id="logs_" class="quadrosOut">
    <div v-if="result" class="qdestaques" v-html="result"></div>
    <form v-for="d in list" :key="d.id" action="" method="post" class="quadros" @submit.prevent="save(d)">
      <em>{{ PA.LDPA_MDEPOSITS_TEXT_ID }}:</em> <strong>{{ d.id }}</strong><br />
      <em>{{ PA.LDPA_MDEPOSITS_TEXT_LOGIN }}:</em> <input v-model="forms[d.id]!.login" name="login" type="text" /><br />
      <em>{{ PA.LDPA_MDEPOSITS_TEXT_TOTAL_CASH }} {{ CASH_NAME }}:</em> <input v-model="forms[d.id]!.cash" name="cash" type="text" /><br />
      <em>{{ PA.LDPA_MDEPOSITS_TEXT_BANK }}:</em> <strong>{{ d.bank }}</strong><br />
      <template v-for="[key, label] in FIELDS" :key="key"><em>{{ label }}:</em> <strong>{{ d.fields[key] ?? '' }}</strong><br /></template>
      <em>{{ PA.LDPA_MDEPOSITS_TEXT_DATE }}:</em> <strong>{{ d.date }}</strong><br />
      <em>{{ PA.LDPA_MDEPOSITS_TEXT_HOUR }}:</em> <strong>{{ d.hour }}</strong><br />
      <em>{{ PA.LDPA_MDEPOSITS_TEXT_PAY_IN }}:</em> <strong>{{ d.paidAt }}</strong><br />
      <em>{{ PA.LDPA_MDEPOSITS_TEXT_ANNEX }}:</em> <strong><template v-if="d.attachment.length < 2">{{ PA.LDPA_MDEPOSITS_TEXT_ANNEX_NOT_SEND }}</template><a v-else :href="uploadUrl('deposits', d.attachment)" target="_blank" @click="openUpload($event, uploadUrl('deposits', d.attachment))">{{ PA.LDPA_MDEPOSITS_TEXT_ANNEX_SEND }}</a></strong><br />
      <em>{{ PA.LDPA_MDEPOSITS_TEXT_COMMENT }}:</em> <strong><template v-for="(l, i) in lines(d.comment)" :key="i"><br v-if="i" />{{ l }}</template></strong><br />
      <em>{{ PA.LDPA_MDEPOSITS_TEXT_VALUE }}:</em> <input v-model="forms[d.id]!.valor" name="valor" type="text" /><template v-if="Number(plainValue(d.amount)) < 1">&nbsp;<strong style="color:red;">{{ PA.LDPA_MDEPOSITS_TEXT_VALUE_WARNING }}</strong></template><br />
      <em>{{ PA.LDPA_MDEPOSITS_TEXT_COMMENT_ADM }}:</em> <textarea v-model="forms[d.id]!.comentario_adm" name="comentario_adm"></textarea><br />
      <em>{{ PA.LDPA_MDEPOSITS_TEXT_NEW_STATUS }}:</em> <select v-model="forms[d.id]!.status" name="status"><option value="0">{{ PA.LDPA_MDEPOSITS_STAT_IN_PROGRESS }}</option><option value="1">{{ PA.LDPA_MDEPOSITS_STAT_COMPLETED }}</option><option value="2">{{ PA.LDPA_MDEPOSITS_STAT_REJECTED }}</option><option value="3">{{ PA.LDPA_MDEPOSITS_STAT_REJECTED_AND_REMOVE }} X {{ CASH_NAME }}</option></select>
      <input type="submit" :value="PA.LDPA_TEXT_SAVE_SUBMIT" class="button" />
    </form>
  </div>
</template>

<style scoped>
/* o <style> que o .tpl imprimia antes da lista */
#logs_ input, #logs_ select {
  color: #000;
}
</style>
