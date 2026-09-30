<script setup lang="ts">
// ldRankings::gerateRankingGens (type 8 Duprian / 9 Vanert)
import { computed } from 'vue'
import { useDbStore } from '@/stores/db'
import { gensRanking } from '@/lib/rankings'
import * as L from '@/lang/ldrankings'

// family: pasta das imagens (templates/sunonline/images/gens/<família>); no banco, 1 = Duprian e 2 = Vanert
const props = defineProps<{ family: 'duprian' | 'vanert'; top: number }>()
const store = useDbStore()
const rows = computed(() => gensRanking(store.db, props.family === 'vanert' ? 2 : 1, props.top))
</script>

<template>
  <div class="quadros">
    <table border="0" width="100%">
      <tbody>
        <tr>
          <td bgcolor="#E4E1CB" align="center"><strong>{{ L.RANKING_PLACE }}</strong></td>
          <td bgcolor="#E4E1CB" align="center"><strong>{{ L.RANKING_CHARACTER_SHORT }}</strong></td>
          <td bgcolor="#E4E1CB" align="center"><strong>{{ L.RANKING_GENS_CONTRIBUITION }}</strong></td>
          <td bgcolor="#E4E1CB" align="center"><strong>{{ L.RANKING_GENS_FAMILY }}</strong></td>
          <td bgcolor="#E4E1CB" align="center"><strong>{{ L.RANKING_GENS_PATENT_LOGO }}</strong></td>
          <td bgcolor="#E4E1CB" align="center"><strong>{{ L.RANKING_GENS_PATENT_NAME }}</strong></td>
        </tr>
        <tr v-for="(r, i) in rows" :key="r.name">
          <td bgcolor="#F1F0E4" align="center"><strong>{{ i + 1 }}</strong></td>
          <td align="center"><RouterLink :to="{ path: '/rankings-personagem', query: { name: r.name } }">{{ r.name }}</RouterLink></td>
          <td align="center">{{ r.contribution }}</td>
          <td align="center"><img :src="`/images/gens/${family}/14.jpg`" /></td>
          <td align="center"><img :src="`/images/gens/${family}/${r.grad}.jpg`" /></td>
          <td align="center">{{ r.gradName }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
