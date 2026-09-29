<script setup lang="ts">
// readNotice.tpl.php + ldNotice: sem ?id lista todas (/noticias); com ?id abre uma (/noticia?id=)
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import InnerLayout from '@/layouts/InnerLayout.vue'
import NoticeComments from '@/components/notices/NoticeComments.vue'
import { notices } from '@/data/notices'
import { useNoticesStore } from '@/stores/notices'
import { phpDate } from '@/lib/date'
import * as N from '@/lang/ldnotice'

const route = useRoute()
const store = useNoticesStore()

const rawId = computed(() => route.query.id)
const hasId = computed(() => rawId.value !== undefined)
const isNumeric = computed(() => /^\s*-?\d+(\.\d+)?\s*$/.test(String(rawId.value ?? '')))
const notice = computed(() => (hasId.value && isNumeric.value ? store.find(Number(rawId.value)) : undefined))
// o ajax de comentários recebia (int)$_GET['id'] — sem id, 0 (notícia inválida)
const commentsId = computed(() => (hasId.value ? Math.trunc(Number(rawId.value)) || 0 : 0))
const sorted = computed(() => [...notices].sort((a, b) => b.date.getTime() - a.date.getTime()))
</script>

<template>
  <InnerLayout location="NOTÍCIAS">
    <div id="etc">
      <div class="legend" style="margin-bottom: 25px;">
        <h3 class="legend-title"><span style="font-size: 13px;">Notícia:</span></h3>
        <div style="margin-bottom: 8px; margin-top: 10px;">
          <template v-if="!hasId">
            <template v-if="sorted.length === 0">{{ N.NOTICE_NOT_EXISTS_NOTICES }}</template>
            <ul v-else>
              <li v-for="n in sorted" :key="n.id"><RouterLink :to="{ path: '/noticia', query: { id: n.id } }">{{ n.subject }}</RouterLink> - {{ phpDate('[d/m/Y g:i a]', n.date) }}</li>
            </ul>
          </template>
          <template v-else-if="!isNumeric">{{ N.NOTICE_ERROR_VARIABLE }}</template>
          <template v-else-if="!notice">{{ N.NOTICE_NOT_EXISTS }}</template>
          <template v-else>
            <h1>{{ notice.subject }}</h1><br /><template v-for="(line, l) in notice.content.split('\n')" :key="l"><br v-if="l > 0" />{{ line }}</template><br /><br /><strong><em>{{ N.NOTICE_ADD_DATE }}</em></strong> {{ phpDate('d/m/Y g:i a', notice.date) }}
          </template>
        </div>
      </div>
      <div class="legend" style="margin-bottom: 25px;">
        <h3 class="legend-title"><span style="font-size: 13px;">Comentários:</span></h3>
        <div style="margin-bottom: 8px; margin-top: 10px;">
          <NoticeComments :notice-id="commentsId" />
        </div>
      </div>
    </div>
  </InnerLayout>
</template>
