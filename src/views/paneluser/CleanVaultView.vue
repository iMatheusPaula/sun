<script setup lang="ts">
// paneluser[CLEAN_VAULT].tpl.php + ldPanelUser::loadOptionsCleanVault
import { reactive, ref } from 'vue'
import { usePanelAccount } from '@/lib/paneluser'
import * as P from '@/lang/ldpaneluser'

const { account, warehouse, checkPassword } = usePanelAccount()
const form = reactive({ Vault1: '0', Vault2: '0', Zen: '0', Pwd: '' })
const response = ref('')

function clean() {
  const w = warehouse.value
  const blue = (m: string) => `<div class='qdestaques2'>${m}</div>`
  if (!w) response.value = `<div class='quadros'><strong>${P.LDPU_CLEAN_VAULT_NOT_HAVE_VAULT}</strong></div>`
  else if (account.value!.online) response.value = blue(P.LDPU_YOU_MUST_BE_OFFLINE)
  else if (form.Pwd === '') response.value = blue(P.LDPU_CLEAN_VAULT_FILL_PASSWORD)
  else if (form.Vault1 === '0' && form.Vault2 === '0' && form.Zen === '0') response.value = blue(P.LDPU_CLEAN_VAULT_CHOOSE_TASK)
  else if (!checkPassword(form.Pwd)) response.value = blue(P.LDPU_CLEAN_VAULT_INVALID_PASSWORD)
  else {
    let done = ''
    if (form.Vault1 === '1') {
      w.items = []
      done += `${P.LDPU_CLEAN_VAULT_1_TEXT_SUCCESS}<br/>`
    }
    if (form.Vault2 === '1') {
      w.items2 = []
      done += `${P.LDPU_CLEAN_VAULT_2_TEXT_SUCCESS}<br/>`
    }
    if (form.Zen === '1') {
      w.money = 0
      done += P.LDPU_CLEAN_VAULT_ZEN_TEXT_SUCCESS
    }
    response.value = blue(done)
  }
}
</script>

<template>
  <h1 style="margin-top: 20px;">Limpar Bau</h1>
  <div>
    <div style="margin-bottom: 8px;" v-html="response"></div>
    <div class="qdestaques">Atenção, as ações realizadas nessa página não podem ser desfeitas.</div>
    <div id="classe_change" style="margin-top: 10px; padding-left: 4px;">
      <form action="" method="post" @submit.prevent="clean">
        <em>Limpar bau 1:</em><br /><select v-model="form.Vault1" name="Vault1" class="inputbox"><option value="0">Não</option><option value="1">Sim</option></select><br />
        <em>Limpar bau 2:</em><br /><select v-model="form.Vault2" name="Vault2" class="inputbox"><option value="0">Não</option><option value="1">Sim</option></select><br />
        <em>Limpar zen:</em><br /><select v-model="form.Zen" name="Zen" class="inputbox"><option value="0">Não</option><option value="1">Sim</option></select><br />
        <em>Minha senha:</em><br /><input v-model="form.Pwd" name="Pwd" type="password" class="inputbox" maxlength="10" /><br />
        <input type="submit" class="button" value="Limpar" style="margin-top: 6px;" /><br />
      </form>
    </div>
  </div>
</template>
