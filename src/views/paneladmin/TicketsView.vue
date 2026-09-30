<script setup lang="ts">
// panelgamemaster/paneladmin[TICKETS_OPERATION|TICKETS_COMPLETING].tpl.php + ldPanelAdmin::optionLoadManagerTickets:
// sem ?Ticket, a lista dos pedidos abertos (0) ou encerrados (1), com borda vermelha quando a última resposta não é
// do Suporte; com ?Ticket=N, o pedido e o histórico. &SubWrite=true&newStatusTicket=N fecha/reabre. O formulário de
// resposta aparece sempre (o original testava a variável do laço das respostas), mas a resposta só grava com o pedido aberto.
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useDbStore } from '@/stores/db'
import { adminPath, useAdminPage } from '@/lib/paneladmin'
import { phpDate } from '@/lib/date'
import * as PA from '@/lang/ldpaneladmin'

const props = defineProps<{ type: 0 | 1 }>()
const route = useRoute()
const router = useRouter()
const store = useDbStore()
const page = useAdminPage()
const option = props.type === 0 ? 'TICKETS_OPERATION' : 'TICKETS_COMPLETING'

const ticketId = route.query.Ticket === undefined ? null : parseInt(String(route.query.Ticket), 10) || 0
if (ticketId !== null && route.query.SubWrite === 'true') {
  const t = store.db.tickets.find((x) => x.id === ticketId)
  if (t) t.status = (parseInt(String(route.query.newStatusTicket), 10) || 0) as 0 | 1
}

const list = computed(() => store.db.tickets.filter((t) => t.status === props.type).sort((a, b) => b.id - a.id))
const answersOf = (id: number) => store.db.ticketAnswers.filter((a) => a.ticketId === id).sort((a, b) => b.date - a.date)
// última resposta que não é do Suporte: borda vermelha
const pending = (id: number) => {
  const last = answersOf(id)[0]
  return !!last && last.answerBy !== 'Suporte'
}
const ticket = computed(() => store.db.tickets.find((t) => t.id === ticketId))
const answer = ref('')

const lines = (text: string) => text.split('\n')
const date = (ms: number) => phpDate('d/m/Y g:i a', new Date(ms))
const viewPath = (id: number) => ({ path: adminPath(page.value, option), query: { Ticket: id } })

function reply() {
  if (answer.value !== '' && ticket.value?.status === 0) {
    store.db.ticketAnswers.push({ ticketId: ticketId!, description: answer.value, answerBy: 'Suporte', date: Date.now() })
  }
  answer.value = ''
}

const setStatus = (status: 0 | 1) =>
  router.push({ path: adminPath(page.value, option), query: { Ticket: ticketId!, SubWrite: 'true', newStatusTicket: status } })
</script>

<template>
  <h1 style="margin-top: 20px;">{{ type === 0 ? 'Tickets Abertos' : 'Tickets encerrados' }}</h1>
  <br /><br />
  <div id="logs_" class="quadrosOut">
    <template v-if="ticketId === null">
      <div v-for="t in list" :key="t.id" class="quadros" :style="pending(t.id) ? 'border-color: red;' : undefined"><em>{{ PA.LDPA_MTICKETS_TEXT_ID }}</em>: <strong>{{ t.id }}</strong><br /><em>{{ PA.LDPA_MTICKETS_TEXT_SUBJECT }}</em>: <strong>{{ t.subject }}</strong><br /><em>{{ PA.LDPA_MTICKETS_TEXT_DATE }}</em>: <strong>{{ date(t.date) }}</strong><br /><em>{{ PA.LDPA_MTICKETS_TEXT_STATUS }}</em>: <strong>{{ t.status === 0 ? PA.LDPA_MTICKETS_TEXT_STATUS_OPEN : PA.LDPA_MTICKETS_TEXT_STATUS_CLOSE }}</strong><br /><RouterLink :to="viewPath(t.id)">{{ PA.LDPA_MTICKETS_TEXT_VIEW_TICKET }}</RouterLink></div>
    </template>
    <template v-else-if="!ticket">{{ PA.LDPA_MTICKETS_TEXT_NOT_FOUND }}</template>
    <template v-else>
      <em>{{ PA.LDPA_MTICKETS_TEXT_ID }}</em>: <strong>{{ route.query.Ticket }}</strong><br />
      <em>{{ PA.LDPA_MTICKETS_TEXT_LOGIN }}</em>: <strong>{{ ticket.account }}</strong><br />
      <em>{{ PA.LDPA_MTICKETS_TEXT_CHARACTER }}</em>: <strong>{{ ticket.character }}</strong><br />
      <em>{{ PA.LDPA_MTICKETS_TEXT_SECTOR }}</em>: <strong>{{ ticket.sector }}</strong><br />
      <em>{{ PA.LDPA_MTICKETS_TEXT_SUBJECT }}</em>: <strong>{{ ticket.subject }}</strong><br />
      <em>{{ PA.LDPA_MTICKETS_TEXT_DESCRIPTION }}</em>: <strong><template v-for="(l, i) in lines(ticket.description)" :key="i"><br v-if="i" />{{ l }}</template></strong><br />
      <em>{{ PA.LDPA_MTICKETS_TEXT_STATUS }}</em>: <strong>{{ ticket.status === 0 ? PA.LDPA_MTICKETS_TEXT_STATUS_OPEN : PA.LDPA_MTICKETS_TEXT_STATUS_CLOSE }}</strong><h1>{{ PA.LDPA_MTICKETS_TEXT_HISTORY }}</h1>
      <div v-if="answersOf(ticket.id).length === 0" class="quadros">{{ PA.LDPA_MTICKETS_TEXT_NOT_HAVE_HISTORY }}</div>
      <div v-for="(a, i) in answersOf(ticket.id)" :key="i" class="quadros"><em>{{ PA.LDPA_MTICKETS_TEXT_H_REPONSE_BY }}</em>: <strong>{{ a.answerBy }}</strong><br /><em>{{ PA.LDPA_MTICKETS_TEXT_DATE }}</em>: <strong>{{ date(a.date) }}</strong><br /><em>{{ PA.LDPA_MTICKETS_TEXT_DESCRIPTION }}</em>: <strong><template v-for="(l, j) in lines(a.description)" :key="j"><br v-if="j" />{{ l }}</template></strong><br /></div>
      <form action="" method="post" @submit.prevent="reply"><em>{{ PA.LDPA_MTICKETS_TEXT_SEND_REPONSE }}:</em><textarea v-model="answer" name="answer"></textarea><input type="submit" class="button" :value="PA.LDPA_MTICKETS_TEXT_SEND_SUBMIT" /><input v-if="ticket.status === 0" type="button" class="button" :value="PA.LDPA_MTICKETS_TEXT_SEND_CLOSE" @click="setStatus(1)" /><input v-else type="button" class="button" :value="PA.LDPA_MTICKETS_TEXT_SEND_OPEN" @click="setStatus(0)" /></form>
    </template>
  </div>
</template>
