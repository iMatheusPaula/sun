<script setup lang="ts">
// paneladmin[GAME_CHAT_SERVER].tpl.php + ldPanelAdmin::optionLoadGameChatServer: as últimas mensagens do jogo
// (webChatServer, a mais nova primeiro). ?loadLasts=N muda quantas; ?action=clearLog apaga o log (alert()).
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useDbStore } from '@/stores/db'
import * as PA from '@/lang/ldpaneladmin'

const route = useRoute()
const router = useRouter()
const store = useDbStore()
const TYPES = [
  PA.LDPA_CHAT_MSG_TYPE_CHAT, PA.LDPA_CHAT_MSG_TYPE_PARTY, PA.LDPA_CHAT_MSG_TYPE_GUILD, PA.LDPA_CHAT_MSG_TYPE_GLOBAL,
  PA.LDPA_CHAT_MSG_TYPE_ANUNGUILD, PA.LDPA_CHAT_MSG_TYPE_WHISPER, PA.LDPA_CHAT_MSG_TYPE_ALlIANCE, PA.LDPA_CHAT_MSG_TYPE_GM, PA.LDPA_CHAT_MSG_TYPE_POST,
]

const cleared = route.query.action === 'clearLog'
if (cleared) store.db.chat = [] // TRUNCATE TABLE
onMounted(() => {
  if (cleared) window.alert(PA.LDPA_CHAT_SERVER_TEXT_TRUNCATE_LOG)
})

// SELECT TOP N … ORDER BY id DESC
const top = route.query.loadLasts === undefined ? 50 : parseInt(String(route.query.loadLasts), 10) || 0
const rows = computed(() => (top > 0 ? [...store.db.chat].sort((a, b) => b.id - a.id).slice(0, top) : []))
const loadLasts = ref(route.query.loadLasts === undefined ? '50' : String(route.query.loadLasts))

const go = (action?: 'clearLog') => router.push({ path: '/paneladmin-game-chat-server', query: { ...(action ? { action } : {}), loadLasts: loadLasts.value } })
</script>

<template>
  <h1 style="margin-top: 20px;">Ver Chat em tempo real</h1>

  <div class="legend" style="margin-top: 25px; padding: 10px;">
    <h3 class="legend-title"><span style="font-size: 13px;">Preencha os dados abaixo:</span></h3>
    <table border="0" width="100%">
      <tbody>
        <tr>
          <td colspan="2" bgcolor="#E2DEC5">
            <input type="button" value="Atualizar" @click="go()" />
            <input type="button" value="Apagar log" @click="go('clearLog')" />
            &nbsp;&nbsp;|&nbsp;&nbsp;
            Mostrar ultimos: <input id="loadLasts" v-model="loadLasts" type="text" />
            <input type="button" value="Carregar" @click="go()" />
          </td>
        </tr>
      </tbody>
    </table>
    <div class="quadrosOut">
      <table border="0" width="100%">
        <tbody>
          <tr>
            <td align="center" bgcolor="#E2DEC5"><strong>{{ PA.LDPA_CHAT_SERVER_TEXT_DATA }}</strong></td>
            <td align="center" bgcolor="#E2DEC5"><strong>{{ PA.LDPA_CHAT_SERVER_TEXT_TYPE }}</strong></td>
            <td align="center" bgcolor="#E2DEC5"><strong>{{ PA.LDPA_CHAT_SERVER_TEXT_NAME }}</strong></td>
            <td align="center" bgcolor="#E2DEC5"><strong>{{ PA.LDPA_CHAT_SERVER_TEXT_MESSAGE }}</strong></td>
          </tr>
          <tr v-for="c in rows" :key="c.id">
            <td align="center" bgcolor="#EDEBDC">{{ c.day }} {{ c.hour }}</td>
            <td align="center" bgcolor="#EDEBDC">{{ TYPES[c.type] ?? c.type }}</td>
            <td align="center" bgcolor="#EDEBDC">{{ c.name }}</td>
            <td bgcolor="#EDEBDC">{{ c.message }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
