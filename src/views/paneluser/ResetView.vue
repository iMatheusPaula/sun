<script setup lang="ts">
// paneluser[RESET].tpl.php + ldPanelUser::loadOptionsReset
import { ref } from 'vue'
import CharacterOption from '@/components/paneluser/CharacterOption.vue'
import { usePanelAccount } from '@/lib/paneluser'
import { resetClassCode, resetParams, resetSpawn } from '@/lib/reset'
import { RESET_MODE } from '@/config/panel'
import { className } from '@/config/settings'
import type { CharacterRow } from '@/data/db'
import * as P from '@/lang/ldpaneluser'

const { plan } = usePanelAccount()
// depois de "Resetar", a tela mostra só a resposta (o original navegava para &reset=true)
const result = ref<{ ok: boolean; html: string } | null>(null)

function view(c: CharacterRow) {
  const resets = c.resets + 1
  const p = resetParams(resets, plan.value)
  const errors: string[] = []
  if (c.level < p.LevelReset) errors.push(P.LDPU_RESET_NOT_HAVE_LEVEL)
  if (c.money < p.ZenRequire) errors.push(P.LDPU_RESET_NOT_HAVE_ZEN)
  if (p.LimitResets !== 0 && resets >= p.LimitResets) errors.push(P.LDPU_RESET_LIMIT_RESETS)
  const newClass = p.CleanQuests ? resetClassCode(c.class) ?? c.class : c.class
  return { resets, p, errors, newClass }
}

function reset(c: CharacterRow) {
  const { resets, p, errors, newClass } = view(c)
  if (errors.length) {
    result.value = { ok: false, html: errors.map((e) => `<strong style='color:#FF0000'>${e}</strong><br />`).join('') }
    return
  }
  Object.assign(c, resetSpawn(c.class), {
    experience: 0, resets: c.resets + 1, resetsWeek: c.resetsWeek + 1, resetsMonth: c.resetsMonth + 1,
    level: p.LevelAfter, money: c.money - p.ZenRequire, class: newClass,
  })
  if (p.ResetPoints) Object.assign(c, { strength: 30, dexterity: 30, energy: 30, vitality: 30, leadership: 30 })
  const points = RESET_MODE === 4 ? p.Points : resets * p.Points
  c.points = p.ResetPoints ? points : c.points + points
  result.value = { ok: true, html: P.LDPU_RESET_TEXT_SUCCESS }
}
</script>

<template>
  <CharacterOption title="Resetar Personagem">
    <template #default="{ char, selected: name }">
      <div v-if="result" :class="result.ok ? 'qdestaques2' : 'qdestaques'" v-html="result.html"></div>
      <div v-else class="quadros">
        <em>{{ P.LDPU_RESET_TEXT_CHARACTER_SELECTED }}:</em> <strong>{{ name }}</strong><br />
        <strong><em>{{ P.LDPU_RESET_TEXT_YOU_NEED_TO_RESET }}:</em></strong><br />
        <em>{{ P.LDPU_RESET_TEXT_LEVEL }}:</em> <strong>{{ view(char).p.LevelReset }}</strong><br />
        <em>{{ P.LDPU_RESET_TEXT_ZEN }}:</em> <strong>{{ view(char).p.ZenRequire }}</strong><br /><br />
        <strong><em>{{ P.LDPU_RESET_TEXT_STATUS_BEFORE_RESET }}:</em></strong><br />
        <em>{{ P.LDPU_RESET_TEXT_RESETS }}:</em> <strong>{{ view(char).resets - 1 }}</strong><br />
        <em>{{ P.LDPU_RESET_TEXT_LEVEL }}:</em> <strong>{{ char.level }}</strong><br />
        <em>{{ P.LDPU_RESET_TEXT_ZEN }}:</em> <strong>{{ char.money }}</strong><br />
        <em>{{ P.LDPU_RESET_TEXT_CLASS }}:</em> <strong>{{ className(char.class) }}</strong><br /><br />
        <strong><em>{{ P.LDPU_RESET_TEXT_STATUS_AFTER_RESET }}:</em></strong><br />
        <em>{{ P.LDPU_RESET_TEXT_RESETS }}:</em> <strong>{{ view(char).resets }}</strong><br />
        <em>{{ P.LDPU_RESET_TEXT_LEVEL }}:</em> <strong>{{ view(char).p.LevelAfter }}</strong><br />
        <em>{{ P.LDPU_RESET_TEXT_ZEN }}:</em> <strong>{{ Math.trunc(char.money - view(char).p.ZenRequire) }}</strong><br />
        <em>{{ P.LDPU_RESET_TEXT_CLASS }}:</em> <strong>{{ className(view(char).newClass) }}</strong><br />
        <em>{{ P.LDPU_RESET_TEXT_MAGICS }}:</em> <strong v-if="view(char).p.CleanMagics" style="color:#FF0000">{{ P.LDPU_RESET_TEXT_MAGICS_ERASE }}</strong><strong v-else>{{ P.LDPU_RESET_TEXT_MAGICS_NOT_ERASE }}</strong><br />
        <em>{{ P.LDPU_RESET_TEXT_ITEMS }}:</em> <strong v-if="view(char).p.CleanItens" style="color:#FF0000">{{ P.LDPU_RESET_TEXT_ITEMS_ERASE }}</strong><strong v-else>{{ P.LDPU_RESET_TEXT_ITEMS_NOT_ERASE }}</strong><br />
        <em>{{ P.LDPU_RESET_TEXT_QUESTS }}:</em> <strong v-if="view(char).p.CleanQuests" style="color:#FF0000">{{ P.LDPU_RESET_TEXT_QUESTS_ERASE }}</strong><strong v-else>{{ P.LDPU_RESET_TEXT_QUESTS_NOT_ERASE }}</strong><br /><br />
        <template v-if="view(char).errors.length"><template v-for="e in view(char).errors" :key="e"><strong style="color:#FF0000">{{ e }}</strong><br /></template></template>
        <input v-else type="button" class="button" :value="P.LDPU_RESET_TEXT_SUBMIT" @click="reset(char)" />
      </div>
    </template>
  </CharacterOption>
</template>
