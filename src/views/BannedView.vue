<script setup lang="ts">
// banned.tpl.php + ldBanned (type=characters / accounts): SELECT * FROM webBanneds WHERE type = X ORDER BY name DESC
// — os vencidos também aparecem, até o Sincronizar apagá-los.
import { computed } from 'vue'
import InnerLayout from '@/layouts/InnerLayout.vue'
import { useDbStore } from '@/stores/db'
import { phpDate } from '@/lib/date'
import { BANNED_ACCOUNTS, BANNED_CHARACTERS, BANNED_NO_USERS } from '@/lang/ldbanned'

const props = defineProps<{ type: 'characters' | 'accounts' }>()
const store = useDbStore()
const title = computed(() => (props.type === 'accounts' ? BANNED_ACCOUNTS : BANNED_CHARACTERS))
const rows = computed(() => {
  const type = props.type === 'accounts' ? 1 : 2
  const key = (name: string) => name.toLowerCase()
  return store.db.bans.filter((b) => b.type === type).sort((a, b) => (key(a.name) < key(b.name) ? 1 : key(a.name) > key(b.name) ? -1 : 0))
})
</script>

<template>
  <InnerLayout :location="title" :subtitle="title" location-style="text-transform: uppercase;">
    <div id="etc">
      <table border="0" width="100%">
        <tbody>
          <tr>
            <td align="center" bgcolor="#E2DEC5"><strong>Nome</strong></td>
            <td align="center" bgcolor="#E2DEC5"><strong>Expira</strong></td>
            <td align="center" bgcolor="#E2DEC5"><strong>Banidor por</strong></td>
            <td align="center" bgcolor="#E2DEC5"><strong>Motivo</strong></td>
          </tr>
          <tr v-if="rows.length === 0"><td align="center" bgcolor="#EDEBDC" colspan="4">{{ BANNED_NO_USERS }}</td></tr>
          <tr v-for="(b, i) in rows" :key="i">
            <td align="center" bgcolor="#EDEBDC">{{ b.name }}</td>
            <td align="center" bgcolor="#EDEBDC">{{ phpDate('d/m/Y G:i', new Date(b.end)) }}</td>
            <td align="center" bgcolor="#EDEBDC">{{ b.by }}</td>
            <td align="center" bgcolor="#EDEBDC">{{ b.description }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </InnerLayout>
</template>
