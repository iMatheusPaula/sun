<script setup lang="ts">
// Evento de perguntas ("Show do Milhão") do menuPanelUser.tpl.php: bloco escondido que o sorteio
// (ldPanelUser::questionsEventCheck) abre num popup do fancybox com a pergunta e 60 segundos para responder.
// Fechado o popup, o bloco some até a próxima página (o fancybox descartava o conteúdo).
import { onUnmounted, ref, watch } from 'vue'
import { useQuestionsEventStore } from '@/stores/questionsEvent'
import FancyBox from '@/components/ui/FancyBox.vue'
import * as P from '@/lang/ldpaneluser'

defineProps<{ name: string; title: string }>()
const qe = useQuestionsEventStore()

const answerFinal = ref(0)
const response = ref('')
const timerHidden = ref(false)
const count = ref(60)
const timerColor = ref<'green' | 'red' | null>(null)
let interval = 0

// timerDecrement, a cada segundo desde que o popup abre
function timerDecrement() {
  count.value--
  if (count.value <= 0) {
    timerColor.value = 'red'
    clearInterval(interval)
  } else timerColor.value = 'green'
}

watch(() => qe.open, (open) => {
  clearInterval(interval)
  if (!open) return
  answerFinal.value = 0
  response.value = ''
  timerHidden.value = false
  count.value = 60
  timerColor.value = null
  interval = window.setInterval(timerDecrement, 1000)
}, { immediate: true })
onUnmounted(() => clearInterval(interval))

function respond() {
  const r = qe.answer(answerFinal.value)
  response.value = r.html
  if (r.hideTimer) timerHidden.value = true
}
</script>

<template>
  <FancyBox v-if="qe.open && qe.question" @closed="qe.open = false">
    <div id="questionsEvent" style="display: block;">
      <form name="questionEventForm">

        <div id="answerQuestion">
          <p class="congratulations">Parabéns {{ name }}!</p>
          <p>&nbsp;</p>
          <p>Você foi sorteado para participar do evento Show do Milhão.</p>
          <p>Responda corretamente a pergunta abaixo, e ganha um prêmio!</p>
          <p>Boa sorte e obrigado pela sua participação no {{ title }}.</p>
        </div>

        <div id="optionsQuestion">
          <div id="stringQuestion">{{ qe.question.question }}</div>
          <div id="stringsAnswer">
            <input id="answerFinal" type="hidden" :value="answerFinal" name="answerFinal" />
            <template v-for="(a, i) in qe.question.answers" :key="i"><input id="asnwer" type="radio" name="asnwer" :value="i + 1" @click="answerFinal = i + 1" /> {{ a }}<br /></template>
          </div>
          <div id="buttonSubmit"><input type="button" :value="P.LDPU_QUESTION_EVENT_INPUT_RESPONSE" @click="respond"></div>
          <div id="reponseSubmit" v-html="response"></div>
        </div>

        <div id="timerString" :style="timerHidden ? { display: 'none' } : undefined">Tempo restante: <div id="timerCount" :style="timerColor ? { color: timerColor } : undefined">{{ count > 0 ? `${count} segundos.` : 'Tempo esgotado.' }}</div></div>

      </form>
    </div>
  </FancyBox>
  <div v-else-if="!qe.question" id="questionsEvent" style="display: none;">
    <form name="questionEventForm">
      <div id="answerQuestion">
        <p class="congratulations">Parabéns {{ name }}!</p>
        <p>&nbsp;</p>
        <p>Você foi sorteado para participar do evento Show do Milhão.</p>
        <p>Responda corretamente a pergunta abaixo, e ganha um prêmio!</p>
        <p>Boa sorte e obrigado pela sua participação no {{ title }}.</p>
      </div>

      <div id="optionsQuestion">
        <div id="reponseSubmit"></div>
      </div>

      <div id="timerString">Tempo restante: <div id="timerCount">60 segundos.</div></div>
    </form>
  </div>
</template>
