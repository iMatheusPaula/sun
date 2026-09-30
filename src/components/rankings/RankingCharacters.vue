<script setup lang="ts">
// ldRankings::gerateRankingResets / Level / Pks / MasterResets: a mesma tabela, com a coluna do tipo destacada
import { computed } from 'vue'
import { useDbStore } from '@/stores/db'
import { accountVip, characterRanking, rankingValue, type CharacterRankingType, type Period } from '@/lib/rankings'
import { className, VIP_NAMES } from '@/config/settings'
import * as L from '@/lang/ldrankings'

const props = defineProps<{ type: CharacterRankingType; period: Period; top: number }>()
const store = useDbStore()
const rows = computed(() => characterRanking(store.db, props.type, props.period, props.top))

// #CDC89E na coluna ordenada: Level (tipo 2), Mortes (4), Resets/Master Resets (1 e 5)
const highlighted = computed(() => (props.type === 2 ? 3 : props.type === 4 ? 4 : 5))
const bg = (col: number) => (col === highlighted.value ? '#CDC89E' : '#E4E1CB')
</script>

<template>
  <div class="quadros">
    <table border="0" width="100%">
      <tbody>
        <tr>
          <td :bgcolor="bg(1)" align="center"><strong>{{ L.RANKING_PLACE }}</strong></td>
          <td :bgcolor="bg(2)" align="center"><strong>{{ L.RANKING_CHARACTER_SHORT }}</strong></td>
          <td :bgcolor="bg(3)" align="center"><strong>{{ L.RANKING_LEVEL }}</strong></td>
          <td :bgcolor="bg(4)" align="center"><strong>{{ L.RANKING_KILLS }}</strong></td>
          <td :bgcolor="bg(5)" align="center"><strong>{{ type === 5 ? L.RANKING_MASTERRESETS : L.RANKING_RESETS }}</strong></td>
          <td :bgcolor="bg(6)" align="center"><strong>{{ L.RANKING_CLASS }}</strong></td>
          <td :bgcolor="bg(7)" align="center"><strong>{{ L.RANKING_STATUS_ACCOUNT }}</strong></td>
        </tr>
        <tr v-for="(c, i) in rows" :key="c.name">
          <td bgcolor="#F1F0E4" align="center"><strong>{{ i + 1 }}</strong></td>
          <td align="center"><RouterLink :to="{ path: '/rankings-personagem', query: { name: c.name } }">{{ c.name }}</RouterLink></td>
          <td align="center">{{ c.level }}</td>
          <td align="center">{{ c.pkCountWeb < 0 ? L.RANKING_HERO : c.pkCountWeb }}</td>
          <td align="center">{{ rankingValue(c, type, period) }}</td>
          <td align="center">{{ className(c.class) }}</td>
          <td align="center">{{ VIP_NAMES[accountVip(store.db, c.account)] }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
