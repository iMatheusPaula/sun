import { defineStore } from 'pinia'
import { reactive, ref, watch } from 'vue'
import { GAME_QUESTION } from '@/config/panel'
import type { Question } from '@/data/db'
import { itemName } from '@/data/itemdb'
import { sprintf } from '@/lang/format'
import * as P from '@/lang/ldpaneluser'
import { useDbStore } from './db'
import { useSessionStore } from './session'

// Evento de perguntas ("Show do Milhão") do painel do usuário: ldPanelUser::questionsEventCheck sorteia uma
// pergunta a cada página do painel e loadOptionsQuestionsEvent (ajax option=QUESTIONS_EVENT) confere a resposta;
// quem acerta ganha um serial para o Golden Archer. O $_SESSION['GAME_QUESTION_*'] fica na aba (sessionStorage).
const STORAGE_KEY = 'sunonline:questions'

interface SessionState {
  login: string | null
  lastLuck: number // GAME_QUESTION_LAST_LUCK: antes disso não sorteia de novo
  enabled: boolean // GAME_QUESTION_ENABLE: há uma pergunta aberta para responder
  number: number // GAME_QUESTION_NUMBER
  time: number // GAME_QUESTION_TIME: prazo da resposta
}

const blank = (login: string | null): SessionState => ({ login, lastLuck: 0, enabled: false, number: 0, time: 0 })

function load(): SessionState {
  try {
    const raw = sessionStorage.getItem(STORAGE_KEY)
    if (raw) return { ...blank(null), ...(JSON.parse(raw) as Partial<SessionState>) }
  } catch {
    // storage indisponível: o estado vale até recarregar
  }
  return blank(null)
}

// rand($min, $max) do PHP: inteiro com os dois extremos incluídos
const rand = (min: number, max: number) => min + Math.floor(Math.random() * (max - min + 1))

export const useQuestionsEventStore = defineStore('questionsEvent', () => {
  const db = useDbStore()
  const session = useSessionStore()
  const state = reactive<SessionState>(load())
  watch(state, (value) => {
    try {
      sessionStorage.setItem(STORAGE_KEY, JSON.stringify(value))
    } catch {
      // sem storage
    }
  }, { deep: true })

  const question = ref<Question | null>(null) // sorteada nesta página
  const open = ref(false) // popup aberto

  // a sessão do PHP era de um login; outra conta na mesma aba começa do zero
  function ownState() {
    if (state.login !== session.login) Object.assign(state, blank(session.login))
  }

  // cada página do painel: sem sorteio (opção desligada, sem permissão, módulo de screenshots) só esconde o bloco
  function pageLoad(draw: boolean) {
    question.value = null
    open.value = false
    if (draw) questionsEventCheck()
  }

  function questionsEventCheck() {
    ownState()
    if (!GAME_QUESTION.ENABLE) return
    const now = Date.now()
    if (state.lastLuck > now) return
    if (rand(GAME_QUESTION.LUCK[0], GAME_QUESTION.LUCK[1]) < GAME_QUESTION.LUCK[2]) return
    const list = db.db.questions
    const q = list[rand(0, list.length - 1)] // ORDER BY NEWID(); tabela vazia: sem pergunta
    if (!q) return
    question.value = q
    open.value = true
    state.enabled = true
    state.lastLuck = now + GAME_QUESTION.WAIT * 1000
    state.number = q.number
    state.time = now + 60_000
  }

  // createSerial: três grupos de 4 hexadecimais, sem repetir um serial já cadastrado
  function createSerial(): [string, string, string] {
    const part = () => rand(0, 0xffff).toString(16).toUpperCase().padStart(4, '0')
    for (;;) {
      const serial: [string, string, string] = [part(), part(), part()]
      if (!db.db.goldenArcher.some((g) => g.serial.join('') === serial.join(''))) return serial
    }
  }

  // loadOptionsQuestionsEvent: o HTML que ia para #reponseSubmit e se o contador some (o <script> da resposta)
  function answer(value: number): { html: string; hideTimer: boolean } {
    const red = (m: string) => `<p class="incorrectResponse">${m}</p>`
    if (!GAME_QUESTION.ENABLE) return { html: '', hideTimer: false }
    ownState()
    if (!state.enabled) return { html: red(P.LDPU_QUESTION_EVENT_ALREADY_ANSWERED), hideTimer: false }
    if (value === 0) return { html: red(P.LDPU_QUESTION_EVENT_SELECT_ANSWER), hideTimer: false }
    state.enabled = false // libera o sistema para novas perguntas
    if (state.time < Date.now()) return { html: red(P.LDPU_QUESTION_EVENT_TIME_END), hideTimer: false }

    const q = db.db.questions.find((x) => x.number === state.number)
    if (!q || value !== q.correct) return { html: red(P.LDPU_QUESTION_EVENT_RESPONSE_INCORRECT), hideTimer: true }

    // prêmio sorteado entre os do tipo da pergunta (shuffle + o primeiro)
    const premiums = GAME_QUESTION.PREMIUM[q.type] ?? GAME_QUESTION.PREMIUM.A
    const { idCategorie, idItem, options } = premiums[rand(0, premiums.length - 1)]!
    const serial = createSerial()
    db.db.goldenArcher.push({
      id: Math.max(0, ...db.db.goldenArcher.map((g) => g.id)) + 1,
      account: session.login!, serial, status: 0,
      item: { cat: idCategorie, idx: idItem, level: options.Level, option: options.Option, skill: options.Skill, luck: options.Luck, excellent: [...options.Excellent] },
    })
    const html = `<p class="correctResponse">${P.LDPU_QUESTION_EVENT_RESPONSE_CORRECT}</p><p>&nbsp;</p>`
      + `<p>${sprintf(P.LDPU_QUESTION_EVENT_RESPONSE_CORRECT_RECEIVER_ITEM, itemName(idCategorie, idItem))}</p><p>&nbsp;</p>`
      + `<p class="serial">${serial.join('-')}</p>`
    return { html, hideTimer: true }
  }

  return { question, open, pageLoad, answer }
})
