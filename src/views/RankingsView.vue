<script setup lang="ts">
// rankings.tpl.php + ldRankings. Cada tipo/período tem sua rota (RANKING_PAGES); ?top= e ?name= ficam na query.
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import InnerLayout from '@/layouts/InnerLayout.vue'
import RankingCharacters from '@/components/rankings/RankingCharacters.vue'
import RankingGuilds from '@/components/rankings/RankingGuilds.vue'
import RankingGens from '@/components/rankings/RankingGens.vue'
import CharacterProfile from '@/components/rankings/CharacterProfile.vue'
import GuildProfile from '@/components/rankings/GuildProfile.vue'
import { normalizeTop, rankingPath, TOPS, type Period, type RankingType } from '@/lib/rankings'
import { RANKING_GENS } from '@/config/settings'

const props = defineProps<{ type: RankingType; period?: Period }>()
const route = useRoute()
const router = useRouter()

const top = computed(() => normalizeTop(route.query.top))
const name = computed(() => String(route.query.name ?? ''))

// "Rankings em geral": checkType() / checkPeriod() do .tpl mantêm período e tipo coerentes
const form = ref({ period: 0, type: 1, top: 10 })
watch(() => form.value.period, (p) => { if (p > 0) form.value.type = 1 })
watch(() => form.value.type, (t) => { if (t > 1) form.value.period = 0 })

function load() {
  const { type, period, top: amount } = form.value
  router.push({ path: rankingPath(type, period), query: amount === 10 ? {} : { top: amount } })
}

// "Busca por nome exato": 6 = personagem, 7 = guild
const search = ref({ type: 6, name: '' })
function find() {
  router.push({ path: rankingPath(search.value.type), query: { name: search.value.name } })
}
</script>

<template>
  <InnerLayout location="RANKINGS" subtitle="Rankings">
    <div id="etc">
      <div class="legend" style="margin-bottom: 25px;">
        <h3 class="legend-title"><span style="font-size: 13px;">Rankings em geral</span></h3>
        <div style="margin-bottom: 8px; margin-top: 10px;">
          <form action="" method="get" @submit.prevent="load">
            <table border="0" width="100%">
              <tbody>
                <tr>
                  <td align="center"><strong>Período: </strong>
                    <select id="period" v-model.number="form.period" name="period">
                      <option value="0">Tempo real</option>
                      <option value="1">Semanal</option>
                      <option value="2">Mensal</option>
                    </select>
                  </td>
                  <td align="center"><strong>Tipo: </strong>
                    <select id="type" v-model.number="form.type" name="type">
                      <option value="1">Resets</option>
                      <option value="5">Master Resets</option>
                      <option value="2">Level</option>
                      <option value="4">Pk (Mortes)</option>
                      <option value="3">Guilds</option>
                      <template v-if="RANKING_GENS"><option value="8">Gens - Familia Duprian</option><option value="9">Gens - Familia Vanert</option></template>
                    </select>
                  </td>
                  <td align="center"><strong>Quantidade:</strong>
                    <select v-model.number="form.top" name="top">
                      <option v-for="t in TOPS" :key="t" :value="t">{{ t }}</option>
                    </select>
                  </td>
                  <td align="center">
                    <input type="submit" value="Carregar" class="button" />
                  </td>
                </tr>
              </tbody>
            </table>
          </form>
        </div>
      </div>
    </div>
    <div id="etc">
      <div class="legend" style="margin-bottom: 25px;">
        <h3 class="legend-title"><span style="font-size: 13px;">Busca por nome exato</span></h3>
        <div style="margin-bottom: 8px; margin-top: 10px;">
          <form action="" method="get" @submit.prevent="find">
            <table border="0" width="100%">
              <tbody>
                <tr>
                  <td align="center"><strong>Tipo: </strong>
                    <select id="searchType" v-model.number="search.type" name="type">
                      <option value="6">Personagem</option>
                      <option value="7">Guild</option>
                    </select>
                  </td>
                  <td align="center"><strong>Nome:</strong>
                    <input v-model="search.name" type="text" name="name" />
                  </td>
                  <td align="center">
                    <input type="submit" value="Buscar" class="button" />
                  </td>
                </tr>
              </tbody>
            </table>
          </form>
        </div>
      </div>
    </div>
    <div id="etc">
      <RankingCharacters v-if="type === 1 || type === 2 || type === 4 || type === 5" :type="type" :period="period ?? 0" :top="top" />
      <RankingGuilds v-else-if="type === 3" :top="top" />
      <CharacterProfile v-else-if="type === 6" :name="name" />
      <GuildProfile v-else-if="type === 7" :name="name" />
      <RankingGens v-else :family="type === 9 ? 'vanert' : 'duprian'" :top="top" />
    </div>
  </InnerLayout>
</template>
