<script setup lang="ts">
// paneluser[MANAGER_PHOTO].tpl.php + ldPanelUser::loadOptionsManagerPhoto. Sem servidor, a foto enviada fica
// no banco do front como data URL (a mesma validação de tamanho e tipo do original).
import { ref } from 'vue'
import CharacterOption from '@/components/paneluser/CharacterOption.vue'
import { NO_PHOTO } from '@/data/placeholders'
import type { CharacterRow } from '@/data/db'
import * as P from '@/lang/ldpaneluser'

const file = ref<File | null>(null)
const result = ref<{ ok: boolean; html: string } | null>(null)

function pick(e: Event) {
  file.value = (e.target as HTMLInputElement).files?.[0] ?? null
}

function upload(c: CharacterRow) {
  const f = file.value
  if (!f) return (result.value = { ok: true, html: P.LDPU_MPHOTO_CHOOSE_PHOTO })
  if (f.size > 500000) return (result.value = { ok: false, html: P.LDPU_MPHOTO_MAX_SIZE })
  if (!/^image\/(pjpeg|jpeg|png|gif|bmp)$/i.test(f.type)) return (result.value = { ok: false, html: P.LDPU_MPHOTO_INVALID_TYPE })
  const reader = new FileReader()
  reader.onload = () => {
    c.image = String(reader.result)
    result.value = { ok: true, html: P.LDPU_MPHOTO_SUCCESS }
  }
  reader.onerror = () => (result.value = { ok: false, html: `${P.LDPU_MPHOTO_UPLOAD_ERROR} 1` })
  reader.readAsDataURL(f)
}
</script>

<template>
  <CharacterOption title="Gerenciar foto">
    <template #default="{ char, selected: name }">
      <div v-if="result" :class="result.ok ? 'qdestaques2' : 'qdestaques'" v-html="result.html"></div>
      <div v-else class="quadros">
        <form action="" method="post" enctype="multipart/form-data" @submit.prevent="upload(char)">
          <table width="100%">
            <tbody>
              <tr>
                <td colspan="2" align="center"><em>{{ P.LDPU_MPHOTO_CHARACTER }}:</em> <strong>{{ name }}</strong></td>
              </tr>
              <tr>
                <td width="34%" align="center">{{ P.LDPU_MPHOTO_CURRENT_PHOTO }}: <br />
                  <img :src="char.image || NO_PHOTO" alt="" width="100" height="100" style="border: 2px solid #222222;" /></td>
                <td width="66%" align="left">
                  {{ P.LDPU_MPHOTO_REFRESH_PHOTO }}: <br />
                  <input type="file" name="photo" @change="pick" /><br />
                  <input type="submit" class="button" :value="P.LDPU_MPHOTO_SUBMIT" />
                </td>
              </tr>
            </tbody>
          </table>
        </form>
      </div>
    </template>
  </CharacterOption>
</template>
