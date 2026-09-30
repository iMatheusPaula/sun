<script setup lang="ts">
// paneluser[CLEAN_INVENTORY].tpl.php + ldPanelUser::loadOptionsCleanInventory. Inventário e magias não têm
// campo no banco do front; o zen do personagem é zerado de fato.
import { reactive, ref } from 'vue'
import CharacterOption from '@/components/paneluser/CharacterOption.vue'
import { usePanelAccount } from '@/lib/paneluser'
import { sprintf } from '@/lang/format'
import type { CharacterRow } from '@/data/db'
import * as P from '@/lang/ldpaneluser'

const { checkPassword } = usePanelAccount()
const form = reactive({ clear_itens: '0', clear_magics: '0', clear_zen: '0', password: '' })
const results = ref<{ ok: boolean; text: string }[] | null>(null)

function clean(c: CharacterRow) {
  if (!checkPassword(form.password)) return (results.value = [{ ok: false, text: P.LDPU_CLEAN_INVENTORY_INVALID_PASSWORD }])
  if (form.clear_itens === '0' && form.clear_magics === '0' && form.clear_zen === '0') return (results.value = [{ ok: false, text: P.LDPU_CLEAN_INVENTORY_SELECT_THING }])
  const out: { ok: boolean; text: string }[] = []
  if (form.clear_itens === '1') out.push({ ok: true, text: sprintf(P.LDPU_CLEAN_INVENTORY_SUCCESS_ITEM_CLEAR, c.name) })
  if (form.clear_magics === '1') out.push({ ok: true, text: sprintf(P.LDPU_CLEAN_INVENTORY_SUCCESS_MAGICS_CLEAR, c.name) })
  if (form.clear_zen === '1') {
    c.money = 0
    out.push({ ok: true, text: sprintf(P.LDPU_CLEAN_INVENTORY_SUCCESS_ZEN_CLEAR, c.name) })
  }
  results.value = out
}
</script>

<template>
  <CharacterOption title="Limpar Inventário">
    <template #default="{ char, selected: name }">
      <template v-if="results">
        <div v-for="(r, i) in results" :key="i" :class="r.ok ? 'qdestaques2' : 'qdestaques'"><strong :style="r.ok ? 'color:#008040' : 'color:#BB0000'">{{ r.text }}</strong></div>
      </template>
      <div v-else class="quadros">
        <form action="" method="post" @submit.prevent="clean(char)">
          <table width="100%" border="0">
            <tbody>
              <tr>
                <td align="center" colspan="2"><em>{{ P.LDPU_CLEAN_INVENTORY_CHARACTER }}:</em> <strong>{{ name }}</strong></td>
              </tr>
              <tr>
                <td align="right" width="50%"><strong>{{ P.LDPU_CLEAN_INVENTORY_ERASE_ITEMS }}:</strong></td>
                <td><select v-model="form.clear_itens" name="clear_itens"><option value="0">{{ P.LDPU_NOT }}</option><option value="1">{{ P.LDPU_YES }}</option></select></td>
              </tr>
              <tr>
                <td align="right"><strong>{{ P.LDPU_CLEAN_INVENTORY_ERASE_MAGICS }}:</strong></td>
                <td><select v-model="form.clear_magics" name="clear_magics"><option value="0">{{ P.LDPU_NOT }}</option><option value="1">{{ P.LDPU_YES }}</option></select></td>
              </tr>
              <tr>
                <td align="right"><strong>{{ P.LDPU_CLEAN_INVENTORY_ERASE_ZEN }}:</strong></td>
                <td><select v-model="form.clear_zen" name="clear_zen"><option value="0">{{ P.LDPU_NOT }}</option><option value="1">{{ P.LDPU_YES }}</option></select></td>
              </tr>
              <tr>
                <td align="right"><strong>{{ P.LDPU_CLEAN_INVENTORY_PASSWORD }}:</strong></td>
                <td><input v-model="form.password" name="password" type="password" size="10" /></td>
              </tr>
              <tr>
                <td align="center" colspan="2"><input type="submit" class="button" :value="P.LDPU_CLEAN_INVENTORY_SUBMIT" /></td>
              </tr>
            </tbody>
          </table>
        </form>
      </div>
    </template>
  </CharacterOption>
</template>
