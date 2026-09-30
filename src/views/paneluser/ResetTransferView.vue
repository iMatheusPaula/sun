<script setup lang="ts">
// paneluser[RESET_TRANSFER].tpl.php + ldPanelUser::loadOptionsResetTransfer. Passo 1: escolher origem e
// destino (?character1=&character2=); passo 2: quantos resets transferir.
import { computed, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { usePanelAccount } from '@/lib/paneluser'
import { RESET_TRANSFER_MIN } from '@/config/panel'
import { sprintf } from '@/lang/format'
import * as P from '@/lang/ldpaneluser'

const route = useRoute()
const router = useRouter()
const { account, characters } = usePanelAccount()

const pick = reactive({ character1: '', character2: '' })
const c1Name = String(route.query.character1 ?? '')
const c2Name = String(route.query.character2 ?? '')
const loaded = route.query.character1 !== undefined || route.query.character2 !== undefined
const c1 = characters.value.find((c) => c.name === c1Name)
const c2 = characters.value.find((c) => c.name === c2Name)
const transresets = ref('0')
const result = ref<{ ok: boolean; text: string } | null>(null)

const step1Error = computed(() => {
  if (!loaded) return null
  if (c1Name === c2Name) return { cls: 'qdestaques2', text: P.LDPU_RESET_TRANS_TEXT_CHOOSE_NOT_EQUAL }
  if (account.value!.online) return { cls: 'qdestaques2', text: P.LDPU_YOU_MUST_BE_OFFLINE }
  if (!c1 || !c2) return { cls: 'quadros', text: P.LDPU_THIS_CHARACTER_NOT_EXIST }
  return null
})

function load() {
  router.push({ path: route.path, query: { character1: pick.character1, character2: pick.character2 } })
}

function onlyDigits(e: KeyboardEvent) {
  if (e.key.length === 1 && !/[0-9]/.test(e.key)) e.preventDefault()
}

function transfer() {
  const amount = Number(transresets.value)
  const red = (text: string) => (result.value = { ok: false, text })
  if (transresets.value.trim() === '' || Number.isNaN(amount)) return red(P.LDPU_RESET_TRANS_TEXT_FILL_ONLY_NUMBERS)
  if (amount < 1) return red(P.LDPU_RESET_TRANS_TEXT_INVALID_AMOUNT)
  if (amount < RESET_TRANSFER_MIN) return red(sprintf(P.LDPU_RESET_TRANS_TEXT_MINIMUM_AMOUNT, RESET_TRANSFER_MIN))
  if (c1!.resets < amount) return red(P.LDPU_RESET_TRANS_TEXT_NOT_HAVE_AMOUNT_RESETS)
  const restore = { points: 0, strength: 30, dexterity: 30, energy: 30, vitality: 30, leadership: 30 }
  Object.assign(c1!, restore, { resets: c1!.resets - amount })
  Object.assign(c2!, restore, { resets: c2!.resets + amount })
  result.value = { ok: true, text: P.LDPU_RESET_TRANS_TEXT_SUCCESS }
}
</script>

<template>
  <h1 style="margin-top: 20px;">Transferir resets</h1>
  <br />

  <div id="etc">
    <div class="legend" style="margin-bottom: 25px;">
      <h3 class="legend-title"><span style="font-size: 13px;">Escolha o personagem:</span></h3>
      <div style="margin-bottom: 8px; margin-top: 10px;">
        <form action="" method="get" @submit.prevent="load">
          <strong>Personagem de origem:&nbsp;</strong>
          <select v-model="pick.character1" name="character1" class="inputbox">
            <option value="" disabled selected>Selecione aqui</option>
            <option v-for="c in characters" :key="c.name" :value="c.name">{{ c.name }}</option>
          </select>
          &nbsp;&nbsp;&nbsp;
          <strong>Personagem de destino:</strong>
          <select v-model="pick.character2" name="character2" class="inputbox">
            <option value="" disabled selected>Selecione aqui</option>
            <option v-for="c in characters" :key="c.name" :value="c.name">{{ c.name }}</option>
          </select>
          <input type="submit" value="Carregar" class="button" />
        </form>
      </div>
    </div>
    <div class="quadrosOut">
      <div v-if="step1Error" :class="step1Error.cls">{{ step1Error.text }}</div>
      <div v-else-if="result" :class="result.ok ? 'qdestaques2' : 'qdestaques'">{{ result.text }}</div>
      <div v-else-if="loaded" class="quadros">
        <form action="" method="POST" @submit.prevent="transfer">
          <table width="100%" border="0">
            <tbody>
              <tr>
                <td align="right" width="45%"><strong>{{ P.LDPU_RESET_TRANS_TEXT_CHARACTER_ORIGIN }}: </strong></td>
                <td><input id="character1" name="character1" size="10" type="text" class="" :value="c1Name" readonly /> {{ c1!.resets }} {{ P.LDPU_RESET_TRANS_TEXT_RESETS }}</td>
              </tr>
              <tr>
                <td align="right" width="45%"><strong>{{ P.LDPU_RESET_TRANS_TEXT_CHARACTER_DESTINY }}: </strong></td>
                <td><input id="character2" name="character2" size="10" type="text" class="" :value="c2Name" readonly /> {{ c2!.resets }} {{ P.LDPU_RESET_TRANS_TEXT_RESETS }}</td>
              </tr>
              <tr>
                <td align="right" width="45%"><strong>{{ P.LDPU_RESET_TRANS_TEXT_TRANSFER }}: </strong></td>
                <td><input id="transresets" v-model="transresets" name="transresets" maxlength="5" size="10" type="text" class="" @keypress="onlyDigits" /> <strong>{{ P.LDPU_RESET_TRANS_TEXT_RESETS }}</strong></td>
              </tr>
              <tr>
                <td colspan="2" align="center"><br /><strong v-html="P.LDPU_RESET_TRANS_TEXT_WARNING"></strong></td>
              </tr>
            </tbody>
          </table>
          <div align="center"><input id="button_submit" type="submit" :value="P.LDPU_RESET_TRANS_TEXT_SUBMIT" class="button" /></div>
        </form>
      </div>
    </div>
  </div>
</template>
