<script setup lang="ts">
// recovery[password].tpl.php + ldRecovery::recoveryPassword (USE_MD5 = 0: a senha vai por e-mail).
// Sem backend não há e-mail: a resposta é a mesma que o site mostrava depois do envio.
import { ref } from 'vue'
import InnerLayout from '@/layouts/InnerLayout.vue'
import { useAccountsStore } from '@/stores/accounts'
import { RECOVERY_LIMIT_TIME, USE_MD5 } from '@/config/settings'
import { sprintf } from '@/lang/format'
import * as L from '@/lang/ldrecovery'

const accounts = useAccountsStore()
const account = ref('')
const result = ref<{ kind: 'error'; message: string } | { kind: 'sent'; login: string; message: string } | null>(null)
const nextRequestAt = new Map<string, number>() // webSendEmailLimit

function recover() {
  const login = account.value
  if (login === '') return (result.value = { kind: 'error', message: L.RECOVERY_FILL_LOGIN })
  if (!accounts.find(login)) return (result.value = { kind: 'error', message: L.RECOVERY_LOGIN_NOT_FOUND })
  if ((nextRequestAt.get(login) ?? 0) > Date.now()) {
    return (result.value = { kind: 'error', message: sprintf(L.RECOVERY_WAIT_TIME_FOR_SEND_AGAIN, RECOVERY_LIMIT_TIME) })
  }
  nextRequestAt.set(login, Date.now() + RECOVERY_LIMIT_TIME * 60_000)
  result.value = { kind: 'sent', login, message: USE_MD5 ? L.RECOVERY_SEND_SUCCESS_MD5 : L.RECOVERY_SEND_SUCCESS }
}

function back() {
  result.value = null
}
</script>

<template>
  <InnerLayout location="RECUPERAR SENHA" subtitle="Recuperar senha">
    <div id="etc">
      <div class="legend" style="margin-bottom: 25px;">
        <h3 class="legend-title"><span style="font-size: 13px;">Informe os dados abaixo:</span></h3>
        <div style="margin-bottom: 8px; margin-top: 10px;">
          <div id="classe_change" style="margin-top: 16px; margin-bottom: 16px; padding-left: 5px;">
            <form action="" method="post" @submit.prevent="recover">
              Digite o seu login: <input v-model="account" class="inputbox" name="account" type="text" />
              <input type="submit" value="Recuperar" class="button" />
            </form>
          </div>
          <div v-if="result?.kind === 'error'" class="qdestaques">{{ result.message }}<br /><RouterLink to="/recovery" @click="back">{{ L.RECOVERY_BACK }}</RouterLink></div>
          <div v-else-if="result?.kind === 'sent'" class="quadros">{{ L.RECOVERY_LOGIN }}: <strong>{{ result.login }}</strong> <br /><br /><strong v-html="result.message"></strong></div>
        </div>
      </div>
    </div>
  </InnerLayout>
</template>
