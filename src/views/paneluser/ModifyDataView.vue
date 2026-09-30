<script setup lang="ts">
// paneluser[MODIFY_DATA].tpl.php + ldPanelUser::loadOptionsModifyData (Write=1 dados, Write=2 senha)
import { reactive, ref } from 'vue'
import { usePanelAccount } from '@/lib/paneluser'
import * as P from '@/lang/ldpaneluser'

const { account, checkPassword } = usePanelAccount()
const a = account.value!

const data = reactive({ userName: a.name, userTel: a.tel })
const pwd = reactive({ userPwd: '', userPwdNew: '', userPwdNewRe: '', userAnswer: '' })
const response = ref('')
const blue = (m: string) => `<div class='qdestaques2'>${m}</div>`
const strlen = (s: string) => new TextEncoder().encode(s).length

function saveData() {
  if (data.userName === '') response.value = blue(P.LDPU_MODIFY_DATA_FILL_NAME)
  else if (data.userTel === '') response.value = blue(P.LDPU_MODIFY_DATA_FILL_PHONE)
  else if (strlen(data.userName) > 10) response.value = blue(P.LDPU_MODIFY_DATA_INVALID_SIZE_NAME)
  else if (strlen(data.userTel) > 15) response.value = blue(P.LDPU_MODIFY_DATA_INVALID_SIZE_PHONE)
  else {
    a.name = data.userName
    a.tel = data.userTel
    response.value = blue(P.LDPU_MODIFY_DATA_SUCCESS_ALTER)
  }
}

function savePassword() {
  if (pwd.userPwd === '') response.value = blue(P.LDPU_MODIFY_DATA_FILL_PASSWORD)
  else if (pwd.userPwdNew === '' || pwd.userPwdNewRe === '') response.value = blue(P.LDPU_MODIFY_DATA_FILL_NEW_PASSWORD)
  else if (pwd.userAnswer === '') response.value = blue(P.LDPU_MODIFY_DATA_FILL_SECRET_ANSWER)
  else if (strlen(pwd.userPwdNew) > 10 || strlen(pwd.userPwdNewRe) > 10) response.value = blue(P.LDPU_MODIFY_DATA_INVALID_SIZE_PASSWORD)
  else if (pwd.userPwdNew !== pwd.userPwdNewRe) response.value = blue(P.LDPU_MODIFY_DATA_PASSWORDS_MUST_BE_IDENTICAL)
  else if (pwd.userAnswer !== a.answer) response.value = blue(P.LDPU_MODIFY_DATA_INVALID_SECRET_ANSWER)
  else if (!checkPassword(pwd.userPwd)) response.value = blue(P.LDPU_MODIFY_DATA_INVALID_PASSWORD)
  else {
    a.password = pwd.userPwdNew
    response.value = blue(P.LDPU_MODIFY_DATA_SUCCESS_ALTER)
  }
}
</script>

<template>
  <h1 style="margin-top: 20px;">Alterar Dados</h1>

  <div id="etc" style="margin-bottom: 26px;" v-html="response"></div>

  <div class="legend" style="margin-bottom: 25px;">
    <h3 class="legend-title"><span style="font-size: 13px;">Dados da minha conta</span></h3>
    <div style="margin-bottom: 8px; margin-top: 10px;">
      <form action="" method="post" @submit.prevent="saveData">
        <em>Meu nome:</em><br /><input v-model="data.userName" name="userName" type="text" class="inputbox" maxlength="10" /><br />
        <em>Meu telefone:</em><br /><input v-model="data.userTel" name="userTel" type="text" class="inputbox" maxlength="15" /><br />
        <em>Meu email:</em><br /><input name="userMail" type="text" class="inputbox" :value="a.email" readonly /><br />
        <input type="submit" value="Alterar" class="button" style="margin-top: 6px;" />
      </form>
    </div>
  </div>

  <div class="legend" style="margin-bottom: 1px;">
    <h3 class="legend-title"><span style="font-size: 13px;">Senha de acesso</span></h3>
    <div style="margin-bottom: 8px; margin-top: 10px;">
      <form action="" method="post" @submit.prevent="savePassword">
        <em>Senha atual:</em><br /><input v-model="pwd.userPwd" name="userPwd" type="password" class="inputbox" maxlength="10" /><br />
        <em>Senha Nova:</em><br /><input v-model="pwd.userPwdNew" name="userPwdNew" type="password" class="inputbox" maxlength="10" /><br />
        <em>Senha Nova:</em><br /><input v-model="pwd.userPwdNewRe" name="userPwdNewRe" type="password" class="inputbox" maxlength="10" /><br />
        <em>Pergunta secreta:</em><br /><strong>{{ a.question }}</strong><br />
        <em>Resposta Secreta:</em><br /><input v-model="pwd.userAnswer" name="userAnswer" type="text" class="inputbox" maxlength="50" /><br />
        <input type="submit" value="Alterar" class="button" style="margin-top: 6px;" /><br />
      </form>
    </div>
  </div>
</template>
