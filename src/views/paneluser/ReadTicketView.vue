<script setup lang="ts">
// paneluser[READ_TICKET].tpl.php + ldPanelUser::loadOptionsReadTicket: lista (sem ?Ticket) ou um pedido com o
// histórico (mais nova primeiro) e o campo de resposta enquanto estiver aberto
import { computed, ref } from 'vue'
import { useRoute } from 'vue-router'
import { usePanelAccount } from '@/lib/paneluser'
import { phpDate } from '@/lib/date'
import * as P from '@/lang/ldpaneluser'

const route = useRoute()
const { store, login } = usePanelAccount()
const ticketId = route.query.Ticket === undefined ? null : Math.trunc(Number(route.query.Ticket)) || 0
const list = computed(() => store.db.tickets.filter((t) => t.account === login.value).sort((a, b) => b.id - a.id))
const ticket = computed(() => store.db.tickets.find((t) => t.account === login.value && t.id === ticketId))
const answers = computed(() => store.db.ticketAnswers.filter((a) => a.ticketId === ticketId).sort((a, b) => b.date - a.date))
const answer = ref('')

const lines = (text: string) => text.split('\n')

function reply() {
  if (answer.value !== '' && ticket.value?.status === 0) {
    store.db.ticketAnswers.push({ ticketId: ticketId!, description: answer.value, answerBy: 'Player', date: Date.now() })
    answer.value = ''
  }
}
</script>

<template>
  <h1 style="margin-top: 20px;">Ver tickets</h1>
  <br />

  <div id="etc">
    <div class="quadrosOut">
      <div id="logs_">
        <template v-if="ticketId === null">
          <div v-for="t in list" :key="t.id" class="quadros"><em>{{ P.LDPU_READ_TICKET_TEXT_ID }}</em>: <strong>{{ t.id }}</strong><br /><em>{{ P.LDPU_READ_TICKET_TEXT_SUBJECT }}</em>: <strong>{{ t.subject }}</strong><br /><em>{{ P.LDPU_READ_TICKET_TEXT_DATE }}</em>: <strong>{{ phpDate('d/m/Y g:i a', new Date(t.date)) }}</strong><br /><em>{{ P.LDPU_READ_TICKET_TEXT_STATUS }}</em>: <strong>{{ t.status === 0 ? P.LDPU_READ_TICKET_TEXT_STATUS_OPEN : P.LDPU_READ_TICKET_TEXT_STATUS_CLOSE }}</strong><br /><RouterLink :to="{ path: '/paneluser-read-ticket', query: { Ticket: t.id } }">{{ P.LDPU_READ_TICKET_TEXT_VIEW_TICKET }}</RouterLink></div>
        </template>
        <template v-else-if="!ticket">{{ P.LDPU_READ_TICKET_TEXT_TICKET_NOT_FOUND }}</template>
        <template v-else>
          <em>{{ P.LDPU_READ_TICKET_TEXT_ID }}</em>: <strong>{{ ticketId }}</strong><br />
          <em>{{ P.LDPU_READ_TICKET_TEXT_SUBJECT }}</em>: <strong>{{ ticket.subject }}</strong><br />
          <em>{{ P.LDPU_READ_TICKET_TEXT_DESCRIPTION }}</em>: <strong><template v-for="(l, i) in lines(ticket.description)" :key="i"><br v-if="i" />{{ l }}</template></strong><br />
          <em>{{ P.LDPU_READ_TICKET_TEXT_STATUS }}</em>: <strong>{{ ticket.status === 0 ? P.LDPU_READ_TICKET_TEXT_STATUS_OPEN : P.LDPU_READ_TICKET_TEXT_STATUS_CLOSE }}</strong><h1>{{ P.LDPU_READ_TICKET_TEXT_HISTORY }}</h1>
          <div v-if="answers.length === 0" class="quadros">{{ P.LDPU_READ_TICKET_TEXT_NO_HISTORY }}</div>
          <div v-for="(a, i) in answers" :key="i" class="quadros"><em>{{ P.LDPU_READ_TICKET_TEXT_RESPONSE_BY }}</em>: <strong>{{ a.answerBy }}</strong><br /><em>{{ P.LDPU_READ_TICKET_TEXT_DATE }}</em>: <strong>{{ phpDate('d/m/Y g:i a', new Date(a.date)) }}</strong><br /><em>{{ P.LDPU_READ_TICKET_TEXT_DESCRIPTION }}</em>: <strong><template v-for="(l, j) in lines(a.description)" :key="j"><br v-if="j" />{{ l }}</template></strong><br /></div>
          <form v-if="ticket.status === 0" action="" method="post" @submit.prevent="reply"><em>{{ P.LDPU_READ_TICKET_TEXT_SEND_RESPONSE }}:</em><textarea v-model="answer" name="answer"></textarea><input type="submit" class="button" :value="P.LDPU_READ_TICKET_TEXT_SUBMIT" /></form>
        </template>
      </div>
    </div>
  </div>
</template>
