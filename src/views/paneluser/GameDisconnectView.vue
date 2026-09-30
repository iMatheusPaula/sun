<script setup lang="ts">
// paneluser[GAME_DISCONNECT].tpl.php + ldPanelUser::loadOptionsGameDisconnect.
// O original testava a conta "offline" com a mensagem de "deve estar offline" (teste invertido) e só então
// pedia ao JoinServer para desconectar; aqui a conta online é desconectada e a offline recebe a resposta
// que o JoinServer dava para quem não está no jogo.
import { ref } from 'vue'
import { usePanelAccount } from '@/lib/paneluser'
import * as P from '@/lang/ldpaneluser'
import { GAME_ERROR_DISCONNECT, GAME_SUCCESS_DISCONNECT } from '@/lang/ldgame'

const { account, checkPassword } = usePanelAccount()
const password = ref('')
const response = ref('')

function disconnect() {
  const a = account.value!
  const blue = (m: string) => `<div class='qdestaques2'>${m.replaceAll('\\n', '<br />')}</div>`
  if (!checkPassword(password.value)) response.value = blue(P.LDPU_GAME_DISCONNECT_INVALID_PASSWORD)
  else if (!a.online) response.value = blue(GAME_ERROR_DISCONNECT)
  else {
    a.online = false
    response.value = blue(`${GAME_SUCCESS_DISCONNECT} ${a.login}`)
  }
}
</script>

<template>
  <h1 style="margin-top: 20px;">Desconectar conta</h1>
  <br />

  <div class="legend" style="margin-bottom: 25px;">
    <h3 class="legend-title"><span style="font-size: 13px;">Digite os dados abaixo:</span></h3>
    <div style="margin-bottom: 8px; margin-top: 10px;">
      <p><strong>Essa opção irá desconectar sua conta do jogo.</strong></p>
      <form action="" method="post" @submit.prevent="disconnect">
        <p>
          <label>Minha senha:</label>
          <input v-model="password" name="password" type="password" maxlength="10" />
        </p>
        <p>
          <input type="submit" value="Desconectar" class="button" />
        </p>
      </form>

      <div id="classe_change" style="margin-top: 16px; margin-bottom: 16px; padding-left: 5px;" v-html="response"></div>
    </div>
  </div>
</template>
