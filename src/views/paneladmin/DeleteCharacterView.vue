<script setup lang="ts">
// paneladmin[DELETE_CHARACTER].tpl.php + ldPanelAdmin::optionLoadDeleteCharacter: apaga o personagem e o tira do
// AccountCharacter (último personagem usado).
import { ref } from 'vue'
import { useDbStore } from '@/stores/db'
import { isEmpty } from '@/lib/paneladmin'
import * as PA from '@/lang/ldpaneladmin'

const store = useDbStore()
const name = ref('')
const response = ref('')

function remove() {
  const typed = name.value
  name.value = '' // o POST recarregava a página com o campo vazio
  if (isEmpty(typed)) return
  const c = store.db.characters.find((x) => x.name.toLowerCase() === typed.toLowerCase())
  if (!c) return (response.value = `<div class='qdestaques'>${PA.LDPA_DELETE_CHARACTER_CHAR_NOT_FOUND}</div>`)
  store.db.characters = store.db.characters.filter((x) => x !== c)
  for (const a of store.db.accounts) if (a.lastCharacter === c.name) delete a.lastCharacter
  response.value = `<div class='qdestaques2'>${PA.LDPA_DELETE_CHARACTER_TEXT_SUCCESS}</div>`
}
</script>

<template>
  <h1 style="margin-top: 20px;">Deletar personagem</h1>

  <div class="legend" style="margin-top: 25px; padding: 10px;">
    <h3 class="legend-title"><span style="font-size: 13px;">Preencha os dados abaixo:</span></h3>
    <form action="" method="post" @submit.prevent="remove">
      <em>Personagem:</em> <input v-model="name" name="character" type="text" class="inputbox" /> <input type="submit" value="Deletar personagem." class="button" />
    </form>
    <div class="quadrosOut" v-html="response"></div>
  </div>
</template>
