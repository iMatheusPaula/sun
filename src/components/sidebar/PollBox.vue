<script setup lang="ts">
// Caixa ENQUETE (ldGeneral) + respostas do ajax require=poll (ldAjax::loadPoll).
import { ref } from 'vue'
import BoxMenu from '@/components/ui/BoxMenu.vue'
import GaugeBar from '@/components/ui/GaugeBar.vue'
import { usePollStore } from '@/stores/poll'
import { POLL_INVALID_OPTION, POLL_NO_VOTES, POLL_TOTAL_VOTES } from '@/lang/ajax'

const poll = usePollStore()
const selected = ref<number | null>(null)
const output = ref<{ kind: 'message'; text: string } | { kind: 'results' } | null>(null)

function vote() {
  output.value = { kind: 'message', text: selected.value ? poll.vote(selected.value) : POLL_INVALID_OPTION }
}

function showResults() {
  output.value = { kind: 'results' }
}
</script>

<template>
  <BoxMenu title="ENQUETE">
    <ul class="commonArrow">
      <li><strong>{{ poll.question }}</strong></li>
      <li v-for="a in poll.answers" :key="a.id"><input v-model="selected" id="answer" name="answer" :value="a.id" type="radio" /> {{ a.answer }}</li>
      <li><input type="button" class="button" value="Votar" @click="vote" /> <input type="button" class="button" value="Resultado" @click="showResults" /></li>
      <div id="pollResult">
        <li v-if="output?.kind === 'message'"><strong>{{ output.text }}</strong></li>
        <template v-else-if="output?.kind === 'results'">
          <strong v-if="poll.totalVotes === 0">{{ POLL_NO_VOTES }}</strong>
          <template v-else>
            <li v-for="a in poll.answers" :key="a.id"><strong>{{ a.answer }}</strong> ({{ a.votes }})<br />
              <GaugeBar :fill-id="String(a.id)" :percent="poll.percent(a.votes)" />
            </li>
            <li>{{ POLL_TOTAL_VOTES }} {{ poll.totalVotes }}</li>
          </template>
        </template>
      </div>
    </ul>
  </BoxMenu>
</template>
