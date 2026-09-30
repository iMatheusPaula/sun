<script setup lang="ts">
// paneladmin[ADD_VIP|DELETE_VIP|TRANSFORM_VIP].tpl.php + ldPanelAdmin::optionLoadManageVip(1|2|3): adicionar dias de
// vip a uma conta Free, voltar a conta a Free, ou trocar o plano e as datas de início/fim (o segundo formulário).
// As listas de hora, minuto e segundo são as do original: horas de 1 a 23 marcadas pelo relógio de 12 horas e minutos
// e segundos de 1 a 60 (sem o 0) — salvar sem mexer pode mudar o horário.
import { computed, reactive, ref } from 'vue'
import { useAccountsStore } from '@/stores/accounts'
import { isEmpty, isNumeric } from '@/lib/paneladmin'
import { ACTIVE_VIPS, VIP_NAMES } from '@/config/settings'
import { phpDate } from '@/lib/date'
import { sprintf } from '@/lang/format'
import type { AccountRow } from '@/data/db'
import * as PA from '@/lang/ldpaneladmin'

const props = defineProps<{ type: 1 | 2 | 3 }>()
const accounts = useAccountsStore()
const blank = () => ({ account: '', flat: '1', days: '0' })
const form = reactive(blank())
const response = ref('')

// Transformar: o formulário das datas (subWrite) da conta carregada
const loaded = ref<AccountRow | null>(null)
const loadedLogin = ref('')
const dates = reactive<Record<string, string>>({})
const year = new Date().getFullYear()
const range = (from: number, to: number) => Array.from({ length: to - from + 1 }, (_, i) => from + i)
const parts: [key: string, label: string, options: number[], format: string][] = [
  ['Day', PA.LDPA_MVIPS_ALTER_TEXT_SELECT_DAY, range(1, 31), 'j'],
  ['Month', PA.LDPA_MVIPS_ALTER_TEXT_SELECT_MONTH, range(1, 12), 'n'],
  ['Year', PA.LDPA_MVIPS_ALTER_TEXT_SELECT_YEAR, range(year - 1, year + 9), 'Y'],
  ['Hour', PA.LDPA_MVIPS_ALTER_TEXT_SELECT_HOUR, range(1, 23), 'h'],
  ['Minutes', PA.LDPA_MVIPS_ALTER_TEXT_SELECT_MINUTE, range(1, 60), 'i'],
  ['Seconds', PA.LDPA_MVIPS_ALTER_TEXT_SELECT_SECOND, range(1, 60), 's'],
]
// separador depois de cada select de data, como no .tpl
const SEPARATORS = [' /', ' /', ` ${PA.LDPA_MVIPS_ALTER_TEXT_SELECT_THE} `, ' : ', ' : ', '']
const plans = computed(() => ACTIVE_VIPS.map((plan) => [plan, VIP_NAMES[plan]] as const))

// marca a opção igual a date(formato) (comparação solta do PHP: "08" == 8); sem nenhuma, o navegador fica na primeira
function preset(prefix: 'begin' | 'end', ms: number) {
  for (const [key, , options, format] of parts) {
    const current = Number(phpDate(format, new Date(ms)))
    dates[prefix + key] = String(options.includes(current) ? current : options[0])
  }
}

function check(login: string, days = ''): AccountRow | string {
  const red = (m: string) => `<div class="qdestaques">${m}</div>`
  if (isEmpty(login)) return red(PA.LDPA_FILL_LOGIN)
  if (props.type === 1 && !isNumeric(days)) return red(PA.LDPA_MVIPS_TEXT_INVALID_DAYS)
  const a = accounts.find(login)
  if (!a) return red(PA.LDPA_INVALID_LOGIN)
  if (props.type === 1 && a.vip > 0) return red(PA.LDPA_MVIPS_TEXT_ALREADY_HAVE_VIP)
  if (props.type !== 1 && a.vip === 0) return red(PA.LDPA_MVIPS_TEXT_NOT_HAVE_VIP)
  return a
}

// o POST recarregava a página: a resposta aparece com o formulário vazio
function submit() {
  const typed = { ...form }
  Object.assign(form, blank())
  loaded.value = null
  const a = check(typed.account, typed.days)
  if (typeof a === 'string') return (response.value = a)
  response.value = ''
  if (props.type === 1) {
    const days = Math.trunc(Number(typed.days))
    const now = Date.now()
    Object.assign(a, { vip: Number(typed.flat), vipBegin: now, vipEnd: now + days * 86400_000 })
    response.value = `<div class='qdestaques2'>${sprintf(PA.LDPA_MVIPS_TEXT_ADD_SUCCESS, days)}</div>`
  } else if (props.type === 2) {
    a.vip = 0
    response.value = `<div class='qdestaques2'>${PA.LDPA_MVIPS_TEXT_REMOVE_SUCCESS}</div>`
  } else {
    loadedLogin.value = typed.account
    preset('begin', a.vipBegin)
    preset('end', a.vipEnd)
    dates.flat = String(a.vip)
    loaded.value = a
  }
}

// subWrite=true: grava plano e datas (mktime)
function save() {
  const a = check(loadedLogin.value)
  loaded.value = null
  if (typeof a === 'string') return (response.value = a)
  const at = (p: 'begin' | 'end') => new Date(+dates[`${p}Year`]!, +dates[`${p}Month`]! - 1, +dates[`${p}Day`]!, +dates[`${p}Hour`]!, +dates[`${p}Minutes`]!, +dates[`${p}Seconds`]!).getTime()
  Object.assign(a, { vip: Number(dates.flat), vipBegin: at('begin'), vipEnd: at('end') })
  response.value = `<div class='qdestaques2'>${PA.LDPA_MVIPS_ALTER_TEXT_SUCCESS}</div>`
}
</script>

<template>
  <h1 style="margin-top: 20px;">{{ type === 1 ? 'Adcionar Vips' : type === 2 ? 'Remover Vips' : 'Transformar Vips' }}</h1>

  <div class="legend" style="margin-top: 25px; padding: 10px;">
    <h3 class="legend-title"><span style="font-size: 13px;">Preencha os dados abaixo:</span></h3>
    <form v-if="type === 1" action="" method="post" @submit.prevent="submit">
      <em>Login:</em><br /><input v-model="form.account" name="account" type="text" class="inputbox" /> <br />
      <em>Plano:</em><br /><select v-model="form.flat" name="flat" class="inputbox"><option v-for="n in 5" :key="n" :value="String(n)">{{ VIP_NAMES[n] }}</option></select> <br />
      <em>Dias:</em><br /><input v-model="form.days" name="days" type="text" class="inputbox" /> dias.<br />
      <input type="submit" value="Adicionar." class="button" style="margin-top: 10px;" />
    </form>
    <form v-else-if="type === 2" action="" method="post" @submit.prevent="submit">
      <em>Login:</em><br /><input v-model="form.account" name="account" type="text" class="inputbox" /> <br />
      <input type="submit" value="Remover." class="button" style="margin-top: 10px;" />
    </form>
    <form v-else action="" method="post" @submit.prevent="submit">
      <em>Login:</em><br /><input v-model="form.account" name="account" type="text" class="inputbox" /> <br />
      <input type="submit" value="Prosseguir." class="button" style="margin-top:10px;" />
    </form>
    <div v-if="loaded" class="quadrosOut">
      <form action="" method="post" class="quadros" @submit.prevent="save"><strong>{{ PA.LDPA_MVIPS_ALTER_TEXT_CONFIG_DATES }}</strong><br /><br /><template v-for="p in (['begin', 'end'] as const)" :key="p"><strong>{{ p === 'begin' ? PA.LDPA_MVIPS_ALTER_TEXT_BEGIN : PA.LDPA_MVIPS_ALTER_TEXT_END }}</strong> <template v-for="([key, label, options], i) in parts" :key="key"><select v-model="dates[p + key]" :name="p + key"><option disabled>{{ label }}</option><option v-for="o in options" :key="o" :value="String(o)">{{ o }}</option></select>{{ SEPARATORS[i] }}</template><br /></template><br /><em>{{ PA.LDPA_MVIPS_ALTER_TEXT_SELECT_WARNING }}</em><br /><br /><strong>{{ PA.LDPA_MVIPS_ALTER_TEXT_SELECT_FLAT }}</strong> <select v-model="dates.flat" name="flat"><option v-for="[plan, name] in plans" :key="plan" :value="String(plan)">{{ name }}</option></select><br /><br /><input type="hidden" name="account" :value="loadedLogin" /><input type="submit" :value="PA.LDPA_TEXT_SAVE_SUBMIT" class="button" /></form>
    </div>
    <div v-else class="quadrosOut" v-html="response"></div>
  </div>
</template>
