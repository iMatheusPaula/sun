<script setup lang="ts">
// paneluser[DOUBLE_VAULT].tpl.php + ldPanelUser::loadOptionsDoubleVault: troca o baú pelo alternativo
import { ref } from 'vue'
import { usePanelAccount } from '@/lib/paneluser'
import * as P from '@/lang/ldpaneluser'

const { account, warehouse, checkPassword } = usePanelAccount()
const userPwd = ref('')
const response = ref('')

function swap() {
  const w = warehouse.value
  const blue = (m: string) => `<div class='qdestaques2'>${m}</div>`
  if (!w) response.value = `<div class='quadros'><strong>${P.LDPU_DOUBLE_VAULT_NOT_HAVE_VAULT}</strong></div>`
  else if (account.value!.online) response.value = blue(P.LDPU_YOU_MUST_BE_OFFLINE)
  else if (userPwd.value === '') response.value = blue(P.LDPU_DOUBLE_VAULT_FILL_PASSWORD)
  else if (userPwd.value.length > 10) response.value = blue(P.LDPU_DOUBLE_VAULT_INVALID_SIZE_PASSWORD)
  else if (!checkPassword(userPwd.value)) response.value = blue(P.LDPU_DOUBLE_VAULT_INVALID_PASSWORD)
  else {
    ;[w.items, w.items2] = [w.items2, w.items]
    response.value = blue(P.LDPU_DOUBLE_VAULT_SUCCESS_ALTER)
  }
}
</script>

<template>
  <h1 style="margin-top: 20px;">Bau Duplo</h1>

  <div id="etc">
    <div id="classe_change" style="margin-top: 16px; margin-bottom: 16px; padding-left: 5px;" v-html="response"></div>

    <div id="etc">
      <div class="qdestaques2">Atenção, usando essa opção você mudará o seu bau para o alternativo.<br />Para desfazer essa ação apenas mude novamente.</div>
      <div style="padding-left: 10px; margin-top: 12px;">
        <form action="" method="post" @submit.prevent="swap">
          <em>Minha senha:</em><br /><input v-model="userPwd" class="inputbox" name="userPwd" type="password" maxlength="10" /><br />
          <input type="submit" value="Alterar Bau" class="button" style="margin-top: 6px;" /><br />
        </form>
      </div>
    </div>
  </div>
</template>
