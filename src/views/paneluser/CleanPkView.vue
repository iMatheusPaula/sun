<script setup lang="ts">
// paneluser[CLEAN_PK].tpl.php + ldPanelUser::loadOptionsCleanPk (CLEAN_MODE 1 = zen fixo, 2 = zen × mortes)
import { ref } from 'vue'
import CharacterOption from '@/components/paneluser/CharacterOption.vue'
import { usePanelAccount } from '@/lib/paneluser'
import { CLEAN_PK } from '@/config/panel'
import type { CharacterRow } from '@/data/db'
import * as P from '@/lang/ldpaneluser'

const { plan } = usePanelAccount()
const result = ref<{ ok: boolean; text: string } | null>(null)

const price = (c: CharacterRow) => (CLEAN_PK.CLEAN_MODE === 2 ? c.pkCount : 1) * CLEAN_PK.PRICEZEN[plan.value]!

function clean(c: CharacterRow) {
  if (c.money < price(c)) return (result.value = { ok: false, text: P.LDPU_CLEAN_NOT_HAVE_ZEN })
  c.money -= price(c)
  Object.assign(c, { pkCount: 0, pkLevel: 0, pkTime: 0 })
  result.value = { ok: true, text: P.LDPU_CLEAN_SUCCESS_TEXT }
}
</script>

<template>
  <CharacterOption title="Limpar PK">
    <template #default="{ char, selected: name }">
      <div v-if="result?.ok" class="qdestaques2">{{ result.text }}</div>
      <strong v-else-if="result" style="color:red">{{ result.text }}</strong>
      <div v-else-if="char.pkCount === 0" class="quadros" v-html="P.LDPU_CLEAN_PK_NOT_PK"></div>
      <div v-else class="quadros">
        <em>{{ P.LDPU_CLEAN_SELECTED_CHARACTER }}:</em> {{ name }}<br />
        <em>{{ P.LDPU_CLEAN_PK_LEVEL }}:</em> {{ char.pkCount }}<br />
        <em>{{ P.LDPU_CLEAN_NEED_ZEN }}:</em> {{ price(char) }}<br />
        <em>{{ P.LDPU_CLEAN_HAVE_ZEN }}:</em> {{ char.money }}<br /><strong v-if="char.money < price(char)" style="color:red">{{ P.LDPU_CLEAN_NOT_HAVE_ZEN }}</strong><input v-else type="button" class="button" :value="P.LDPU_CLEAN_SUBMIT" @click="clean(char)" />
      </div>
    </template>
  </CharacterOption>
</template>
