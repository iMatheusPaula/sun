<script setup lang="ts">
// paneluser[LOG_BUYS].tpl.php + ldPanelUser::loadOptionsLogBuys: ?Write=1 depósitos (SGolds), ?Write=2 vips
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { usePanelAccount } from '@/lib/paneluser'
import { CASH_NAME, VIP_NAMES } from '@/config/settings'
import { phpDate } from '@/lib/date'
import * as P from '@/lang/ldpaneluser'

const route = useRoute()
const { store, login } = usePanelAccount()
const write = computed(() => String(route.query.Write ?? ''))
const deposits = computed(() => store.db.deposits.filter((d) => d.account === login.value))
const vips = computed(() => store.db.vipPurchases.filter((v) => v.account === login.value))

// o ternário do original testa status == 0 duas vezes: "Rejeitado" nunca aparece, status 2 vira "Erro de Status"
const status = (s: number) => (s === 0 ? P.LDPU_LOG_BUY_TEXT_IN_PROGRESS : s === 1 ? P.LDPU_LOG_BUY_TEXT_COMPLETED : P.LDPU_LOG_BUY_TEXT_STATUS_ERROR)
const lines = (text: string) => text.split('\n')
</script>

<template>
  <h1 style="margin-top: 20px;">Log de compras</h1>
  <br />
  <div class="legend" style="margin-bottom: 25px;">
    <h3 class="legend-title"><span style="font-size: 13px;">Selecione o log desejado abaixo:</span></h3>
    <div style="margin-bottom: 8px; margin-top: 10px;">
      <ul>
        <li><em>Gerar log de compras de:</em> <RouterLink :to="{ path: '/paneluser-log-buys', query: { Write: 1 } }"><strong>{{ CASH_NAME }}</strong></RouterLink></li>
        <li><em>Gerar log de compras de:</em> <RouterLink :to="{ path: '/paneluser-log-buys', query: { Write: 2 } }"><strong>Vips</strong></RouterLink></li>
      </ul>
    </div>
  </div>

  <div id="logs_">
    <template v-if="write === '1'">
      <div v-for="d in deposits" :key="d.id" class="quadros"><em>{{ P.LDPU_LOG_BUY_TEXT_ID }}:</em> <strong>{{ d.id }}</strong><br /><em>{{ P.LDPU_LOG_BUY_TEXT_AMOUNT }}:</em> <strong>{{ d.cash }}</strong><br /><em>{{ P.LDPU_LOG_BUY_TEXT_DATE }}:</em> <strong>{{ d.date }}</strong><br /><em>{{ P.LDPU_LOG_BUY_TEXT_HOUR }}:</em> <strong>{{ d.hour }}</strong><br /><em>{{ P.LDPU_LOG_BUY_TEXT_COMMENT }}:</em> <strong><template v-for="(l, i) in lines(d.comment)" :key="i"><br v-if="i" />{{ l }}</template></strong><br /><em>{{ P.LDPU_LOG_BUY_TEXT_COMMENT_ADM }}:</em> <strong><template v-for="(l, i) in lines(d.adminComment)" :key="i"><br v-if="i" />{{ l }}</template></strong><br /><em>{{ P.LDPU_LOG_BUY_TEXT_STATUS }}:</em> <strong>{{ status(d.status) }}</strong><br /></div>
    </template>
    <template v-else-if="write === '2'">
      <div v-for="v in vips" :key="v.id" class="quadros"><em>{{ P.LDPU_LOG_BUY_TEXT_ID }}:</em> <strong>{{ v.id }}</strong><br /><em>{{ P.LDPU_LOG_BUY_TEXT_TYPE }}:</em> <strong>{{ VIP_NAMES[v.type] }}</strong><br /><em>{{ P.LDPU_LOG_BUY_TEXT_DAYS }}:</em> <strong>{{ v.days }}</strong><br /><em>{{ P.LDPU_LOG_BUY_TEXT_VALUE_BY }}:</em> <strong>{{ v.cashAmount }}</strong><br /><em>{{ P.LDPU_LOG_BUY_TEXT_DATE }}:</em> <strong>{{ phpDate('d/m/Y g:i a', new Date(v.date)) }}</strong><br /></div>
    </template>
  </div>
</template>
