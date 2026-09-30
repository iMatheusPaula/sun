<script setup lang="ts">
// paneluser[OPEN_TICKET].tpl.php + ldPanelUser::loadOptionsOpenTicket (OPEN_TICKET MAX_OPEN pedidos abertos)
import { ref } from 'vue'
import { panelLinks, usePanelAccount } from '@/lib/paneluser'
import { OPEN_TICKET_MAX_OPEN } from '@/config/panel'
import { sprintf } from '@/lang/format'
import * as P from '@/lang/ldpaneluser'

const { store, login, characters } = usePanelAccount()
const response = ref('')

function open(event: Event) {
  const form = new FormData(event.target as HTMLFormElement)
  const get = (name: string) => String(form.get(name) ?? '')
  const opened = store.db.tickets.filter((t) => t.account === login.value && t.status === 0).length
  if (opened >= OPEN_TICKET_MAX_OPEN) {
    response.value = `<div class='qdestaques2'>${sprintf(P.LDPU_OPEN_TICKET_MAX_TICKET_OPEN, OPEN_TICKET_MAX_OPEN)}</div>`
    return
  }
  const id = Math.max(0, ...store.db.tickets.map((t) => t.id)) + 1
  store.db.tickets.push({
    id, account: login.value, character: get('character'), sector: get('sector'), subject: get('subject'),
    description: get('description'), date: Date.now(), ip: '127.0.0.1', status: 0,
  })
  response.value = `<div class='qdestaques2'>${panelLinks(P.LDPU_OPEN_TICKET_SUCCESS)}</div>`
}
</script>

<template>
  <h1 style="margin-top: 20px;">Abrir ticket</h1>
  <br />

  <div id="etc">
    <div class="legend" style="margin-bottom: 25px;">
      <form action="" method="post" @submit.prevent="open">
        <table border="0" cellpadding="1" cellspacing="1" width="438">
          <tbody>
            <tr><td colspan="2" align="left" style="background: none;"><strong>Preencha todos os campos abaixo para abrir o pedido!</strong></td></tr>
            <tr><td height="15" colspan="2" style="background: none;">&nbsp;</td></tr>
            <tr>
              <td align="right" width="20%" style="background: none;"><strong>Personagem:</strong></td>
              <td align="left" style="background: none;"> <select name="character" class="inputbox"><option v-for="c in characters" :key="c.name" :value="c.name">{{ c.name }}</option></select></td>
            </tr>
            <tr>
              <td align="right" width="20%" style="background: none;"><strong>Setor:</strong></td>
              <td align="left" style="background: none;"><input type="radio" name="sector" checked value="Suporte Tecnico" />Suporte Técnico<br /><input type="radio" name="sector" value="Suporte Financeiro" />Suporte Financeiro</td>
            </tr>
            <tr>
              <td align="right" width="20%" style="background: none;"><strong>Assunto:</strong></td>
              <td align="left" style="background: none;"><input name="subject" type="text" class="inputbox" size="50" maxlength="30" /></td>
            </tr>
            <tr>
              <td align="right" width="20%" style="background: none;"><strong>Descrição:</strong></td>
              <td align="left" style="background: none;"><textarea name="description" cols="40" rows="6">Descreva aqui o motivo para que nossa equipe possa analizar.</textarea></td>
            </tr>
            <tr>
              <td colspan="2" style="background: none;"><div class="qdestaques">Não use os caracteres: " (aspas) ' (apostofro) ; (ponto e virgula)</div></td>
            </tr>
            <tr>
              <td align="center" colspan="2" style="background: none;"><input type="Submit" class="button" value="Abrir Pedido" /></td>
            </tr>
          </tbody>
        </table>
      </form>
    </div>
    <div class="quadrosOut" v-html="response"></div>
  </div>
</template>
