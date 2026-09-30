<script setup lang="ts">
// paneladmin[EDIT_CHARACTER].tpl.php + ldPanelAdmin::optionLoadEditCharacter: carrega o personagem num formulário e
// grava tudo de uma vez. No original os selects de classe e de mapa não vinham com a opção atual marcada, então
// salvar sem mexer neles trocava a classe para Dark Wizard e o mapa para Lorencia; aqui vêm marcados.
import { reactive, ref } from 'vue'
import { useDbStore } from '@/stores/db'
import { isEmpty } from '@/lib/paneladmin'
import { MAPS, SEASON_CLASSES, className } from '@/config/settings'
import type { CharacterRow } from '@/data/db'
import * as PA from '@/lang/ldpaneladmin'

const store = useDbStore()
const name = ref('')
const loaded = ref<CharacterRow | null>(null)
const loadedName = ref('')
const fields = ['cLevel', 'LevelUpPoint', 'Experience', 'Strength', 'Dexterity', 'Vitality', 'Energy', 'Leadership', 'Money'] as const
const form = reactive<Record<string, string>>({})
const response = ref('')

const labels: Record<(typeof fields)[number], string> = {
  cLevel: PA.LDPA_TEXT_LEVEL, LevelUpPoint: PA.LDPA_TEXT_LEVEL_UP_POINTS, Experience: PA.LDPA_TEXT_EXPERIENCE,
  Strength: PA.LDPA_TEXT_STRENGTH, Dexterity: PA.LDPA_TEXT_DEXTERITY, Vitality: PA.LDPA_TEXT_VITALITY,
  Energy: PA.LDPA_TEXT_ENERGY, Leadership: PA.LDPA_TEXT_LEADERSHIP, Money: PA.LDPA_TEXT_MONEY,
}

// Write=true com o personagem digitado
function load() {
  loaded.value = null
  response.value = ''
  const typed = name.value
  name.value = '' // o POST recarregava a página com o campo vazio
  if (isEmpty(typed)) return
  const c = store.db.characters.find((x) => x.name.toLowerCase() === typed.toLowerCase())
  if (!c) return (response.value = `<div class='qdestaques'>${PA.LDPA_EDIT_CHARACTER_CHAR_NOT_FOUND}</div>`)
  loadedName.value = typed
  Object.assign(form, {
    Class: String(c.class), cLevel: String(c.level), LevelUpPoint: String(c.points), Experience: String(c.experience),
    Strength: String(c.strength), Dexterity: String(c.dexterity), Vitality: String(c.vitality), Energy: String(c.energy),
    Leadership: String(c.leadership), Money: String(c.money), MapNumber: String(c.map), MapPosX: String(c.x), MapPosY: String(c.y),
    PkCount: String(c.pkCount), PkLevel: String(c.pkLevel), PkTime: String(c.pkTime), CtlCode: String(c.ctlCode), Resets: String(c.resets),
  })
  loaded.value = c
}

// Write=true&character=… : o UPDATE com todos os campos (numéricos no SQL)
function save() {
  const c = store.db.characters.find((x) => x.name.toLowerCase() === loadedName.value.toLowerCase())
  const n = (key: string) => Math.trunc(Number(form[key])) || 0
  if (c) {
    Object.assign(c, {
      class: n('Class'), level: n('cLevel'), points: n('LevelUpPoint'), experience: n('Experience'), strength: n('Strength'),
      dexterity: n('Dexterity'), vitality: n('Vitality'), energy: n('Energy'), leadership: n('Leadership'), money: n('Money'),
      map: n('MapNumber'), x: n('MapPosX'), y: n('MapPosY'), pkCount: n('PkCount'), pkLevel: n('PkLevel'), pkTime: n('PkTime'),
      ctlCode: n('CtlCode'), resets: n('Resets'),
    })
  }
  loaded.value = null
  response.value = `<div class='qdestaques2'>${PA.LDPA_EDIT_CHARACTER_TEXT_SUCCESS}</div>`
}
</script>

<template>
  <h1 style="margin-top: 20px;">Editar personagem</h1>

  <div class="legend" style="margin-top: 25px; padding: 10px;">
    <h3 class="legend-title"><span style="font-size: 13px;">Preencha os dados abaixo:</span></h3>
    <form action="" method="post" @submit.prevent="load">
      <em>Personagem:</em> <input v-model="name" name="character" type="text" class="inputbox" /> <input type="submit" value="Carregar dados." class="button" />
    </form>
    <div v-if="loaded" class="quadrosOut">
      <div class="quadros">
        <form action="" method="post" @submit.prevent="save">
          <em>{{ PA.LDPA_TEXT_LOGIN }}:</em><br /><strong>{{ loaded.account }}</strong> <br />
          <em>{{ PA.LDPA_TEXT_CHARACTER }}:</em><br /><strong>{{ loadedName }}</strong> <br />
          <em>{{ PA.LDPA_TEXT_CLASS }}:</em><br /><select v-model="form.Class" name="Class"><option v-for="[code, label] in SEASON_CLASSES" :key="code" :value="String(code)">{{ label }}</option></select> - <strong>Atual: {{ className(loaded.class) }}</strong><br />
          <template v-for="f in fields" :key="f"><em>{{ labels[f] }}:</em><br /><input v-model="form[f]" :name="f" type="text" /> <br /></template>
          <em>{{ PA.LDPA_TEXT_MAP_NUMBER }}:</em><br /><select v-model="form.MapNumber" name="MapNumber"><option v-for="([num, label], i) in MAPS" :key="i" :value="String(num)">{{ label }}</option></select> <br />
          <em>{{ PA.LDPA_TEXT_MAP_POS_X }}:</em><br /><input v-model="form.MapPosX" name="MapPosX" type="text" /> <br />
          <em>{{ PA.LDPA_TEXT_MAP_POS_Y }}:</em><br /><input v-model="form.MapPosY" name="MapPosY" type="text" /> <br />
          <em>{{ PA.LDPA_TEXT_PKCOUNT }}:</em><br /><input v-model="form.PkCount" name="PkCount" type="text" /> <br />
          <em>{{ PA.LDPA_TEXT_PKLEVEL }}:</em><br /><input v-model="form.PkLevel" name="PkLevel" type="text" /> <br />
          <em>{{ PA.LDPA_TEXT_PKTIME }}:</em><br /><input v-model="form.PkTime" name="PkTime" type="text" /> <br />
          <em>{{ PA.LDPA_TEXT_CTLCODE }}:</em><br /><input v-model="form.CtlCode" name="CtlCode" type="text" /> {{ PA.LDPA_EDIT_CHARACTER_TEXT_CTLCODE_LEGEND }}<br />
          <em>{{ PA.LDPA_TEXT_RESETS }}:</em><br /><input v-model="form.Resets" name="Resets" type="text" /> <br />
          <input type="submit" :value="PA.LDPA_TEXT_SAVE_SUBMIT" class="button" />
        </form>
      </div>
    </div>
    <div v-else class="quadrosOut" v-html="response"></div>
  </div>
</template>
