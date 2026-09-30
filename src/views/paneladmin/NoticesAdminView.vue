<script setup lang="ts">
// paneladmin[ADD_NOTICE|REMOVE_NOTICE|MODIFY_NOTICE].tpl.php + ldPanelAdmin::optionLoadAddNotice / RemoveNotice /
// ModifyNotice: as notícias do site (HTML, como o original aceitava). Alterar grava a data de agora, então a notícia
// alterada passa a ser a mais nova. O original tirava "<?" e "?>" do conteúdo.
import { computed, reactive, ref } from 'vue'
import { useDbStore } from '@/stores/db'
import { isEmpty } from '@/lib/paneladmin'
import * as PA from '@/lang/ldpaneladmin'

const props = defineProps<{ mode: 'add' | 'remove' | 'modify' }>()
const store = useDbStore()
const response = ref('')
const clean = (html: string) => html.replace(/<\?/g, '').replace(/\?>/g, '')
const options = computed(() => [...store.db.notices].sort((a, b) => b.id - a.id))
const firstId = () => String(options.value[0]?.id ?? '')

// Adicionar
const add = reactive({ subject: '', content: 'Aceita codigos HTML.' })
function insert() {
  const { subject, content } = add
  Object.assign(add, { subject: '', content: 'Aceita codigos HTML.' }) // o POST recarregava a página
  if (isEmpty(subject) || isEmpty(content)) return (response.value = `<div class='qdestaques'>${PA.LDPA_FILL_ALL_INPUTS}</div>`)
  const id = Math.max(0, ...store.db.notices.map((n) => n.id)) + 1
  store.db.notices.push({ id, subject, content: clean(content), date: Date.now() })
  response.value = `<div class='qdestaques2'>${PA.LDPA_NOTICE_ADD_TEXT_SUCCESS}</div>`
}

// Remover / Alterar: a notícia escolhida no select
const selected = ref(firstId())
function remove() {
  const id = parseInt(selected.value, 10) || 0
  store.db.notices = store.db.notices.filter((n) => n.id !== id)
  selected.value = firstId()
  response.value = `<div class='qdestaques2'>${PA.LDPA_NOTICE_REMOVE_TEXT_SUCCESS}</div>`
}

const editing = ref<{ id: number; subject: string; content: string } | null>(null)
function load() {
  response.value = ''
  const n = store.db.notices.find((x) => x.id === (parseInt(selected.value, 10) || 0))
  selected.value = firstId()
  // o original montava o formulário mesmo sem achar a notícia (campos vazios)
  editing.value = { id: n?.id ?? 0, subject: n?.subject ?? '', content: n?.content ?? '' }
}
function modify() {
  const e = editing.value!
  editing.value = null
  const n = store.db.notices.find((x) => x.id === e.id)
  if (n) Object.assign(n, { subject: e.subject, content: clean(e.content), date: Date.now() })
  response.value = `<div class='qdestaques2'>${PA.LDPA_NOTICE_ALTER_TEXT_SUCCESS}</div>`
}
</script>

<template>
  <template v-if="props.mode === 'add'">
    <h1 style="margin-top: 20px;">Adcionar notícia</h1>

    <div class="legend" style="margin-top: 25px; padding: 10px;">
      <h3 class="legend-title"><span style="font-size: 13px;">Preencha os dados abaixo:</span></h3>
      <form action="" method="post" name="noticeFrom" @submit.prevent="insert">
        <em>Titulo:</em><br /><input v-model="add.subject" name="subject" type="text" class="inputbox" maxlength="50" /> <br />
        <em>Notícia:</em><br /><textarea id="content" v-model="add.content" name="content" cols="80" rows="6"></textarea><br />
        <input type="submit" value="Adicionar." class="button" style="margin-top:10px;" />
      </form>
      <div class="quadrosOut" v-html="response"></div>
    </div>
  </template>

  <template v-else>
    <h1 style="margin-top: 20px;">{{ mode === 'remove' ? 'Remover notícia' : 'Modificar notícia' }}</h1>

    <div class="legend" style="margin-top: 25px; padding: 10px;">
      <h3 class="legend-title"><span style="font-size: 13px;">Preencha os dados abaixo:</span></h3>
      <form action="" method="post" @submit.prevent="mode === 'remove' ? remove() : load()">
        <em>Titulo:</em><br /><select v-model="selected" name="id" class="inputbox"><option v-for="n in options" :key="n.id" :value="String(n.id)">{{ n.subject }}</option></select> <br />
        <input type="submit" :value="mode === 'remove' ? 'Remover.' : 'Carregar.'" class="button" :style="mode === 'remove' ? 'margin-top:10px;' : 'margin-top: 10px;'" />
      </form>
      <div v-if="mode === 'remove'" class="quadrosOut" v-html="response"></div>
      <div v-else-if="editing" id="logs_" class="quadrosOut">
        <form action="" method="post" name="noticeFrom" @submit.prevent="modify">
          <em>{{ PA.LDPA_NOTICE_ALTER_TEXT_TITLE }}:</em><br /><input v-model="editing.subject" name="subject" type="text" maxlength="50" /> <br />
          <em>{{ PA.LDPA_NOTICE_ALTER_TEXT_NOTICE }}:</em><br /><textarea id="content" v-model="editing.content" name="content"></textarea><br />
          <input type="submit" :value="PA.LDPA_NOTICE_ALTER_TEXT_SUBMIT" class="button" />
        </form>
      </div>
      <div v-else id="logs_" class="quadrosOut" v-html="response"></div>
    </div>
  </template>
</template>
