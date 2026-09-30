<script setup lang="ts">
// paneluser[SEND_SMS].tpl.php + ldPanelUser::loadOptionsSendSMS: o original mandava um e-mail para o
// endereço de SMS da operadora; sem servidor, a mensagem não sai daqui e a tela responde como no envio.
import { ref } from 'vue'
import { usePanelAccount } from '@/lib/paneluser'
import { SMS_FROM_LENGTH, SMS_RECIPIENTS, SMS_SUBJECT } from '@/config/panel'
import * as P from '@/lang/ldpaneluser'

const { login } = usePanelAccount()
const maxLength = 130 - login.value.length - SMS_FROM_LENGTH - SMS_SUBJECT.length - 2
const response = ref('')

function send() {
  response.value = `<div class='qdestaques2'><strong>${P.LDPU_SEND_SMS_SUCCESS}</strong></div>`
}
</script>

<template>
  <h1 style="margin-top: 20px;">Enviar SMS</h1>
  <br />

  <div id="etc">
    <div class="legend" style="margin-bottom: 25px;">
      <h3 class="legend-title"><span style="font-size: 13px;">Preencha os dados abaixo para enviar o SMS:</span></h3>
      <div style="margin-bottom: 8px; margin-top: 10px;">
        <form action="" method="post" @submit.prevent="send">
          <table style="width: 100%;">
            <tbody>
              <tr><td colspan="4" align="center" style="background: none;"><strong>Preencha os dados abaixo para enviar o SMS:</strong></td></tr>
              <tr>
                <td align="right" style="background: none;"><strong>Para: </strong></td>
                <td style="background: none;"><template v-for="r in SMS_RECIPIENTS" :key="r.number"><input type="radio" name="para" :value="`${r.ddd}${r.number}${r.suffix}`" /> {{ r.name }}<br /></template></td>
              </tr>
              <tr>
                <td align="right" style="background: none;"><strong>Mensagem: </strong></td>
                <td style="background: none;"><input class="inputbox" name="mensagem" type="text" :maxlength="maxLength" :value="`Maximo ${maxLength} Caracters`" size="35" /></td>
              </tr>
              <tr>
                <td align="center" colspan="2" style="background: none;"><input type="submit" value="Enviar Mensagem" class="button" /></td>
              </tr>
            </tbody>
          </table>
        </form>
      </div>
    </div>
    <div class="quadrosOut" v-html="response"></div>
  </div>
</template>
