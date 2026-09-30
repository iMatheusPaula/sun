<script setup lang="ts">
// paneluser[CHANGE_NICK].tpl.php + ldPanelUser::loadOptionsChangeNick
import { ref } from 'vue'
import CharacterOption from '@/components/paneluser/CharacterOption.vue'
import { usePanelAccount } from '@/lib/paneluser'
import type { CharacterRow } from '@/data/db'
import * as P from '@/lang/ldpaneluser'

const { store, account } = usePanelAccount()
const newnick = ref<string | null>(null)
const result = ref<{ cls: string; html: string } | null>(null)

// em guild: as guilds do painel ainda não existem no banco do front — nenhum personagem da conta está em uma
const inGuild = (_name: string) => false

function change(c: CharacterRow) {
  const nick = newnick.value ?? c.name
  const red = (m: string, cls = 'qdestaques') => (result.value = { cls, html: `<strong style='color:#BB0000'>${m}</strong>` })
  if (inGuild(c.name)) return red(P.LDPU_CHANGE_NICK_NOT_HAVE_GUILD)
  if (nick === '' || /[^a-zA-Z0-9_!=?&-]/.test(nick)) return red(P.LDPU_CHANGE_NICK_INVALID_CHARS)
  if (nick.length > 10) return red(P.LDPU_CHANGE_NICK_INVALID_NAME_SIZE)
  if (nick === c.name) return red(P.LDPU_CHANGE_NICK_FILL_NAME)
  if (store.db.characters.some((ch) => ch.name.toLowerCase() === nick.toLowerCase())) return red(P.LDPU_CHANGE_NICK_ALREADY_EXISTS_THIS_NICK)
  if (c.ctlCode === 1) return red(P.LDPU_CHANGE_NICK_CHARACTER_BANNED)
  if (/WEBZEN|ADM|GM|MD|NT|DV/i.test(nick)) return red(P.LDPU_CHANGE_NICK_INVALID_SIGLES, 'qdestaques2')
  if (account.value!.lastCharacter === c.name) account.value!.lastCharacter = nick
  c.name = nick
  result.value = { cls: 'qdestaques2', html: P.LDPU_CHANGE_NICK_SUCCESS }
}
</script>

<template>
  <CharacterOption title="Mudar Nick">
    <template #default="{ char, selected: name }">
      <div v-if="result" :class="result.cls" v-html="result.html"></div>
      <div v-else class="quadros">
        <form action="" method="post" @submit.prevent="change(char)">
          <table width="100%" border="0">
            <tbody>
              <tr>
                <td align="center" colspan="2"><em>{{ P.LDPU_CHANGE_NICK_CHARACTER }}:</em> <strong>{{ name }}</strong></td>
              </tr>
              <tr>
                <td align="right" width="40%"><strong>{{ P.LDPU_CHANGE_NICK_NEW_NICK }}: </strong></td>
                <td><input id="newnick" :value="newnick ?? name" name="newnick" maxlength="10" size="15" type="text" class="" @input="newnick = ($event.target as HTMLInputElement).value" /></td>
              </tr>
              <tr>
                <td align="center" colspan="2"><input type="submit" class="button" :value="P.LDPU_CHANGE_NICK_SUBMIT" /></td>
              </tr>
            </tbody>
          </table>
        </form>
      </div>
    </template>
  </CharacterOption>
</template>
