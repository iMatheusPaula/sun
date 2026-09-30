<script setup lang="ts">
// paneluser[SCREENSHOT].tpl.php + módulo screenshots (screenshots.module.php, action=panelManager): enviar
// (a foto vira uma versão "common" de até MAX_WIDTH de largura e uma miniatura 100x100) e apagar as próprias
// screenshots — o administrador vê e apaga as de todos.
import { computed, ref } from 'vue'
import { usePanelAccount } from '@/lib/paneluser'
import { useSessionStore } from '@/stores/session'
import { loadImage, resizeImage, toJpeg, validImageName } from '@/lib/images'
import { SCREENSHOTS } from '@/config/settings'

const { store, login } = usePanelAccount()
const session = useSessionStore()
const isAdmin = computed(() => session.privilege === 2)
const response = ref('')
const legend = ref('')
const photo = ref<HTMLInputElement>()

const amount = computed(() => store.db.screenshots.filter((s) => s.account === login.value).length)
const deletable = computed(() => store.db.screenshots.filter((s) => isAdmin.value || s.account === login.value).sort((a, b) => a.id - b.id))
const deleteId = ref(String(deletable.value[0]?.id ?? ''))

const red = (m: string) => `<div class="qdestaques">${m}</div>`

// subAction=upload
async function upload(e: Event) {
  const form = e.target as HTMLFormElement
  const file = photo.value?.files?.[0]
  const text = legend.value
  form.reset() // o POST recarregava a página com o formulário vazio
  legend.value = ''
  if (!file) return (response.value = red('Selecione o arquivo para enviar.'))
  if (text === '' || text === '0') return (response.value = red('Escreva uma legenda para a screenshot.')) // empty()
  const img = validImageName(file.name) ? await loadImage(file) : null
  if (!img) return (response.value = red('Arquivo inválido.'))

  const common = img.width > SCREENSHOTS.MAX_WIDTH ? resizeImage(img, SCREENSHOTS.MAX_WIDTH) : resizeImage(img, img.width, img.height)
  const small = resizeImage(common, 100, 100)
  store.db.screenshots.push({
    id: Math.max(0, ...store.db.screenshots.map((s) => s.id)) + 1,
    image: '', account: login.value, date: Date.now(), rate: 0, legend: text,
    files: { small: toJpeg(small), common: toJpeg(common), sw: small.width, sy: small.height, cw: common.width, cy: common.height },
  })
  resetDelete()
  response.value = '<div class="qdestaques2">Screenshot enviada com sucesso.</div>'
}

// subAction=delete
function remove() {
  if (deleteId.value === '') {
    response.value = red('Sem fotos para deletar.')
    return
  }
  const id = Number(deleteId.value)
  const i = store.db.screenshots.findIndex((s) => s.id === id && (isAdmin.value || s.account === login.value))
  if (i >= 0) store.db.screenshots.splice(i, 1)
  resetDelete()
  response.value = red('Screenshot deletada com sucesso.')
}

function resetDelete() {
  deleteId.value = String(deletable.value[0]?.id ?? '')
}
</script>

<template>
  <h1 style="margin-top: 20px;">Screenshots</h1>
  <br />

  <div id="etc" style="margin-bottom: 26px;" v-html="response"></div>

  <div class="legend" style="margin-bottom: 25px;">
    <h3 class="legend-title"><span style="font-size: 13px;">Enviar novas screenshots</span></h3>
    <div style="margin-bottom: 8px; margin-top: 10px;">
      <form action="" method="post" enctype="multipart/form-data" @submit.prevent="upload">
        <table width="100%">
          <tbody>
            <tr>
              <td>
                <strong>Quantidade de screenshots que já enviei:</strong> <strong>{{ amount }}</strong>
              </td>
            </tr>
            <tr>
              <td>
                <strong>Enviar nova screenshot:</strong> <em>Somente são aceitos os formatos: .jpg, .png, .gif, .bmp</em> <br />
                Legenda da screenshot: <input v-model="legend" type="text" name="legend" maxlength="50" /><br />
                Selecione a foto: <input ref="photo" type="file" name="photo" />
                <input type="submit" class="button" value="Enviar" />
              </td>
            </tr>
          </tbody>
        </table>
      </form>
    </div>
  </div>
  <div class="legend" style="margin-bottom: 25px;">
    <h3 class="legend-title"><span style="font-size: 13px;">Apagar screenshots</span></h3>
    <div style="margin-bottom: 8px; margin-top: 10px;">
      <form action="" method="post" @submit.prevent="remove">
        <table width="100%">
          <tbody>
            <tr>
              <td>
                <strong>Selecione abaixo a screenshot a ser apagada:</strong><br /><select v-model="deleteId" name="id"><option v-for="s in deletable" :key="s.id" :value="String(s.id)">{{ s.legend }} - Votos: {{ s.rate }}</option></select><input type="submit" class="button" value="Deletar" />
              </td>
            </tr>
          </tbody>
        </table>
      </form>
    </div>
  </div>
</template>
