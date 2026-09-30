<script setup lang="ts">
// paneladmin[GERATE_BACKUPS].tpl.php + ldPanelAdmin::optionLoadGerateBackup: o original mandava o SQL Server fazer
// BACKUP DATABASE num arquivo do servidor. Sem servidor, o backup é o do banco do front (JSON), baixado com o nome
// do arquivo (%s = banco escolhido); a lista de bancos é a do exemplo do kit.
import { reactive, ref } from 'vue'
import { useDbStore } from '@/stores/db'
import { phpDate } from '@/lib/date'
import { sprintf } from '@/lang/format'
import * as PA from '@/lang/ldpaneladmin'

const store = useDbStore()
const DATABASES = ['master', 'model', 'msdb', 'MuOnline', 'tempdb'] // exec sp_databases
const suggestion = () => `%s ${phpDate('m-d-Y g-i a', new Date())}.bak`
const form = reactive({ database: DATABASES[0]!, filename: suggestion(), dirname: 'C:\\Backups\\' })
const result = ref('')

function backup() {
  const address = sprintf(form.dirname + form.filename, form.database)
  const blob = new Blob([JSON.stringify(store.db, null, 1)], { type: 'application/json' })
  const link = document.createElement('a')
  link.href = URL.createObjectURL(blob)
  link.download = sprintf(form.filename, form.database)
  link.click()
  URL.revokeObjectURL(link.href)
  Object.assign(form, { database: DATABASES[0]!, filename: suggestion(), dirname: 'C:\\Backups\\' }) // o POST recarregava a página
  result.value = `<div class="qdestaques2">${PA.LDPA_GENERATE_BACKUP_SUCCESS}<br /><em>${address}</em></div>`
}
</script>

<template>
  <h1 style="margin-top: 20px;">Gerar Backup</h1>

  <div class="legend" style="margin-top: 25px; padding: 10px;">
    <h3 class="legend-title"><span style="font-size: 13px;">Preencha os dados abaixo:</span></h3>
    <form action="" method="post" @submit.prevent="backup">
      <em>Banco de dados:</em> <select v-model="form.database" name="database" class="inputbox"><option v-for="d in DATABASES" :key="d" :value="d">{{ d }}</option></select><br />
      <em>Nome do arquivo:</em> <input v-model="form.filename" name="filename" type="text" class="inputbox" /> <strong>(Recomendamos não alterar)</strong><br />
      <em>Salvar em:</em> <input v-model="form.dirname" name="dirname" type="text" class="inputbox" /><br />
      <input type="submit" value="Criar Backup" class="button" />
    </form>

    <div class="quadrosOut" v-html="result"></div>
  </div>
</template>
