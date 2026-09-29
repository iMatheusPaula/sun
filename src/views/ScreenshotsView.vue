<script setup lang="ts">
// screenshots.tpl.php + módulo screenshots: viewAll (/screenshots) e viewImage/voteImage (/screenshot?id=)
import { computed, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import InnerLayout from '@/layouts/InnerLayout.vue'
import { useScreenshotsStore } from '@/stores/screenshots'
import { screenshotLarge, screenshotThumb } from '@/data/placeholders'
import { SCREENSHOTS } from '@/config/settings'
import { phpDate } from '@/lib/date'

const props = defineProps<{ mode: 'all' | 'view' }>()
const route = useRoute()
const store = useScreenshotsStore()

// viewAll: 5 por linha; quando o total é múltiplo de 5 sobra uma linha vazia, como no original
const rows = computed(() => {
  const all = store.ranked()
  const out: (typeof all)[] = [[]]
  all.forEach((s, i) => {
    out[out.length - 1]!.push(s)
    if ((i + 1) % 5 === 0) out.push([])
  })
  return out
})

const id = computed(() => Math.trunc(Number(route.query.id)) || 0)
const shot = computed(() => store.find(id.value))
const voteResult = ref<{ ok: boolean; message: string } | null>(null)
watch(id, () => (voteResult.value = null))

// imagem grande limitada a $SCREENSHOTS['MAX_WIDTH'] (cw × cy gravados no envio)
const bigWidth = SCREENSHOTS.MAX_WIDTH
const bigHeight = Math.round((SCREENSHOTS.MAX_WIDTH * 3) / 4)

function vote() {
  voteResult.value = store.vote(id.value)
}

const votes = (rate: number) => `Quantidade de voto${rate > 0 ? 's' : ''}:`
</script>

<template>
  <InnerLayout location="SCREENSHOT" subtitle="Screenshots">
    <div id="etc">
      <table style="width: 100%;">
        <tbody v-if="props.mode === 'all'">
          <tr v-if="rows[0]!.length === 0"><td>Screenshot não encontrada.</td></tr>
          <template v-else>
            <tr v-for="(row, r) in rows" :key="r">
              <td v-for="s in row" :key="s.id" align="center">
                <RouterLink :to="{ path: '/screenshot', query: { id: s.id } }">
                  <img :src="screenshotThumb" style="width: 90px; height: 68px;" /><br />
                  <strong>{{ votes(s.rate) }}</strong> {{ s.rate }}
                </RouterLink>
              </td>
            </tr>
          </template>
        </tbody>
        <tbody v-else>
          <tr v-if="voteResult"><td><div :class="voteResult.ok ? 'qdestaques2' : 'qdestaques'">{{ voteResult.message }}</div></td></tr>
          <tr v-if="!shot"><td>Screenshot não encontrada.</td></tr>
          <template v-else>
            <tr>
              <td align="center">
                <img :src="screenshotLarge" :style="{ width: `${bigWidth}px`, height: `${bigHeight}px` }" />
              </td>
            </tr>
            <tr>
              <td>
                <strong>Legenda:</strong> {{ shot.caption }}<br />
                <strong>Foto enviada por:</strong> {{ shot.author }}<br />
                <strong>Enviada em:</strong> {{ phpDate('d/m/y \\a\\s G:i:s', shot.sentAt) }}<br />
                <strong>{{ votes(shot.rate) }}</strong> {{ shot.rate }}<br />
                <form action="" method="post" @submit.prevent="vote">
                  <strong>Gostou da imagem:</strong>
                  <input type="submit" value="Votar" class="button" />
                </form>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>
  </InnerLayout>
</template>
