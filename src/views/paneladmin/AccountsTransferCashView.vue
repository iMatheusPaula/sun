<script setup lang="ts">
// paneladmin[MANAGER_ACCOUNTS_TRANSFER_CASH].tpl.php + ldPanelAdmin::optionLoadManagerAccountsTransferCash: as contas
// liberadas para transferir SGolds/Golds (resale = 1). Remover era um link (?action=remove&memb___id=X); Adicionar,
// um POST que respondia com alert() quando o login não existe.
import { computed, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useDbStore } from '@/stores/db'
import { useAccountsStore } from '@/stores/accounts'
import { CASH_NAME, CASH_NAME2 } from '@/config/settings'
import * as PA from '@/lang/ldpaneladmin'

const route = useRoute()
const store = useDbStore()
const accounts = useAccountsStore()
const login = ref('')

if (route.query.action === 'remove') {
  const a = accounts.find(String(route.query.memb___id ?? ''))
  if (a) a.resale = false
}

const list = computed(() => store.db.accounts.filter((a) => a.resale))
const removePath = (id: string) => ({ path: '/paneladmin-manager-accounts-transfer-cash', query: { action: 'remove', memb___id: id } })

function add() {
  const a = accounts.find(login.value)
  if (!a) window.alert(PA.LDPA_MACCOUNT_TRANSFCASH_INCORRECT_LOGIN)
  else a.resale = true
  login.value = ''
}
</script>

<template>
  <h1 style="margin-top: 20px;">Liberar / Travar conta para transferir {{ CASH_NAME }}/{{ CASH_NAME2 }}</h1>

  <div class="legend" style="margin-top: 25px; padding: 10px;">
    <h3 class="legend-title"><span style="font-size: 13px;">Preencha os dados abaixo:</span></h3>
    <div class="quadrosOut">
      <table border="0" width="100%">
        <tbody>
          <tr>
            <td align="center" bgcolor="#E2DEC5"><strong>{{ PA.LDPA_MACCOUNT_TRANSFCASH_LOGIN }}</strong></td>
            <td align="center" bgcolor="#E2DEC5"><strong>{{ PA.LDPA_MACCOUNT_TRANSFCASH_EMAIL }}</strong></td>
            <td align="center" bgcolor="#E2DEC5"><strong>{{ PA.LDPA_MACCOUNT_TRANSFCASH_ACTION }}</strong></td>
          </tr>
          <tr v-for="a in list" id="rowDc" :key="a.login">
            <td align="center" bgcolor="#EDEBDC"><strong>{{ a.login }}</strong></td>
            <td align="center" bgcolor="#EDEBDC">{{ a.email }}</td>
            <td align="center" bgcolor="#EDEBDC"><RouterLink :to="removePath(a.login)">Remover</RouterLink></td>
          </tr>
          <tr>
            <td colspan="3">
              <form action="" method="post" @submit.prevent="add">
                <em>{{ PA.LDPA_MACCOUNT_TRANSFCASH_ADD_LOGIN }}: </em><input v-model="login" type="text" name="memb___id" maxlength="10" />
                <input type="submit" :value="PA.LDPA_MACCOUNT_TRANSFCASH_ADD_SUBMIT" class="button" />
              </form>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
