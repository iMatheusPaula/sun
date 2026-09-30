import { defineStore } from 'pinia'
import { computed } from 'vue'
import { POLL_DISABLE, POLL_INVALID_OPTION, POLL_LOGIN_TO_VOTE, POLL_VOTE_SUCESS } from '@/lang/ldajax'
import { useDbStore } from './db'
import { useSessionStore } from './session'

// settings.php: $POLL['LOGIN'] = false; $POLL['HANG_WITH']['type'] = 0 (votos sem trava)
const POLL_LOGIN = false

// Caixa ENQUETE (ldGeneral::loadPoll): a enquete ativa mais nova (webPollQuestions), com as respostas e os
// votos do ajax require=poll (ldAjax::loadPoll) — no banco do front, que o painel do admin altera.
export const usePollStore = defineStore('poll', () => {
  const store = useDbStore()
  const active = computed(() => store.db.polls.filter((p) => p.active).sort((a, b) => b.id - a.id)[0])
  const question = computed(() => active.value?.question ?? '')
  const answers = computed(() => (active.value ? store.db.pollAnswers.filter((a) => a.idPoll === active.value!.id) : []))
  const totalVotes = computed(() => answers.value.reduce((sum, a) => sum + a.votes, 0))

  // action=vote
  function vote(answerId: number): string {
    const session = useSessionStore()
    if (POLL_LOGIN && !session.isLogged) return POLL_LOGIN_TO_VOTE
    if (!active.value) return POLL_DISABLE
    const answer = answers.value.find((a) => a.idAnswer === answerId)
    if (!answer) return POLL_INVALID_OPTION
    answer.votes++
    return POLL_VOTE_SUCESS
  }

  // action=result: (int)((votos / total) * 100)
  function percent(votes: number): number {
    return totalVotes.value === 0 ? 0 : Math.trunc((votes / totalVotes.value) * 100)
  }

  return { active, question, answers, totalVotes, vote, percent }
})
