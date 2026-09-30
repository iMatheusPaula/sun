<script setup lang="ts">
// onlines.tpl.php + ldOnlines: MEMB_STAT (ConnectStat = 1) JOIN AccountCharacter (GameIDC) JOIN Character, na ordem
// da tabela; conta sem personagem atual fica de fora. ?room=<sala> filtra pelo servidor (vazio ou "all" = todos).
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import InnerLayout from '@/layouts/InnerLayout.vue'
import { useDbStore } from '@/stores/db'
import { VIP_NAMES } from '@/config/settings'
import * as L from '@/lang/ldonlines'

const route = useRoute()
const store = useDbStore()
const same = (a: string, b: string) => a.toLowerCase() === b.toLowerCase()
const rows = computed(() => {
  const room = String(route.query.room ?? '')
  const all = room === '' || room === '0' || room === 'all' // empty($_GET['room'])
  return store.db.accounts
    .filter((a) => a.online && (all || same(a.server, room)))
    .flatMap((a) => {
      const c = a.lastCharacter ? store.db.characters.find((ch) => same(ch.name, a.lastCharacter!)) : undefined
      return c ? [{ character: a.lastCharacter!, connectedAt: a.connectTM, resets: c.resets, vip: a.vip, server: a.server }] : []
    })
})
</script>

<template>
  <InnerLayout location="SERVIDORES" subtitle="Players online">
    <div id="etc">
      <table border="0" width="100%">
        <tbody>
          <tr>
            <td align="center" bgcolor="#E2DEC5"><strong>{{ L.ONLINES_CHARACTER }}</strong></td>
            <td align="center" bgcolor="#E2DEC5"><strong>{{ L.ONLINES_CONNECT_IN }}</strong></td>
            <td align="center" bgcolor="#E2DEC5"><strong>{{ L.ONLINES_RESETS }}</strong></td>
            <td align="center" bgcolor="#E2DEC5"><strong>{{ L.ONLINES_ACCOUNT_TYPE }}</strong></td>
            <td align="center" bgcolor="#E2DEC5"><strong>{{ L.ONLINES_SERVER }}</strong></td>
          </tr>
          <tr v-for="o in rows" :key="o.character">
            <td align="center" bgcolor="#EDEBDC">{{ o.character }}</td>
            <td align="center" bgcolor="#EDEBDC">{{ o.connectedAt }}</td>
            <td align="center" bgcolor="#EDEBDC">{{ o.resets }}</td>
            <td align="center" bgcolor="#EDEBDC">{{ VIP_NAMES[o.vip] }}</td>
            <td align="center" bgcolor="#EDEBDC">{{ o.server }}</td>
          </tr>
          <tr><td colspan="5"><em><strong>{{ L.ONLINES_TOTAL_ONLINE }} {{ rows.length }} </strong></em></td></tr>
        </tbody>
      </table>
    </div>
  </InnerLayout>
</template>
