<script setup lang="ts">
// paneluser[CONFIRM_PAYMENT].tpl.php + ldPanelUser::loadOptionsConfirmPayment: comprovante de depósito
// (show_hide_blocks mostra os campos do banco escolhido). O anexo fica como data URL no banco do front.
import { ref } from 'vue'
import { panelLinks, usePanelAccount } from '@/lib/paneluser'
import { CASH_NAME } from '@/config/settings'
import { CONFIRM_PAYMENT_MAX_OPEN } from '@/config/panel'
import { sprintf } from '@/lang/format'
import * as P from '@/lang/ldpaneluser'

type Bank = 'Opt_Bradesco' | 'Opt_Itau' | 'Opt_BBrasil' | 'Opt_CXEcon' | 'Opt_Loterica' | 'Opt_CXAqui'
const BANKS: [value: string, label: string, block: Bank][] = [
  ['Banco Bradesco ou Banco Postal', 'Banco Bradesco ou Banco Postal', 'Opt_Bradesco'], ['Banco Itau', 'Banco Itaú', 'Opt_Itau'],
  ['Banco do Brasil', 'Banco do Brasil', 'Opt_BBrasil'], ['Caixa Economica Federal', 'Caixa Econômica Federal', 'Opt_CXEcon'],
  ['Loterica', 'Lotérica', 'Opt_Loterica'], ['Caixa Aqui', 'Caixa Aqui', 'Opt_CXAqui'],
]
// campos de cada banco: [nome do campo, rótulo, valor inicial]
const FIELDS: Record<Bank, [name: string, label: string, initial?: string][]> = {
  Opt_Bradesco: [['nterminal', 'Número terminal'], ['ntransferencia', 'Número transferência'], ['agencia_acolhedora', 'Agência acolhedora'], ['nsequencia', 'Número Sequência']],
  Opt_Itau: [['ctr', 'CTR'], ['caixa_eletronico', 'Caixa Eletrônico', 'Ex: 123456/1234']],
  Opt_BBrasil: [['nenvelope', 'Número Envelope'], ['ndocumento', 'Número Documento']],
  Opt_CXEcon: [['nterminal', 'Número do terminal']],
  Opt_Loterica: [['ncontrole', 'Número de controle'], ['nterminal', 'Número do terminal']],
  Opt_CXAqui: [['noperador', 'Número do operador']],
}

const { store, login } = usePanelAccount()
const bank = ref<Bank | null>(null)
const sent = ref(false)
const response = ref('')
const fileInput = ref<HTMLInputElement | null>(null)

function readFile(file: File): Promise<string> {
  return new Promise((resolve) => {
    const reader = new FileReader()
    reader.onload = () => resolve(String(reader.result))
    reader.onerror = () => resolve('')
    reader.readAsDataURL(file)
  })
}

async function confirm(event: Event) {
  const formEl = event.target as HTMLFormElement
  const form = new FormData(formEl)
  const get = (name: string) => String(form.get(name) ?? '')
  const empty = (name: string) => get(name) === '' || get(name) === '0'
  let errors = ''
  const open = store.db.deposits.filter((d) => d.account === login.value && d.status === 0).length
  if (open >= CONFIRM_PAYMENT_MAX_OPEN) errors += `<ul><li> ${sprintf(P.LDPU_CONFIRM_DEPOSIT_ERROR_LIMIT_CONFIRM_OPEN, CONFIRM_PAYMENT_MAX_OPEN)}. </li></ul>`
  if (empty('golds')) errors += `<ul><li> ${P.LDPU_CONFIRM_DEPOSIT_FILL_AMOUNT_CASH} ${CASH_NAME}. </li></ul>`
  if (empty('bank')) errors += `<ul><li> ${P.LDPU_CONFIRM_DEPOSIT_FILL_BANK} </li></ul>`
  if (empty('data')) errors += `<ul><li> ${P.LDPU_CONFIRM_DEPOSIT_FILL_DATE} </li></ul>`
  if (empty('hora')) errors += `<ul><li> ${P.LDPU_CONFIRM_DEPOSIT_FILL_HOUR} </li></ul>`
  if (empty('valor')) errors += `<ul><li> ${P.LDPU_CONFIRM_DEPOSIT_FILL_VALUE} </li></ul>`
  if (empty('pago_em')) errors += `<ul><li> ${P.LDPU_CONFIRM_DEPOSIT_FILL_PAY_IN} </li></ul>`
  if (errors) {
    response.value = `<div class="qdestaques"><strong><em>${P.LDPU_CONFIRM_DEPOSIT_ERROR_FOUND}:</em></strong> <br />${errors}</div>`
    return
  }
  // anexo só em JPG/PNG, como no original (sem ele, o depósito vai sem comprovante)
  const image = fileInput.value?.files?.[0]
  const attachment = image && ['image/jpeg', 'image/pjpeg', 'image/png'].includes(image.type) ? await readFile(image) : ''
  // os campos do banco escolhido (vários bancos repetem o nome "nterminal")
  const field = (id: string) => (formEl.querySelector(`#${id}`) as HTMLInputElement | null)?.value ?? ''
  const fields = Object.fromEntries((bank.value ? FIELDS[bank.value] : []).map(([name]) => [name, field(`${bank.value}_${name}`)]))
  const id = Math.max(0, ...store.db.deposits.map((d) => d.id)) + 1
  store.db.deposits.push({
    id, account: login.value, cash: Math.trunc(Number(get('golds'))) || 0, bank: get('bank'), fields, date: get('data'), hour: get('hora'),
    paidAt: get('pago_em'), attachment, comment: get('comment'), amount: get('valor'), adminComment: '', status: 0,
  })
  sent.value = true
  response.value = `<div class="qdestaques">${panelLinks(P.LDPU_CONFIRM_DEPOSIT_SUCCESS)}</div>`
}

const alertCash = () => alert(`Coloque a quantidade de ${CASH_NAME} aqui.`)
const alertJpg = () => alert('Somente fotos com a extenssão JPG.')
const clearFile = () => { if (fileInput.value) fileInput.value.value = '' }
</script>

<template>
  <h1 style="margin-top: 20px;">Confirmar pagamento</h1>
  <br />

  <div id="etc">
    <div class="legend" style="margin-bottom: 25px;">
      <h3 class="legend-title"><span style="font-size: 13px;">Digite os dados abaixo:</span></h3>
      <div style="margin-bottom: 8px; margin-top: 10px;">
        <div class="qdestaques2"><strong>Atenção:</strong> A má utilização deste serviço pode causar o bloqueio permanente de sua conta.</div>
        <form v-show="!sent" id="FormConfirm" method="post" enctype="multipart/form-data" action="" @submit.prevent="confirm">
          <table cellpadding="0" cellspacing="2" width="100%">
            <tbody>
              <tr>
                <td style="width:65%; background: none;" valign="top">
                  Quantia de {{ CASH_NAME }}<br /><input name="golds" type="text" class="inputbox" value="0" maxlength="10" /> <a href="javascript:void(0);" @click.prevent="alertCash">[?]</a><br />
                  Qual banco você usou<br />
                  <template v-for="[value, label, block] in BANKS" :key="block"><input name="bank" type="radio" :value="value" @click="bank = block" /> {{ label }}<br /></template>
                </td>
                <td style="width:35%; background: none;">
                  <div v-for="(fields, block) in FIELDS" :id="block" :key="block" :style="bank === block ? 'display:block;' : 'display:none;'">
                    <template v-for="[name, label, initial] in fields" :key="name">{{ label }}<br /><input :id="`${block}_${name}`" :name="name" class="inputbox" type="text" maxlength="15" :value="initial" :disabled="bank !== null && bank !== block" /><br /></template>
                  </div>
                  Data<br /><input name="data" type="text" class="inputbox" value="Ex: 01/01/2000" maxlength="10" /><br />
                  Hora<br /><input name="hora" type="text" class="inputbox" value="Ex: 00:00:00" maxlength="8" /><br />
                  Valor pago<br /><input name="valor" type="text" class="inputbox" value="R$ 0.00" maxlength="10" /><br />
                  Pago em<br /><select name="pago_em" class="inputbox"><option></option><option value="Atendente">Atendente</option><option value="Auto Atendimento">Auto Atendimento</option><option value="Trans. Eletrônica">Trans. Eletrônica</option></select><br />
                  Anexo Comprovante<br /><input id="image" ref="fileInput" type="file" name="image" size="10" /> <a href="javascript:void(0);" @click.prevent="clearFile">[x]</a>&nbsp;<a href="javascript:void(0);" @click.prevent="alertJpg">[?]</a><br />
                  Comentário<br /><textarea rows="5" style="width:200px" name="comment">Máximo 200 caracters.</textarea>
                </td>
              </tr>
            </tbody>
          </table>
          <input type="submit" value="Enviar Confirmação" class="button" style="margin-top: 6px;" />
        </form>
        <div class="quadrosOut" v-html="response"></div>
      </div>
    </div>
  </div>
</template>
