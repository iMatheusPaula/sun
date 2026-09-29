<script setup lang="ts">
// banned.tpl.php + ldBanned (type=characters / accounts). O original ordena por nome desc;
// aqui fica a ordem do kit até a fase de limpeza.
import { computed } from 'vue'
import InnerLayout from '@/layouts/InnerLayout.vue'
import { bannedAccounts, bannedCharacters } from '@/data/banned'
import { phpDate } from '@/lib/date'
import { BANNED_ACCOUNTS, BANNED_CHARACTERS, BANNED_NO_USERS } from '@/lang/ldbanned'

const props = defineProps<{ type: 'characters' | 'accounts' }>()
const title = computed(() => (props.type === 'accounts' ? BANNED_ACCOUNTS : BANNED_CHARACTERS))
const rows = computed(() => (props.type === 'accounts' ? bannedAccounts : bannedCharacters))
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
          <tr v-for="b in rows" :key="b.name">
            <td align="center" bgcolor="#EDEBDC">{{ b.name }}</td>
            <td align="center" bgcolor="#EDEBDC">{{ phpDate('d/m/Y G:i', b.until) }}</td>
            <td align="center" bgcolor="#EDEBDC">{{ b.by }}</td>
            <td align="center" bgcolor="#EDEBDC">{{ b.reason }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </InnerLayout>
</template>
