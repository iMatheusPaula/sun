<script setup lang="ts">
// ldRankings::gerateRankingGuilds: ORDER BY G_Score desc
import { computed } from 'vue'
import { useDbStore } from '@/stores/db'
import { guildRanking, memberWithStatus } from '@/lib/rankings'
import * as L from '@/lang/ldrankings'

const props = defineProps<{ top: number }>()
const store = useDbStore()
const rows = computed(() => guildRanking(store.db, props.top))
</script>

<template>
  <div class="quadros">
    <table border="0" width="100%">
      <tbody>
        <tr>
          <td bgcolor="#E4E1CB" align="center"><strong>{{ L.RANKING_PLACE_X }}</strong></td>
          <td bgcolor="#E4E1CB" align="center"><strong>{{ L.RANKING_GUILD_NAME }}</strong></td>
          <td bgcolor="#E4E1CB" align="center"><strong>{{ L.RANKING_GUILD_GM }}</strong></td>
          <td bgcolor="#E4E1CB" align="center"><strong>{{ L.RANKING_GUILD_ASSISTANT }}</strong></td>
          <td bgcolor="#E4E1CB" align="center"><strong>{{ L.RANKING_GUILD_BM }}</strong></td>
          <td bgcolor="#E4E1CB" align="center"><strong>{{ L.RANKING_GUILD_SCORE_SHORT }}</strong></td>
        </tr>
        <tr v-for="(g, i) in rows" :key="g.name">
          <td bgcolor="#F1F0E4" align="center"><strong>{{ i + 1 }}</strong></td>
          <td bgcolor="#F1F0E4" align="center"><RouterLink :to="{ path: '/rankings-guild', query: { name: g.name } }">{{ g.name }}</RouterLink></td>
          <td bgcolor="#F1F0E4" align="center">{{ g.master }}</td>
          <td bgcolor="#F1F0E4" align="center">{{ memberWithStatus(store.db, g.name, 64) }}</td>
          <td bgcolor="#F1F0E4" align="center">{{ memberWithStatus(store.db, g.name, 32) }}</td>
          <td bgcolor="#F1F0E4" align="center">{{ g.score }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
