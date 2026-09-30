<script setup lang="ts">
// paneladmin[EDIT_ACCOUNT].tpl.php + ldPanelAdmin::optionLoadEditAccount: carrega a conta (MEMB_INFO) num formulário
// e grava as alterações; a senha só muda se for preenchida (USE_MD5 desligado: o campo mostra a senha atual).
import { reactive, ref } from 'vue'
import { useAccountsStore } from '@/stores/accounts'
import { isEmpty } from '@/lib/paneladmin'
import type { AccountRow } from '@/data/db'
import * as PA from '@/lang/ldpaneladmin'

const accounts = useAccountsStore()
const login = ref('')
const loaded = ref<AccountRow | null>(null)
const loadedLogin = ref('') // $_POST['account'] como foi digitado
const form = reactive({ memb__pwd: '', mail_addr: '', memb_name: '', tel__numb: '', fpas_ques: '', fpas_answ: '', data: '', aniversario: '' })
const response = ref('')

// Write=true
function load() {
  loaded.value = null
  response.value = ''
  const typed = login.value
  login.value = '' // o POST recarregava a página com o campo vazio
  if (isEmpty(typed)) return (response.value = `<div class="qdestaques">${PA.LDPA_FILL_LOGIN}</div>`)
  const a = accounts.find(typed)
  if (!a) return (response.value = `<div class="qdestaques">${PA.LDPA_INVALID_LOGIN}</div>`)
  loadedLogin.value = typed
  Object.assign(form, { memb__pwd: a.password, mail_addr: a.email, memb_name: a.name, tel__numb: a.tel, fpas_ques: a.question, fpas_answ: a.answer, data: a.createdAt, aniversario: a.birthday })
  loaded.value = a
}

// SubWrite=true&account=…
function save() {
  const a = accounts.find(loadedLogin.value)
  if (a) {
    Object.assign(a, { email: form.mail_addr, name: form.memb_name, tel: form.tel__numb, question: form.fpas_ques, answer: form.fpas_answ, createdAt: form.data, birthday: form.aniversario })
    if (!isEmpty(form.memb__pwd)) a.password = form.memb__pwd
  }
  loaded.value = null
  response.value = `<div class='qdestaques2'>${PA.LDPA_EDIT_ACCOUNT_TEXT_SECCESS}</div>`
}
</script>

<template>
  <h1 style="margin-top: 20px;">Editar conta</h1>

  <div class="legend" style="margin-top: 25px; padding: 10px;">
    <h3 class="legend-title"><span style="font-size: 13px;">Preencha os dados abaixo:</span></h3>
    <form action="" method="post" @submit.prevent="load">
      <em>Login:</em> <input v-model="login" name="account" type="text" class="inputbox" /> <input type="submit" value="Carregar dados." class="button" />
    </form>
    <div v-if="loaded" class="quadrosOut">
      <div class="quadros">
        <form action="" method="post" @submit.prevent="save">
          <em>{{ PA.LDPA_TEXT_LOGIN }}:</em><br /><strong>{{ loadedLogin }}</strong><br />
          <em>{{ PA.LDPA_TEXT_PASSWORD }}:</em><br /><input v-model="form.memb__pwd" type="text" name="memb__pwd" /> ({{ PA.LDPA_EDIT_ACCOUNT_TEXT_PASSWORD_FOR_ALTER }})<br />
          <em>{{ PA.LDPA_TEXT_EMAIL }}:</em><br /><input v-model="form.mail_addr" type="text" name="mail_addr" /><br />
          <em>{{ PA.LDPA_TEXT_NAME }}:</em><br /><input v-model="form.memb_name" type="text" name="memb_name" /><br />
          <em>{{ PA.LDPA_TEXT_PHONE }}:</em><br /><input v-model="form.tel__numb" type="text" name="tel__numb" /><br />
          <em>{{ PA.LDPA_TEXT_SECRET_QUESTION }}:</em><br /><input v-model="form.fpas_ques" type="text" name="fpas_ques" /><br />
          <em>{{ PA.LDPA_TEXT_SECRET_ANSWER }}:</em><br /><input v-model="form.fpas_answ" type="text" name="fpas_answ" /><br />
          <em>{{ PA.LDPA_EDIT_ACCOUNT_TEXT_CREATE_IN }}:</em><br /><input v-model="form.data" type="text" name="data" /><br />
          <em>{{ PA.LDPA_EDIT_ACCOUNT_TEXT_BIRTHDAY }}:</em><br /><input v-model="form.aniversario" type="text" name="aniversario" /><br />
          <input type="submit" class="button" :value="PA.LDPA_TEXT_ALTER_DATA_SUBMIT" />
        </form>
      </div>
    </div>
    <div v-else class="quadrosOut" v-html="response"></div>
  </div>
</template>
