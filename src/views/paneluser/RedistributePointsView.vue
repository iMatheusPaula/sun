<script setup lang="ts">
// paneluser[REDISTRIBUTE_POINTS].tpl.php + ldPanelUser::loadOptionsRedistributePoints: devolve todos os
// pontos (menos os 30 de cada status) para distribuir de novo
import { ref } from 'vue'
import CharacterOption from '@/components/paneluser/CharacterOption.vue'
import { isDarkLord } from '@/lib/reset'
import type { CharacterRow } from '@/data/db'
import * as P from '@/lang/ldpaneluser'

const done = ref(false)

function total(c: CharacterRow) {
  const base = c.points + c.strength + c.dexterity + c.vitality + c.energy - 120
  return isDarkLord(c.class) ? base + (c.leadership - 30) : base
}

function redistribute(c: CharacterRow) {
  Object.assign(c, { points: total(c), strength: 30, dexterity: 30, vitality: 30, energy: 30 })
  if (isDarkLord(c.class)) c.leadership = 30
  done.value = true
}
</script>

<template>
  <CharacterOption title="Redistribuir pontos">
    <template #default="{ char, selected: name }">
      <div v-if="done" class="qdestaques2">{{ P.LDPU_REDISTRIBUTE_POINTS_SUCCESS }}</div>
      <div v-else class="quadros">
        <em>{{ P.LDPU_REDISTRIBUTE_POINTS_CHARACTER }}:</em> <strong>{{ name }}</strong> <br />
        <em>{{ P.LDPU_REDISTRIBUTE_POINTS_LEVEL_UP_POINTS }}:</em> <strong>{{ char.points }}</strong> <br />
        <em>{{ P.LDPU_REDISTRIBUTE_POINTS_STRENGTH }}:</em> <strong>{{ char.strength }}</strong> <br />
        <em>{{ P.LDPU_REDISTRIBUTE_POINTS_DEXTERITY }}:</em> <strong>{{ char.dexterity }}</strong> <br />
        <em>{{ P.LDPU_REDISTRIBUTE_POINTS_VITALITY }}:</em> <strong>{{ char.vitality }}</strong> <br />
        <em>{{ P.LDPU_REDISTRIBUTE_POINTS_ENERGY }}:</em> <strong>{{ char.energy }}</strong> <br />
        <template v-if="isDarkLord(char.class)"><em>{{ P.LDPU_REDISTRIBUTE_POINTS_LEADERSHIP }}:</em> <strong>{{ char.leadership }}</strong> <br /></template>
        <em>{{ P.LDPU_REDISTRIBUTE_POINTS_TOTAL_POINTS }}:</em> <strong>{{ total(char) }}</strong> <br />
        <input type="button" class="button" :value="P.LDPU_REDISTRIBUTE_POINTS_SUBMIT" @click="redistribute(char)" />
      </div>
    </template>
  </CharacterOption>
</template>
