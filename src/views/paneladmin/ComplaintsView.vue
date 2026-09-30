<script setup lang="ts">
// panelgamemaster/paneladmin[COMPLAINTS].tpl.php + ldPanelAdmin::optionLoadManagerComplaints: todas as denúncias
// (mais nova primeiro); o botão abria &Write=true&id=N, que apaga a denúncia e mostra a lista de novo.
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useDbStore } from '@/stores/db'
import { adminPath, openUpload, uploadUrl, useAdminPage } from '@/lib/paneladmin'
import { phpDate } from '@/lib/date'
import * as PA from '@/lang/ldpaneladmin'

const route = useRoute()
const router = useRouter()
const store = useDbStore()
const page = useAdminPage()

let deleted = false
if (route.query.Write === 'true') {
  const id = parseInt(String(route.query.id), 10) || 0
  store.db.complaints = store.db.complaints.filter((c) => c.id !== id)
  deleted = true
}
const list = computed(() => [...store.db.complaints].sort((a, b) => b.id - a.id))
const lines = (text: string) => text.split('\n')
const remove = (id: number) => router.push({ path: adminPath(page.value, 'COMPLAINTS'), query: { Write: 'true', id } })
</script>

<template>
  <h1 style="margin-top: 20px;">Denuncias</h1>
  <br /><br />
  <div id="logs_" class="quadrosOut">
    <div v-if="deleted" class="qdestaques2">{{ PA.LDPA_MCOMPLAINTS_TEXT_DELETE_SUCCESS }}</div>
    <div v-for="c in list" :key="c.id" class="quadros">
      <em>{{ PA.LDPA_MCOMPLAINTS_TEXT_ID }}</em>: {{ c.id }} <br />
      <em>{{ PA.LDPA_MCOMPLAINTS_TEXT_LOGIN }}</em>: <strong>{{ c.account }}</strong> <br />
      <em>{{ PA.LDPA_MCOMPLAINTS_TEXT_IMAGE }}</em>: <a :href="uploadUrl('complaints', c.image)" target="_blank" @click="openUpload($event, uploadUrl('complaints', c.image))">[Ver Foto]</a> <br />
      <em>{{ PA.LDPA_MCOMPLAINTS_TEXT_DESCRIPTION }}</em>: <strong><template v-for="(l, i) in lines(c.description)" :key="i"><br v-if="i" />{{ l }}</template></strong> <br />
      <em>{{ PA.LDPA_MCOMPLAINTS_TEXT_IP }}</em>: {{ c.ip }} <br />
      <em>{{ PA.LDPA_MCOMPLAINTS_TEXT_DATE }}</em>: {{ phpDate('d/m/Y g:i a', new Date(c.date)) }} <br />
      <div style="text-align:right;"><input type="button" :value="PA.LDPA_MCOMPLAINTS_TEXT_DELETE_NOTICE" class="button" @click="remove(c.id)" /></div>
    </div>
  </div>
</template>
