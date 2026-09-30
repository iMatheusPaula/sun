<script setup lang="ts">
// paneluser[MASTER_RESET].tpl.php + ldPanelUser::loadOptionsMasterReset
import { ref } from 'vue'
import CharacterOption from '@/components/paneluser/CharacterOption.vue'
import { usePanelAccount } from '@/lib/paneluser'
import { isDarkLord, resetClassCode, resetParams, resetSpawn } from '@/lib/reset'
import { MASTER_RESET, RESET_MODE } from '@/config/panel'
import { className } from '@/config/settings'
import { sprintf } from '@/lang/format'
import type { CharacterRow } from '@/data/db'
import * as P from '@/lang/ldpaneluser'

const { account, plan } = usePanelAccount()
const result = ref<{ ok: boolean; html: string } | null>(null)
const req = MASTER_RESET.POINTS_REQUIRE
const after = MASTER_RESET.POINTS_AFTER

function view(c: CharacterRow) {
  const resets = c.resets + 1
  const requireResets = MASTER_RESET.RESETS_REQUIRE[plan.value]!
  const p = resetParams(resets, plan.value, resets - requireResets)
  const dl = isDarkLord(c.class)
  const errors: string[] = []
  if (c.strength < req.Strength) errors.push(sprintf(P.LDPU_MRESET_TEXT_NEED_POINTS_STRENGTH, req.Strength))
  if (c.dexterity < req.Dexterity) errors.push(sprintf(P.LDPU_MRESET_TEXT_NEED_POINTS_DEXTERITY, req.Dexterity))
  if (c.energy < req.Energy) errors.push(sprintf(P.LDPU_MRESET_TEXT_NEED_POINTS_ENERGY, req.Energy))
  if (c.vitality < req.Vitality) errors.push(sprintf(P.LDPU_MRESET_TEXT_NEED_POINTS_VITALITY, req.Vitality))
  if (dl && c.leadership < req.Leadership) errors.push(sprintf(P.LDPU_MRESET_TEXT_NEED_POINTS_LEADERSHIP, req.Leadership))
  if (c.level < p.LevelReset) errors.push(P.LDPU_RESET_NOT_HAVE_LEVEL)
  if (c.money < p.ZenRequire) errors.push(P.LDPU_RESET_NOT_HAVE_ZEN)
  if (resets - 1 < requireResets) errors.push(P.LDPU_MRESET_TEXT_NOT_HAVE_RESETS)
  const newClass = p.CleanQuests ? resetClassCode(c.class) ?? c.class : c.class
  return { resets, requireResets, p, dl, errors, newClass }
}

function masterReset(c: CharacterRow) {
  const { resets, requireResets, p, errors, newClass } = view(c)
  if (errors.length) {
    result.value = { ok: false, html: errors.map((e) => `<strong style='color:#FF0000'>${e}</strong><br />`).join('') }
    return
  }
  Object.assign(c, resetSpawn(c.class), {
    experience: 0, resets: c.resets - requireResets, masterResets: c.masterResets + 1,
    level: p.LevelAfter, money: c.money - p.ZenRequire, class: newClass,
  })
  if (p.ResetPoints) {
    Object.assign(c, { strength: after.Strength, dexterity: after.Dexterity, energy: after.Energy, vitality: after.Vitality, leadership: after.Leadership })
  }
  const points = RESET_MODE === 4 ? p.Points : (resets - requireResets) * p.Points
  c.points = p.ResetPoints ? points : c.points + points
  if (MASTER_RESET.BONUS_ACTIVE) account.value!.cash += MASTER_RESET.BONUS_AMOUNT[plan.value]!
  result.value = { ok: true, html: P.LDPU_MRESET_TEXT_SUCCESS }
}
</script>

<template>
  <CharacterOption title="Master Reset">
    <template #default="{ char, selected: name }">
      <div v-if="result" :class="result.ok ? 'qdestaques2' : 'qdestaques'" v-html="result.html"></div>
      <div v-else class="quadros">
        <em>{{ P.LDPU_RESET_TEXT_CHARACTER_SELECTED }}:</em> <strong>{{ name }}</strong><br /><br />
        <strong><em>{{ P.LDPU_RESET_TEXT_YOU_NEED_TO_RESET }}:</em></strong><br />
        <em>{{ P.LDPU_RESET_TEXT_LEVEL }}:</em> <strong>{{ view(char).p.LevelReset }}</strong><br />
        <em>{{ P.LDPU_RESET_TEXT_ZEN }}:</em> <strong>{{ view(char).p.ZenRequire }}</strong><br />
        <em>{{ P.LDPU_RESET_TEXT_RESETS }}</em>: <strong>{{ view(char).requireResets }}</strong><br />
        <em>{{ P.LDPU_MRESET_TEXT_POINTS_STRENGTH }}</em>: <strong>{{ req.Strength }}</strong><br />
        <em>{{ P.LDPU_MRESET_TEXT_POINTS_DEXTERITY }}</em>: <strong>{{ req.Dexterity }}</strong><br />
        <em>{{ P.LDPU_MRESET_TEXT_POINTS_ENERGY }}</em>: <strong>{{ req.Energy }}</strong><br />
        <em>{{ P.LDPU_MRESET_TEXT_POINTS_VITALITY }}</em>: <strong>{{ req.Vitality }}</strong><br />
        <template v-if="view(char).dl"><em>{{ P.LDPU_MRESET_TEXT_POINTS_LEADERSHIP }}</em>: <strong>{{ req.Leadership }}</strong><br /></template>
        <br />
        <strong><em>{{ P.LDPU_RESET_TEXT_STATUS_BEFORE_RESET }}:</em></strong><br />
        <em>{{ P.LDPU_RESET_TEXT_RESETS }}:</em> <strong>{{ view(char).resets - 1 }}</strong><br />
        <em>{{ P.LDPU_MRESET_TEXT_POINTS_STRENGTH }}</em>: <strong>{{ char.strength }}</strong><br />
        <em>{{ P.LDPU_MRESET_TEXT_POINTS_DEXTERITY }}</em>: <strong>{{ char.dexterity }}</strong><br />
        <em>{{ P.LDPU_MRESET_TEXT_POINTS_ENERGY }}</em>: <strong>{{ char.energy }}</strong><br />
        <em>{{ P.LDPU_MRESET_TEXT_POINTS_VITALITY }}</em>: <strong>{{ char.vitality }}</strong><br />
        <template v-if="view(char).dl"><em>{{ P.LDPU_MRESET_TEXT_POINTS_LEADERSHIP }}</em>: <strong>{{ char.leadership }}</strong><br /></template>
        <em>{{ P.LDPU_MRESET_TEXT_MASTER_RESETS }}:</em> <strong>{{ char.masterResets }}</strong><br />
        <em>{{ P.LDPU_RESET_TEXT_LEVEL }}:</em> <strong>{{ char.level }}</strong><br />
        <em>{{ P.LDPU_RESET_TEXT_ZEN }}:</em> <strong>{{ char.money }}</strong><br />
        <em>{{ P.LDPU_RESET_TEXT_CLASS }}:</em> <strong>{{ className(char.class) }}</strong><br /><br />
        <strong><em>{{ P.LDPU_RESET_TEXT_STATUS_AFTER_RESET }}:</em></strong><br />
        <em>{{ P.LDPU_RESET_TEXT_RESETS }}:</em> <strong>{{ view(char).resets - 1 - view(char).requireResets }}</strong><br />
        <em>{{ P.LDPU_MRESET_TEXT_POINTS_STRENGTH }}</em>: <strong>{{ after.Strength }}</strong><br />
        <em>{{ P.LDPU_MRESET_TEXT_POINTS_DEXTERITY }}</em>: <strong>{{ after.Dexterity }}</strong><br />
        <em>{{ P.LDPU_MRESET_TEXT_POINTS_ENERGY }}</em>: <strong>{{ after.Energy }}</strong><br />
        <em>{{ P.LDPU_MRESET_TEXT_POINTS_VITALITY }}</em>: <strong>{{ after.Vitality }}</strong><br />
        <template v-if="view(char).dl"><em>{{ P.LDPU_MRESET_TEXT_POINTS_LEADERSHIP }}</em>: <strong>{{ after.Leadership }}</strong><br /></template>
        <em>{{ P.LDPU_MRESET_TEXT_MASTER_RESETS }}:</em> <strong>{{ char.masterResets + 1 }}</strong><br />
        <em>{{ P.LDPU_RESET_TEXT_LEVEL }}:</em> <strong>{{ view(char).p.LevelAfter }}</strong><br />
        <em>{{ P.LDPU_RESET_TEXT_ZEN }}:</em> <strong>{{ Math.trunc(char.money - view(char).p.ZenRequire) }}</strong><br />
        <em>{{ P.LDPU_RESET_TEXT_CLASS }}:</em> <strong>{{ className(view(char).newClass) }}</strong><br />
        <em>{{ P.LDPU_RESET_TEXT_MAGICS }}:</em> <strong v-if="view(char).p.CleanMagics" style="color:#FF0000">{{ P.LDPU_RESET_TEXT_MAGICS_ERASE }}</strong><strong v-else>{{ P.LDPU_RESET_TEXT_MAGICS_NOT_ERASE }}</strong><br />
        <em>{{ P.LDPU_RESET_TEXT_ITEMS }}:</em> <strong v-if="view(char).p.CleanItens" style="color:#FF0000">{{ P.LDPU_RESET_TEXT_ITEMS_ERASE }}</strong><strong v-else>{{ P.LDPU_RESET_TEXT_ITEMS_NOT_ERASE }}</strong><br />
        <em>{{ P.LDPU_RESET_TEXT_QUESTS }}:</em> <strong v-if="view(char).p.CleanQuests" style="color:#FF0000">{{ P.LDPU_RESET_TEXT_QUESTS_ERASE }}</strong><strong v-else>{{ P.LDPU_RESET_TEXT_QUESTS_NOT_ERASE }}</strong><br /><br />
        <template v-if="view(char).errors.length"><template v-for="e in view(char).errors" :key="e"><strong style="color:#FF0000">{{ e }}</strong><br /></template></template>
        <input v-else type="button" class="button" :value="P.LDPU_MRESET_TEXT_SUBMIT" @click="masterReset(char)" />
      </div>
    </template>
  </CharacterOption>
</template>
