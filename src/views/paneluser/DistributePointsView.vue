<script setup lang="ts">
// paneluser[DISTRIBUTE_POINTS].tpl.php + ldPanelUser::loadOptionsDistributePoints, com a validação que o
// original fazia no navegador (varNumeric, maxPoints no onblur de cada campo, leftPoints)
import { computed, reactive, ref } from 'vue'
import CharacterOption from '@/components/paneluser/CharacterOption.vue'
import { DISTRIBUTE_POINTS_MAX } from '@/config/panel'
import { sprintf } from '@/lang/format'
import type { CharacterRow } from '@/data/db'
import * as P from '@/lang/ldpaneluser'

type Field = 'forca' | 'agili' | 'vital' | 'energ' | 'comman'
const STAT: Record<Field, keyof CharacterRow> = { forca: 'strength', agili: 'dexterity', vital: 'vitality', energ: 'energy', comman: 'leadership' }
const LABEL: Record<Field, string> = {
  forca: P.LDPU_DISTRIBUTE_POINTS_STRENGTH, agili: P.LDPU_DISTRIBUTE_POINTS_DEXTERITY, vital: P.LDPU_DISTRIBUTE_POINTS_VITALITY,
  energ: P.LDPU_DISTRIBUTE_POINTS_ENERGY, comman: P.LDPU_DISTRIBUTE_POINTS_LEADERSHIP,
}

const values = reactive<Record<Field, string>>({ forca: '0', agili: '0', vital: '0', energ: '0', comman: '0' })
const marks = reactive<Partial<Record<Field, 'ok' | 'error' | 'neutral'>>>({})
const notes = reactive<Partial<Record<Field, string>>>({})
const totalError = ref(false)
const fieldError = ref(false)
const result = ref<{ ok: boolean; html: string } | null>(null)

const fields = (c: CharacterRow): Field[] => (c.class === 64 || c.class === 65 ? ['forca', 'agili', 'vital', 'energ', 'comman'] : ['forca', 'agili', 'vital', 'energ'])
const num = (v: string) => (v === '' ? 0 : parseFloat(v))
const used = computed(() => (Object.keys(values) as Field[]).reduce((sum, f) => sum + num(values[f]), 0))
const MARK_STYLE = { ok: { border: '1px solid #00D800', color: '#009F00' }, error: { border: '1px solid #FF8080', color: '#FF2121' }, neutral: { border: '1px solid #CFCFCF', color: '#777' } }

// só dígitos (e backspace), como varNumeric
function onlyDigits(e: KeyboardEvent) {
  if (e.key.length === 1 && !/[0-9]/.test(e.key)) e.preventDefault()
}

function leftPoints(c: CharacterRow, f: Field) {
  if (used.value > c.points) {
    marks[f] = 'error'
    totalError.value = true
  } else {
    totalError.value = false
    marks[f] = num(values[f]) === 0 ? 'neutral' : 'ok'
  }
}

function maxPoints(c: CharacterRow, f: Field) {
  const current = c[STAT[f]] as number
  if (current + num(values[f]) > DISTRIBUTE_POINTS_MAX) {
    marks[f] = 'error'
    notes[f] = sprintf(P.LDPU_DISTRIBUTE_POINTS_MAX_POINTS_AMOUNT, DISTRIBUTE_POINTS_MAX)
    fieldError.value = true
  } else {
    fieldError.value = false
    notes[f] = num(values[f]) === 0 ? `${P.LDPU_DISTRIBUTE_POINTS_CURRENT_AMOUNT}: ${current}` : `${P.LDPU_DISTRIBUTE_POINTS_NEXT_AMOUNT}: ${current + num(values[f])}`
    marks[f] = num(values[f]) === 0 ? 'neutral' : 'ok'
  }
  leftPoints(c, f)
}

function distribute(c: CharacterRow) {
  const all = fields(c)
  const total = all.reduce((sum, f) => sum + num(values[f]), 0)
  const blue = (html: string) => (result.value = { ok: true, html })
  const red = (html: string) => (result.value = { ok: false, html })
  if (total > c.points) return red(P.LDPU_DISTRIBUTE_POINTS_NOT_HAVE_AMOUNT)
  if (all.some((f) => /[^0-9]/.test(values[f]))) return red(P.LDPU_DISTRIBUTE_ONLY_NUMBERS)
  if (all.some((f) => num(values[f]) + (c[STAT[f]] as number) > DISTRIBUTE_POINTS_MAX)) return red(sprintf(P.LDPU_DISTRIBUTE_MAXIMUM_POINTS, DISTRIBUTE_POINTS_MAX))
  c.points -= Math.trunc(total)
  for (const f of all) (c[STAT[f]] as number) += Math.trunc(num(values[f]))
  blue(P.LDPU_DISTRIBUTE_SUCCESS)
}
</script>

<template>
  <CharacterOption title="Distribuir pontos">
    <template #default="{ char, selected: name }">
      <div v-if="result" :class="result.ok ? 'qdestaques2' : 'qdestaques'" v-html="result.html"></div>
      <div v-else class="quadros">
        <form action="" method="post" @submit.prevent="distribute(char)">
          <table border="0">
            <tbody>
              <tr>
                <td align="center" colspan="3"><em>{{ P.LDPU_DISTRIBUTE_POINTS_CHARACTER }}:</em> {{ name }}</td>
              </tr>
              <tr>
                <td align="center" colspan="3">{{ P.LDPU_DISTRIBUTE_POINTS_YOU_HAVE }}: <strong id="restam_ponts">{{ Math.max(0, char.points - used) }}</strong> {{ P.LDPU_DISTRIBUTE_POINTS_OF_DISTRIBUTE }}</td>
              </tr>
              <tr>
                <td align="center" colspan="3"><strong id="erro_max_ponts" v-html="totalError ? P.LDPU_DISTRIBUTE_POINTS_NOT_HAVE_AMOUNT : ''"></strong></td>
              </tr>
              <tr v-for="(f, i) in fields(char)" :key="f">
                <td align="right" :width="i === 0 ? '48%' : undefined"><strong>{{ LABEL[f] }}: </strong></td>
                <td :width="i === 0 ? '10%' : undefined"><input :id="f" v-model="values[f]" :name="f" maxlength="5" size="10" type="text" class="" :style="marks[f] ? MARK_STYLE[marks[f]!] : undefined" @keypress="onlyDigits" @blur="maxPoints(char, f)" /></td>
                <td><div :id="`${f}_erro`" v-html="notes[f] ?? `${P.LDPU_DISTRIBUTE_POINTS_CURRENT_AMOUNT}: ${char[STAT[f]]}`"></div></td>
              </tr>
              <tr>
                <td align="center" colspan="3"><input id="button_submit" type="submit" class="button" :value="P.LDPU_DISTRIBUTE_POINTS_SUBMIT" :disabled="totalError || fieldError" /></td>
              </tr>
            </tbody>
          </table>
        </form>
      </div>
    </template>
  </CharacterOption>
</template>
