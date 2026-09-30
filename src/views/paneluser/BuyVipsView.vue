<script setup lang="ts">
// paneluser[BUY_VIPS].tpl.php + ldPanelUser::loadOptionsBuyVips: compra ou renova o vip com SGolds
import { ref } from 'vue'
import { usePanelAccount } from '@/lib/paneluser'
import { ACTIVE_VIPS, CASH_NAME, VIP_ACTIVES, VIP_DAYS, VIP_NAMES, VIP_PRICES } from '@/config/settings'
import { phpDate } from '@/lib/date'
import { sprintf } from '@/lang/format'
import * as P from '@/lang/ldpaneluser'

const { store, account } = usePanelAccount()
const openVip = ref(0) // AbreMenuV2('divV2N')
const response = ref<{ ok: boolean; html: string } | null>(null)
const periods = ['30 dias (1 Mês)', '90 dias (3 Meses)', '180 dias (6 Meses)', '365 dias (1 Ano)']

function buy(event: Event) {
  const a = account.value!
  const [type, days] = String(new FormData(event.target as HTMLFormElement).get('vip') ?? '').split(':').map(Number) as [number, number]
  const red = (html: string) => (response.value = { ok: false, html })
  const tier = VIP_DAYS.indexOf(days as (typeof VIP_DAYS)[number])
  if (!(type >= 1 && type <= 5)) return red(P.LDPU_BUY_VIP_ERROR_TIME)
  if (!VIP_ACTIVES[type as 1]) return red(P.LDPU_BUY_VIP_DISABLED_TYPE)
  if (tier === -1) return red(P.LDPU_BUY_VIP_ERROR_TIME)
  const price = VIP_PRICES[type as 1][tier]
  if (price > a.cash) return red(P.LDPU_BUY_VIP_NOT_HAVE_CASH)
  if (a.vip > 0 && type !== a.vip) return red(sprintf(P.LDPU_BUY_VIP_YOU_IS_VIP, VIP_NAMES[a.vip]!, VIP_NAMES[type]!, VIP_NAMES[a.vip]!))

  a.cash -= price
  if (a.vip > 0) {
    a.vipEnd += days * 86400_000 // renovação: soma os dias ao vencimento atual
  } else {
    a.vip = type
    a.vipBegin = Date.now()
    a.vipEnd = Date.now() + days * 86400_000
  }
  const id = Math.max(0, ...store.db.vipPurchases.map((v) => v.id)) + 1
  store.db.vipPurchases.push({ id, account: a.login, type, days, cashAmount: price, date: Date.now() })
  response.value = { ok: true, html: sprintf(P.LDPU_BUY_VIP_SUCCESS, phpDate('d-m-Y g:i a', new Date(a.vipEnd))) + '</em>' }
}
</script>

<template>
  <h1 style="margin-top: 20px;">Comprar vip</h1>
  <br />

  <div id="etc">
    <div class="legend" style="margin-bottom: 25px;">
      <h3 class="legend-title"><span style="font-size: 13px;">Escolha o personagem:</span></h3>
      <div style="margin-bottom: 8px; margin-top: 10px;">
        <ul>
          <li>Seu saldo é de: {{ account!.cash }} {{ CASH_NAME }}</li>
        </ul>
        <ul>
          <li>
            O que você deseja comprar?
            <template v-for="(v, i) in ACTIVE_VIPS" :key="v"><a href="javascript:;" @click.prevent="openVip = v"><strong>{{ VIP_NAMES[v] }}</strong></a>{{ i < ACTIVE_VIPS.length - 1 ? ', ' : '' }}</template>
          </li>
        </ul>

        <div v-for="v in ACTIVE_VIPS" v-show="openVip === v" :id="`divV2${v}`" :key="v">
          <form id="Compras" action="" method="post" @submit.prevent="buy">
            <div class="legend" style="margin-bottom: 25px; margin-top: 20px;">
              <h3 class="legend-title"><span style="font-size: 13px;">Por quanto tempo você deseja ser {{ VIP_NAMES[v] }}?</span></h3>
              <div style="margin-bottom: 6px; margin-top: 6px;">
                <template v-for="(days, i) in VIP_DAYS" :key="days"><input name="vip" type="radio" :value="`${v}:${days}`" :checked="i === 0" />&nbsp;{{ VIP_NAMES[v] }} {{ periods[i] }}: <strong>{{ VIP_PRICES[v][i] }}</strong> {{ CASH_NAME }}<br /></template>
                <input type="submit" value="Finalizar Compra" class="button" style="margin-top: 6px; margin-left: 20px;" />
              </div>
            </div>
          </form>
        </div>

        <div style="margin-top: 16px; margin-bottom: 16px;"><div v-if="response" :class="response.ok ? 'qdestaques2' : 'qdestaques'" v-html="response.html"></div></div>
      </div>
    </div>
  </div>
</template>
