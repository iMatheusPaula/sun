<script setup lang="ts">
// Comentários de uma notícia: ajax noticeselectcomments (lista), noticecomment (postar), noticedeletecomment (apagar).
import { computed, ref, watch } from 'vue'
import { useNoticesStore } from '@/stores/notices'
import { useSessionStore } from '@/stores/session'
import { useAccountsStore } from '@/stores/accounts'
import { useDbStore } from '@/stores/db'
import { findCharacter } from '@/lib/rankings'
import { characterPhoto, NO_PHOTO } from '@/data/placeholders'
import { phpDate } from '@/lib/date'
import * as A from '@/lang/ldajax'

const props = defineProps<{ noticeId: number }>()
const notices = useNoticesStore()
const session = useSessionStore()
const accounts = useAccountsStore()
const store = useDbStore()

const valid = computed(() => !!notices.find(props.noticeId))
const list = computed(() => notices.commentsOf(props.noticeId))
const isAdmin = computed(() => session.privilege >= 2)

// [Excluir] troca o conteúdo do quadro pela resposta, até a lista ser recarregada
const deleted = ref(new Map<number, { ok: boolean; message: string }>())
const shown = computed(() => [
  ...list.value.map((c) => ({ comment: c, result: undefined })),
  ...[...deleted.value.entries()].map(([id, result]) => ({ comment: { id }, result })),
].sort((a, b) => b.comment.id - a.comment.id))

const text = ref('')
const response = ref<{ ok: boolean; message: string } | null>(null)
watch(() => props.noticeId, () => {
  deleted.value = new Map()
  response.value = null
})

// quem assina: o último personagem usado pela conta (AccountCharacter.GameIDC), senão o login
function author(account: string) {
  const name = accounts.find(account)?.lastCharacter
  return name ? findCharacter(store.db, name) : undefined
}

function post() {
  response.value = notices.post(props.noticeId, text.value)
  if (response.value.ok) deleted.value = new Map() // loadComments()
}

function remove(id: number) {
  const result = notices.remove(id)
  deleted.value = new Map(deleted.value).set(id, result)
}
</script>

<template>
  <div id="commentSelectReturn">
    <div v-if="!valid" class="qdestaques">{{ A.NOTICES_COMMENTS_INVALID_NOTICE }}</div>
    <template v-else>
      <div v-for="({ comment: c, result }, i) in shown" :id="`divComent_${i + 1}`" :key="c.id" class="quadros">
        <div v-if="result" :class="result.ok ? 'qdestaques2' : 'qdestaques'">{{ result.message }}</div>
        <table v-else-if="'account' in c" style="width: 100%;">
          <tbody>
            <tr>
              <td align="center" style="padding-right: 20px; width: 120px">
                <RouterLink v-if="author(c.account)" :to="{ path: '/rankings-personagem', query: { name: author(c.account)!.name } }">
                  <strong>{{ author(c.account)!.name }}</strong><br />
                  <img :src="characterPhoto(author(c.account)!.image)" style="border: none; width:100px; height:100px;" /><br />{{ author(c.account)!.resets }} resets
                </RouterLink>
                <template v-else>
                  <strong>{{ c.account }}</strong><br /><img :src="NO_PHOTO" style="border: none; width:100px; height:100px;" />
                </template>
              </td>
              <td valign="top"><strong><em>{{ A.NOTICES_COMMENTS_COMMENT }}</em></strong> <br />
                <template v-for="(line, l) in c.comment.split('\n')" :key="l"><br v-if="l > 0" />{{ line }}</template>
              </td>
            </tr>
            <tr>
              <td colspan="2" align="right">
                {{ A.NOTICES_COMMENTS_DATE }} {{ phpDate('d/m/Y G:i:s', c.date) }}<template v-if="isAdmin">, IP: {{ c.ip }}, <a href="javascript: void(0);" @click.prevent="remove(c.id)">{{ A.NOTICES_COMMENTS_ERASE }}</a></template>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>
  </div>
  <div class="quadros" style="color: black;">
    Deixe seu comentário sobre essa notícia:<br />
    <textarea id="comment" v-model="text" style="width: 100%; height: 80px;"></textarea>
    <input type="button" value="Postar comentário" class="button" @click="post" />
    <br /><div id="commentReturn"><div v-if="response" :class="response.ok ? 'qdestaques2' : 'qdestaques'" v-html="response.message"></div></div>
  </div>
</template>
