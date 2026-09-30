<script setup lang="ts">
// paneladmin[GAME_DISCONNECT|GAME_MSG_SPECIFIC|GAME_MSG_ALL].tpl.php + ldPanelAdmin::optionLoadGameDisconnect /
// GameMsgSpecific / GameMsgAll: as contas online (MEMB_STAT + AccountCharacter) e os pedidos ao JoinServer
// (ldGame), respondidos com alert(). Sem JoinServer, desconectar tira a conta do jogo e as mensagens são dadas
// como enviadas. O cabeçalho "Conectou as" da tela de desconectar usa uma constante que não existe no .lang.php
// (o PHP 5 mostrava o próprio nome).
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useDbStore } from '@/stores/db'
import { isEmpty } from '@/lib/paneladmin'
import { GAME_ERROR_DISCONNECT, GAME_INCORRENCT_LOGIN, GAME_SUCCESS_DISCONNECT } from '@/lang/ldgame'
import * as PA from '@/lang/ldpaneladmin'

const props = defineProps<{ tool: 'disconnect' | 'specific' | 'all' }>()
const route = useRoute()
const store = useDbStore()
const alertText = (m: string) => m.replaceAll('\\n', '\n') // o \n do .lang.php vira quebra no alert()
const online = computed(() => store.db.accounts.filter((a) => a.online))

// ldGame::disconnectPlayer (?action=disconnect&memb___id=X): o alert() vinha antes da lista
let pendingAlert = ''
if (props.tool === 'disconnect' && route.query.action === 'disconnect') {
  const login = String(route.query.memb___id ?? '')
  const a = store.db.accounts.find((x) => x.login.toLowerCase() === login.toLowerCase())
  if (login === '' || login.length > 10) pendingAlert = GAME_INCORRENCT_LOGIN
  else if (!a?.online) pendingAlert = GAME_ERROR_DISCONNECT
  else {
    a.online = false
    pendingAlert = `${GAME_SUCCESS_DISCONNECT} ${login}`
  }
}
onMounted(() => {
  if (pendingAlert) window.alert(alertText(pendingAlert))
})

const disconnectPath = (login: string) => ({ path: '/paneladmin-game-disconnect', query: { action: 'disconnect', memb___id: login } })

// action=sendMsg
const message = ref(PA.LDPA_GAME_MSG_SPECIFIC_TEXT_MESSAGE)
const usernames = ref<string[]>([])
function send() {
  let text: string
  if (props.tool === 'specific' && usernames.value.length === 0) text = PA.LDPA_GAME_MSG_SPECIFIC_SELECT_ANY_USER
  else if (isEmpty(message.value) || message.value.length > 60) text = PA.LDPA_GAME_MSG_SPECIFIC_INVALID_SIZE_TEXT
  else text = props.tool === 'specific' ? PA.LDPA_GAME_MSG_SPECIFIC_TEXT_SEND_SUCCESS : PA.LDPA_GAME_MSG_ALL_TEXT_SEND_SUCCESS
  // o POST recarregava a página com o formulário como veio
  message.value = PA.LDPA_GAME_MSG_SPECIFIC_TEXT_MESSAGE
  usernames.value = []
  window.alert(text)
}

const TITLES = { disconnect: 'Desconectar contas onlines', specific: 'Enviar mensagem para determinados logins online', all: 'Enviar mensagem para todos online' }
const head = (text: string) => text
</script>

<template>
  <h1 style="margin-top: 20px;">{{ TITLES[tool] }}</h1>

  <div class="legend" style="margin-top: 25px; padding: 10px;">
    <h3 class="legend-title"><span style="font-size: 13px;">Preencha os dados abaixo:</span></h3>
    <div class="quadrosOut">
      <table v-if="tool === 'disconnect'" border="0" width="100%">
        <tbody>
          <tr>
            <td align="center" bgcolor="#E2DEC5"><strong>{{ PA.LDPA_GAME_DISCONNECT_TEXT_LOGIN }}</strong></td>
            <td align="center" bgcolor="#E2DEC5"><strong>{{ PA.LDPA_GAME_DISCONNECT_TEXT_CHARACTER }}</strong></td>
            <td align="center" bgcolor="#E2DEC5"><strong>{{ head('LDPA_GAME_DISCONNECT_TEXT_CONNECT_TM') }}</strong></td>
            <td align="center" bgcolor="#E2DEC5"><strong>{{ PA.LDPA_GAME_DISCONNECT_TEXT_SERVER }}</strong></td>
            <td align="center" bgcolor="#E2DEC5"><strong>{{ PA.LDPA_GAME_DISCONNECT_TEXT_ACTION }}</strong></td>
          </tr>
          <tr v-for="(a, i) in online" :id="`rowDc${i + 1}`" :key="a.login">
            <td align="center" bgcolor="#EDEBDC">{{ a.login }}</td>
            <td align="center" bgcolor="#EDEBDC">{{ a.lastCharacter }}</td>
            <td align="center" bgcolor="#EDEBDC">{{ a.connectTM }}</td>
            <td align="center" bgcolor="#EDEBDC">{{ a.server }}</td>
            <td align="center" bgcolor="#EDEBDC"><RouterLink :to="disconnectPath(a.login)">{{ PA.LDPA_GAME_DISCONNECT_TEXT_DISCONNECT }}</RouterLink></td>
          </tr>
          <tr><td colspan="5"><em><strong>{{ PA.LDPA_GAME_DISCONNECT_TEXT_TOTAL_ONLINES }}: {{ online.length }} </strong></em></td></tr>
        </tbody>
      </table>

      <form v-else-if="tool === 'specific'" action="" method="post" @submit.prevent="send">
        <table border="0" width="100%">
          <tbody>
            <tr>
              <td align="center" bgcolor="#E2DEC5"><strong>{{ PA.LDPA_GAME_MSG_SPECIFIC_TEXT_LOGIN }}</strong></td>
              <td align="center" bgcolor="#E2DEC5"><strong>{{ PA.LDPA_GAME_MSG_SPECIFIC_TEXT_CHARACTER }}</strong></td>
              <td align="center" bgcolor="#E2DEC5"><strong>{{ PA.LDPA_GAME_MSG_SPECIFIC_TEXT_CONNECT_TM }}</strong></td>
              <td align="center" bgcolor="#E2DEC5"><strong>{{ PA.LDPA_GAME_MSG_SPECIFIC_TEXT_SERVER }}</strong></td>
              <td align="center" bgcolor="#E2DEC5"><strong>{{ PA.LDPA_GAME_MSG_SPECIFIC_TEXT_SEND }}</strong></td>
            </tr>
            <tr v-for="(a, i) in online" :id="`rowDc${i + 1}`" :key="a.login">
              <td align="center" bgcolor="#EDEBDC">{{ a.login }}</td>
              <td align="center" bgcolor="#EDEBDC">{{ a.lastCharacter }}</td>
              <td align="center" bgcolor="#EDEBDC">{{ a.connectTM }}</td>
              <td align="center" bgcolor="#EDEBDC">{{ a.server }}</td>
              <td align="center" bgcolor="#EDEBDC"><input v-model="usernames" type="checkbox" name="usernames[]" :value="a.login" /></td>
            </tr>
            <tr><td colspan="5"><em><strong>{{ PA.LDPA_GAME_MSG_SPECIFIC_TEXT_TOTAL_ONLINES }}: {{ online.length }} </strong></em></td></tr>
            <tr><td colspan="5"><em><strong>{{ PA.LDPA_GAME_MSG_SPECIFIC_TEXT_MESSAGE }}: </strong></em><input v-model="message" type="text" name="message" maxlength="60" /><input type="submit" :value="PA.LDPA_GAME_MSG_SPECIFIC_TEXT_SUBMIT" class="button" /></td></tr>
          </tbody>
        </table>
      </form>

      <form v-else action="" method="post" @submit.prevent="send">
        <table border="0" width="100%">
          <tbody>
            <tr><td>
              <em><strong>{{ PA.LDPA_GAME_MSG_SPECIFIC_TEXT_MESSAGE }}: </strong></em>
              <input v-model="message" type="text" name="message" maxlength="60" />
              <input type="submit" :value="PA.LDPA_GAME_MSG_SPECIFIC_TEXT_SUBMIT" class="button" />
            </td></tr>
          </tbody>
        </table>
      </form>
    </div>
  </div>
</template>
