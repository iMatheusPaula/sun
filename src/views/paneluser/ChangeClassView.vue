<script setup lang="ts">
// paneluser[CHANGE_CLASS].tpl.php + ldPanelUser::loadOptionsChangeClass (lista de classes da VESION_MUSERVER 2).
// Quests e magias zeradas (RESET_QUESTS / RESET_SKILLS) não têm campo no banco do front.
import { ref } from 'vue'
import CharacterOption from '@/components/paneluser/CharacterOption.vue'
import { CLASS_CODES } from '@/config/settings'
import type { CharacterRow } from '@/data/db'
import * as P from '@/lang/ldpaneluser'

// VESION_MUSERVER 2 (Season 2): DW, SM, DK, BK, FE, ME, MG, DL
const CLASSES = [0, 1, 16, 17, 32, 33, 48, 64].map((code) => CLASS_CODES.find(([c]) => c === code)!)
const newclass = ref(String(CLASSES[0]![0]))
const done = ref(false)

function change(c: CharacterRow) {
  c.class = Math.trunc(Number(newclass.value))
  done.value = true
}
</script>

<template>
  <CharacterOption title="Mudar Classe">
    <template #default="{ char }">
      <div v-if="done" class="qdestaques2">{{ P.LDPU_CHANGE_CLASS_SUCCESS }}</div>
      <div v-else class="quadros">
        <form action="" method="post" @submit.prevent="change(char)">
          <strong>{{ P.LDPU_CHANGE_CLASS_NEW_CLASS }}: </strong>
          <select v-model="newclass" name="newclass">
            <option v-for="[code, name] in CLASSES" :key="code" :value="String(code)">{{ name }}</option>
          </select>
          <input type="submit" class="button" :value="P.LDPU_CHANGE_CLASS_SUBMIT" />
        </form>
      </div>
    </template>
  </CharacterOption>
</template>
