<script setup lang="ts">
// paneluser[MODIFY_PERSONALID].tpl.php + ldPanelUser::loadOptionsModifyPersonalID
import { reactive, ref } from 'vue'
import { usePanelAccount } from '@/lib/paneluser'
import * as P from '@/lang/ldpaneluser'

const { account, checkPassword } = usePanelAccount()
const form = reactive({ pid: '', pwd: '' })
const response = ref('')

function save() {
  const red = (m: string) => `<div class='qdestaques'><strong style='color:red;'>${m}</strong></div>`
  if (!/^\s*[+-]?(\d+\.?\d*|\.\d+)(e[+-]?\d+)?\s*$/i.test(form.pid)) response.value = red(P.LDPU_MODIFY_PID_FILL_ONLY_NUMBERS) // is_numeric
  else if (form.pid.length !== 7) response.value = red(P.LDPU_MODIFY_PID_INVALID_SIZE)
  else if (!checkPassword(form.pwd)) response.value = red(P.LDPU_MODIFY_PID_INVALID_PASSWORD)
  else {
    account.value!.personalId = `111111${form.pid}` // sno__numb
    response.value = `<div class='quadros'><strong style='color:blue;'>${P.LDPU_MODIFY_PID_SUCCESS}</strong></div>`
  }
}
</script>

<template>
  <h1 style="margin-top: 20px;">Alterar Personal ID</h1>
  <br />
  <div id="etc" style="margin-bottom: 26px;" v-html="response"></div>

  <div class="legend" style="margin-bottom: 25px;">
    <h3 class="legend-title"><span style="font-size: 13px;">Digite os dados abaixo:</span></h3>
    <div style="margin-bottom: 8px; margin-top: 10px;">
      <form action="" method="post" @submit.prevent="save">
        <div class="qdestaques2" style="margin-bottom: 16px;"><strong>Atenção</strong>, o personal ID serve para quando você vai deletar ou sair de uma guild ou deletar um personagem.<br />
          Com o Personal ID sua conta estará mais segura.</div>
        Digite um número de 7 caracters: <input v-model="form.pid" name="pid" type="text" class="inputbox" maxlength="7" /><br />
        Digite sua senha: <input v-model="form.pwd" name="pwd" type="password" class="inputbox" maxlength="10" /><br />
        <input type="submit" class="button" value="Alterar Personal ID" style="margin-top: 8px;" />
      </form>
    </div>
  </div>
</template>
