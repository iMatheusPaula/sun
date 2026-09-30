<script setup lang="ts">
// paneluser[OPEN_COMPLAINTS].tpl.php + ldPanelUser::loadOptionsOpenComplaints: denúncia com foto (JPG/PNG)
import { ref } from 'vue'
import { usePanelAccount } from '@/lib/paneluser'
import * as P from '@/lang/ldpaneluser'

const { store, login } = usePanelAccount()
const descricao = ref('')
const fileInput = ref<HTMLInputElement | null>(null)
const response = ref<{ ok: boolean; html: string } | null>(null)

function send() {
  if (descricao.value === '') return // o original ignora o envio sem descrição
  const image = fileInput.value?.files?.[0]
  if (!image || !['image/jpeg', 'image/pjpeg', 'image/png'].includes(image.type)) {
    response.value = { ok: false, html: P.LDPU_COMPLAINT_INVALID_FILE }
    return
  }
  const reader = new FileReader()
  reader.onload = () => {
    const id = Math.max(0, ...store.db.complaints.map((c) => c.id)) + 1
    store.db.complaints.push({
      id, account: login.value, image: String(reader.result), description: descricao.value, date: Date.now(), ip: '127.0.0.1', status: 0,
    })
    response.value = { ok: true, html: P.LDPU_COMPLAINT_SUCCESS }
  }
  reader.readAsDataURL(image)
}
</script>

<template>
  <h1 style="margin-top: 20px;">Abrir denuncias</h1>
  <br />

  <div class="legend" style="margin-bottom: 25px;">
    <h3 class="legend-title"><span style="font-size: 13px;">Digite os dados abaixo:</span></h3>
    <div style="margin-bottom: 8px; margin-top: 10px;">
      <form action="" method="post" enctype="multipart/form-data" @submit.prevent="send">
        <table border="0" cellpadding="0" cellspacing="2">
          <tbody>
            <tr>
              <td style="background: none;"><strong>Foto para denuncia:</strong></td>
              <td style="background: none;"><input ref="fileInput" name="image" type="file" size="16" />
                (.jpg / .gif / .png)</td>
            </tr>
            <tr>
              <td style="background: none;"><strong>Descrição:</strong></td>
              <td style="background: none;"><textarea v-model="descricao" name="descricao" cols="40" rows="4"></textarea></td>
            </tr>
            <tr>
              <td align="center" colspan="2" style="background: none;"><input type="submit" class="button" value="Enviar Denuncia" /></td>
            </tr>
          </tbody>
        </table>
      </form>

      <div id="classe_change" style="margin-top: 16px; margin-bottom: 16px; padding-left: 5px;"><div v-if="response" :class="response.ok ? 'qdestaques2' : 'qdestaques'" v-html="response.html"></div></div>
    </div>
  </div>
</template>
