import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { poll as samplePoll } from '@/data/site'
import { POLL_INVALID_OPTION, POLL_LOGIN_TO_VOTE, POLL_VOTE_SUCESS } from '@/lang/ldajax'
import { useSessionStore } from './session'

// settings.php: $POLL['LOGIN'] = false; $POLL['HANG_WITH']['type'] = 0 (votos sem trava)
const POLL_LOGIN = false

export const usePollStore = defineStore('poll', () => {
  const question = samplePoll.question
  const answers = ref(samplePoll.answers.map((a) => ({ ...a })))
  const totalVotes = computed(() => answers.value.reduce((sum, a) => sum + a.votes, 0))

  // ldAjax::loadPoll action=vote
  function vote(answerId: number): string {
    const session = useSessionStore()
    if (POLL_LOGIN && !session.isLogged) return POLL_LOGIN_TO_VOTE
    const answer = answers.value.find((a) => a.id === answerId)
    if (!answer) return POLL_INVALID_OPTION
    answer.votes++
    return POLL_VOTE_SUCESS
  }

  // ldAjax::loadPoll action=result: (int)((votos / total) * 100)
  function percent(votes: number): number {
    return totalVotes.value === 0 ? 0 : Math.trunc((votes / totalVotes.value) * 100)
  }

  return { question, answers, totalVotes, vote, percent }
})
