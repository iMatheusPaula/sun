<script setup lang="ts">
// paneluser[MOVE_CHARACTER].tpl.php + ldPanelUser::loadOptionsMoveCharacter (verify_coorderns / valid_coordener)
import { reactive, ref } from 'vue'
import CharacterOption from '@/components/paneluser/CharacterOption.vue'
import { MAPS } from '@/config/settings'
import type { CharacterRow } from '@/data/db'
import * as P from '@/lang/ldpaneluser'

type Coord = 'coordX' | 'coordY'
// null = ainda a posição atual do personagem (o valor inicial dos campos)
const form = reactive<{ movemap: string; coordX: string | null; coordY: string | null }>({ movemap: String(MAPS[0]![0]), coordX: null, coordY: null })
const marks = reactive<Partial<Record<Coord, 'ok' | 'error'>>>({})
const notes = reactive<Partial<Record<Coord, string>>>({})
const done = ref(false)

const current = (c: CharacterRow, coord: Coord) => form[coord] ?? String(coord === 'coordX' ? c.x : c.y)

function onlyDigits(e: KeyboardEvent) {
  if (e.key.length === 1 && !/[0-9]/.test(e.key)) e.preventDefault()
}

function valid(c: CharacterRow, coord: Coord) {
  const value = Number(current(c, coord))
  if (value < 0) [marks[coord], notes[coord]] = ['error', P.LDPU_MOVE_CHARCTER_MUST_MORE_ZERO]
  if (value > 255) [marks[coord], notes[coord]] = ['error', P.LDPU_MOVE_CHARCTER_MUST_LOWER_255]
  if (value >= 0 && value <= 255) [marks[coord], notes[coord]] = ['ok', P.LDPU_MOVE_CHARCTER_INVALID_COORDENATE]
}

function pickMap(c: CharacterRow) {
  const map = MAPS.find(([n]) => String(n) === form.movemap)
  form.coordX = String(map?.[2] ?? 125)
  form.coordY = String(map?.[3] ?? 125)
  valid(c, 'coordX')
  valid(c, 'coordY')
}

function move(c: CharacterRow) {
  Object.assign(c, {
    map: Math.trunc(Number(form.movemap)) || 0,
    x: Math.trunc(Number(current(c, 'coordX'))) || 0,
    y: Math.trunc(Number(current(c, 'coordY'))) || 0,
  })
  done.value = true
}

const styleOf = (m?: 'ok' | 'error') => (m === 'ok' ? { border: '1px solid #00D800', color: '#009F00' } : m === 'error' ? { border: '1px solid #FF8080', color: '#FF2121' } : undefined)
const input = (coord: Coord, e: Event) => (form[coord] = (e.target as HTMLInputElement).value)
</script>

<template>
  <CharacterOption title="Mover personagem">
    <template #default="{ char, selected: name }">
      <div v-if="done" class="qdestaques2">{{ P.LDPU_MOVE_CHARCTER_SUCCESS }}</div>
      <div v-else class="quadros">
        <form action="" method="post" @submit.prevent="move(char)">
          <table width="100%" border="0">
            <tbody>
              <tr>
                <td colspan="3" align="center"><strong>{{ P.LDPU_MOVE_CHARCTER_CHARACTER }}: {{ name }}</strong></td>
              </tr>
              <tr>
                <td align="right" width="47%"><strong>{{ P.LDPU_MOVE_CHARCTER_MOVE_TO }}: </strong></td>
                <td> <select v-model="form.movemap" name="movemap" @change="pickMap(char)"><option v-for="([number, mapName], i) in MAPS" :key="i" :value="String(number)">{{ mapName }}</option></select> </td>
              </tr>
              <tr>
                <td align="right" width="47%"><strong>{{ P.LDPU_MOVE_CHARCTER_COORD_X }}: </strong></td>
                <td><input id="coordX" :value="current(char, 'coordX')" name="coordX" size="10" maxlength="3" :style="styleOf(marks.coordX)" @input="input('coordX', $event)" @keypress="onlyDigits" @blur="valid(char, 'coordX')" /></td>
                <td><div id="coordX_erro">{{ notes.coordX }}</div></td>
              </tr>
              <tr>
                <!-- LDPU_MOVE_CHARCTER_COORD_y: a classe usa essa constante, e o .lang.php define ..._COORD_Y; o original mostrava o nome -->
                <td align="right" width="47%"><strong>LDPU_MOVE_CHARCTER_COORD_y: </strong></td>
                <td><input id="coordY" :value="current(char, 'coordY')" name="coordY" size="10" maxlength="3" :style="styleOf(marks.coordY)" @input="input('coordY', $event)" @keypress="onlyDigits" @blur="valid(char, 'coordY')" /></td>
                <td><div id="coordY_erro">{{ notes.coordY }}</div></td>
              </tr>
            </tbody>
          </table>
          <div align="center"><input id="button_submit" type="submit" class="button" :value="P.LDPU_MOVE_CHARCTER_SUBMIT" :disabled="marks.coordX === 'error' || marks.coordY === 'error'" /></div>
        </form>
      </div>
    </template>
  </CharacterOption>
</template>
