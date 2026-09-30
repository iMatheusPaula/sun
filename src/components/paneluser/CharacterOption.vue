<script setup lang="ts">
// Moldura das opções de "Gerenciar Personagens" (paneluser[RESET].tpl.php e irmãos): escolher o personagem
// leva a ?character=<nome>; com ele escolhido, as checagens comuns do ldPanelUser (conta online, personagem
// de outra conta) e, se passar, o conteúdo da opção.
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { usePanelAccount } from '@/lib/paneluser'
import type { CharacterRow } from '@/data/db'
import * as P from '@/lang/ldpaneluser'

defineProps<{ title: string }>()
defineSlots<{ default(props: { char: CharacterRow; selected: string }): unknown }>()

const route = useRoute()
const router = useRouter()
const { account, characters } = usePanelAccount()

// lido ao abrir a tela, como na requisição do original: a tela é recriada a cada troca de URL, e o
// personagem continua o mesmo objeto mesmo se a opção o renomear
const name = computed(() => String(route.query.character ?? ''))
const char = characters.value.find((c) => c.name === name.value)

function choose(event: Event) {
  router.push({ path: route.path, query: { character: (event.target as HTMLSelectElement).value } })
}
</script>

<template>
  <h1 style="margin-top: 20px;">{{ title }}</h1>
  <br />

  <div id="etc">
    <div class="legend" style="margin-bottom: 25px;">
      <h3 class="legend-title"><span style="font-size: 13px;">Escolha o personagem:</span></h3>
      <div style="margin-bottom: 8px; margin-top: 10px;">
        <strong>Personagem:&nbsp;</strong>
        <select name="personagem" class="inputbox" @change="choose">
          <option value="" disabled selected>Selecione aqui</option>
          <option v-for="c in characters" :key="c.name" :value="c.name">{{ c.name }}</option>
        </select>
      </div>
    </div>
    <div class="quadrosOut">
      <template v-if="name">
        <div v-if="account?.online" class="qdestaques2">{{ P.LDPU_YOU_MUST_BE_OFFLINE }}</div>
        <div v-else-if="!char" class="quadros">{{ P.LDPU_THIS_CHARACTER_NOT_EXIST }}</div>
        <slot v-else :char="char" :selected="name" />
      </template>
    </div>
  </div>
</template>
