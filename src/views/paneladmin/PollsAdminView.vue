<script setup lang="ts">
// paneladmin[ADD_POLL|REMOVE_POLL|MODIFY_POLL].tpl.php + ldPanelAdmin::optionLoadManagerPoll(0|1|2): as enquetes da
// caixa ENQUETE (a ativa mais nova é a que aparece no site). Remover e as ações de Alterar eram links
// (?action=remove&idPoll=N, ?action=modify&idPoll=N[&subAction=remove&idAnswer=N]); incluir resposta, um POST.
import { computed, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useDbStore } from '@/stores/db'
import { isEmpty, isNumeric } from '@/lib/paneladmin'
import { sprintf } from '@/lang/format'
import * as PA from '@/lang/ldpaneladmin'

const props = defineProps<{ mode: 'add' | 'remove' | 'modify' }>()
const route = useRoute()
const router = useRouter()
const store = useDbStore()
const db = store.db
const q = route.query
const result = ref('')

const polls = computed(() => [...store.db.polls].sort((a, b) => b.id - a.id))
const active = (on: boolean) => (on ? PA.LDPA_MPOLL_REMOVE_QUESTION_TEXT_ACTIVE_YES : PA.LDPA_MPOLL_REMOVE_QUESTION_TEXT_ACTIVE_NOT)
const nextAnswerId = () => Math.max(0, ...store.db.pollAnswers.map((a) => a.idAnswer)) + 1

// Adicionar (action=insert): nasce ativa, com as respostas preenchidas
const add = reactive({ question: '', answers: ['', '', '', '', ''] })
function insert() {
  const { question, answers } = add
  Object.assign(add, { question: '', answers: ['', '', '', '', ''] }) // o POST recarregava a página
  if (isEmpty(question)) return (result.value = `<div class="qdestaques">${PA.LDPA_MPOLL_INSERT_FILL_QUESTION}</div>`)
  const id = Math.max(0, ...store.db.polls.map((p) => p.id)) + 1
  store.db.polls.push({ id, question, active: true })
  for (const answer of answers) if (!isEmpty(answer)) store.db.pollAnswers.push({ idPoll: id, idAnswer: nextAnswerId(), answer, votes: 0 })
  result.value = `<div class="qdestaques2">${PA.LDPA_MPOLL_INSERT_QUESTION_SUCCESS}</div>`
}

// Remover (action=remove&idPoll=N)
const removed = props.mode === 'remove' && q.action === 'remove' && isNumeric(String(q.idPoll ?? ''))
if (removed) {
  const id = Math.trunc(Number(q.idPoll))
  db.polls = db.polls.filter((p) => p.id !== id)
  db.pollAnswers = db.pollAnswers.filter((a) => a.idPoll !== id)
}
function confirmRemove(id: number) {
  if (window.confirm(PA.LDPA_MPOLL_REMOVE_QUESTION_TEXT_SUBMIT_SURE)) router.push({ path: '/paneladmin-remove-poll', query: { action: 'remove', idPoll: id } })
}

// Alterar (action=modify&idPoll=N)
const editingId = props.mode === 'modify' && q.action === 'modify' && isNumeric(String(q.idPoll ?? '')) ? Math.trunc(Number(q.idPoll)) : null
if (editingId !== null && q.subAction === 'remove' && !isEmpty(String(q.idAnswer ?? ''))) {
  const idAnswer = Number(q.idAnswer)
  db.pollAnswers = db.pollAnswers.filter((a) => !(a.idPoll === editingId && a.idAnswer === idAnswer))
}
const editing = computed(() => store.db.polls.find((p) => p.id === editingId))
const answers = computed(() => store.db.pollAnswers.filter((a) => a.idPoll === editingId))
const newAnswer = ref('')
function insertAnswer() {
  if (!isEmpty(newAnswer.value)) store.db.pollAnswers.push({ idPoll: editingId!, idAnswer: nextAnswerId(), answer: newAnswer.value, votes: 0 })
  newAnswer.value = ''
}
function confirmRemoveAnswer(idAnswer: number) {
  if (window.confirm(PA.LDPA_MPOLL_MODIFY_TEXT_ANSWER_REMOVE)) {
    router.push({ path: '/paneladmin-modify-poll', query: { action: 'modify', subAction: 'remove', idPoll: editingId!, idAnswer } })
  }
}
const modifyPath = (id: number) => router.push({ path: '/paneladmin-modify-poll', query: { action: 'modify', idPoll: id } })
</script>

<template>
  <template v-if="mode === 'add'">
    <h1 style="margin-top: 20px;">Adicionar Enquete</h1>

    <div class="legend" style="margin-top: 25px; padding: 10px;">
      <h3 class="legend-title"><span style="font-size: 13px;">Preencha os dados abaixo:</span></h3>
      <form action="" method="post" class="quadros" @submit.prevent="insert">
        Pergunta da enquete: <br /><input v-model="add.question" name="question" type="text" maxlength="50" class="inputbox" /><br />
        <template v-for="(_, i) in add.answers" :key="i">Resposta 0{{ i + 1 }}: <br /><input v-model="add.answers[i]" name="answer[]" type="text" maxlength="50" class="inputbox" /><br /></template>
        <input type="submit" class="button" value="Cadastrar" />
      </form>
      <div class="quadrosOut" v-html="result"></div>
    </div>
  </template>

  <template v-else>
    <h1 style="margin-top: 20px;">{{ mode === 'remove' ? 'Remover Enquete' : 'Modificar Enquete' }}</h1>

    <div class="legend" style="margin-top: 25px; padding: 10px;">
      <h3 class="legend-title"><span style="font-size: 13px;">Listando enquetes do sistema:</span></h3>
      <div v-if="mode === 'remove'" class="quadrosOut">
        <div v-if="removed" class="qdestaques2">{{ sprintf(PA.LDPA_MPOLL_REMOVE_QUESTION_SUCCESS, Math.trunc(Number(q.idPoll))) }}</div>
        <div v-for="p in polls" :key="p.id" class="quadros">{{ PA.LDPA_MPOLL_REMOVE_QUESTION_TEXT_QUESTION }}: {{ p.question }}<br />{{ PA.LDPA_MPOLL_REMOVE_QUESTION_TEXT_ACTIVE }}: {{ active(p.active) }}<br /><input type="button" class="button" :value="PA.LDPA_MPOLL_REMOVE_QUESTION_TEXT_SUBMIT" @click="confirmRemove(p.id)" /></div>
      </div>
      <div v-else-if="editingId !== null" class="quadrosOut">
        <div class="quadros">{{ PA.LDPA_MPOLL_REMOVE_QUESTION_TEXT_QUESTION }}: {{ editing?.question }}<br />{{ PA.LDPA_MPOLL_REMOVE_QUESTION_TEXT_ACTIVE }}: {{ active(!!editing?.active) }}<br /><br /><form action="" method="post" class="quadros" @submit.prevent="insertAnswer">{{ PA.LDPA_MPOLL_MODIFY_TEXT_QUESTION_ADD }}: <br /><input v-model="newAnswer" name="answer" type="text" maxlength="50" /><input type="submit" class="button" :value="PA.LDPA_MPOLL_MODIFY_TEXT_QUESTION_SUBMIT" /></form><div class="quadros">{{ PA.LDPA_MPOLL_MODIFY_TEXT_ANSWERS }}:<div v-for="a in answers" :key="a.idAnswer" class="quadros">{{ PA.LDPA_MPOLL_MODIFY_TEXT_ANSWER }}: {{ a.answer }}<br />{{ PA.LDPA_MPOLL_MODIFY_TEXT_ANSWER_VOTES }}: {{ a.votes }}<br /><input type="button" class="button" :value="PA.LDPA_MPOLL_REMOVE_QUESTION_TEXT_SUBMIT" @click="confirmRemoveAnswer(a.idAnswer)" /></div></div></div>
      </div>
      <div v-else class="quadrosOut">
        <div v-for="p in polls" :key="p.id" class="quadros">{{ PA.LDPA_MPOLL_REMOVE_QUESTION_TEXT_QUESTION }}: {{ p.question }}<br />{{ PA.LDPA_MPOLL_REMOVE_QUESTION_TEXT_ACTIVE }}: {{ active(p.active) }}<br /><input type="button" class="button" :value="PA.LDPA_MPOLL_MODIFY_TEXT_ANSWER_ALTER_SUBMIT" @click="modifyPath(p.id)" /></div>
      </div>
    </div>
  </template>
</template>
