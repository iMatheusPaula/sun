<script setup lang="ts">
// paneluser[AUCTIONS].tpl.php + ldPanelUser::loadOptionsAuctions: lista dos leilões ativos e, com
// ?action=showDetailsAuction&id=N, os detalhes com o lance atual, o lance da conta e o campo de lance.
// O lance (action=bid) respondia com alert(): sucesso recarregava os detalhes; erro voltava à página.
// Como no original, o lance não desconta os pontos (o UPDATE estava comentado).
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { usePanelAccount } from '@/lib/paneluser'
import { phpDate } from '@/lib/date'
import { auctionPhoto, auctionStatus } from '@/lib/auctions'
import { sprintf } from '@/lang/format'
import { POINTS_NAME } from '@/config/settings'
import type { Auction } from '@/data/db'
import FancyBox from '@/components/ui/FancyBox.vue'
import * as P from '@/lang/ldpaneluser'

const route = useRoute()
const router = useRouter()
const { store, login, account } = usePanelAccount()

const detailsId = route.query.action === 'showDetailsAuction' ? parseInt(String(route.query.id), 10) || 0 : null
const auctions = computed(() => store.db.auctions.filter((a) => a.active).sort((a, b) => b.id - a.id))
const auction = computed(() => auctions.value.find((a) => a.id === detailsId))

const status = (a: Auction, unknown: string) => auctionStatus(a, {
  end: P.LDPU_AUCTIONS_STATUS_END, waitBegin: P.LDPU_AUCTIONS_STATUS_WAIT_BEGIN, inProgress: P.LDPU_AUCTIONS_STATUS_IN_PROGRESS,
  waitPremium: P.LDPU_AUCTIONS_STATUS_WAIT_PREMIUM, unknown,
})

const detailsPath = (id: number) => ({ path: '/paneluser-auctions', query: { action: 'showDetailsAuction', id } })
const premiumShort = (premium: string) => premium.slice(0, 40) + (premium.length > 40 ? '...' : '')
const date = (ms: number) => phpDate('d/m/Y G:i:s', new Date(ms))

const bids = computed(() => store.db.bids.filter((b) => b.auction === detailsId))
const topBid = computed(() => bids.value.reduce<(typeof bids.value)[number] | undefined>((top, b) => (!top || b.amount > top.amount ? b : top), undefined))
const lastUserBid = computed(() => (topBid.value ? `${topBid.value.account}, ${topBid.value.lastBidDay} ${topBid.value.lastBidHour}` : P.LDPU_AUCTIONS_TAKE_FRIST_BID))
const lastValueBid = computed(() => topBid.value?.amount ?? auction.value?.minimumBid ?? 0)
const yourBid = computed(() => bids.value.find((b) => b.account === login.value)?.amount ?? 0)
const valueToBid = ref(String(lastValueBid.value + 1))
const largePhoto = ref<string | null>(null)

// action=bid
function bid() {
  const points = valueToBid.value
  const value = Number(points)
  const fail = (message: string) => window.alert(message) // history.go(-1): continua nos detalhes
  if (!/^\s*[+-]?(\d+\.?\d*|\.\d+)(e[+-]?\d+)?\s*$/i.test(points) || value <= 0) return fail(sprintf(P.LDPU_AUCTIONS_FILL_NUMBER_POSITIVE, POINTS_NAME)) // is_numeric
  const a = auction.value
  if (!a) return fail(P.LDPU_AUCTIONS_INVALID_AUCTION_OR_NOT_ACTIVE)
  const now = Date.now()
  if (a.closed) return fail(P.LDPU_AUCTIONS_STATUS_END)
  if (a.start > now) return fail(P.LDPU_AUCTIONS_STATUS_WAIT_BEGIN)
  if (a.end < now) return fail(P.LDPU_AUCTIONS_STATUS_WAIT_PREMIUM)
  if (a.minimumBid > value) return fail(`${P.LDPU_AUCTIONS_YOU_NEED_BID_MORE} ${a.minimumBid}.`)
  if (topBid.value && topBid.value.amount >= value) return fail(`${P.LDPU_AUCTIONS_YOU_NEED_BID_MORE} ${points}.`)
  if (account.value!.points < value) return fail(`${P.LDPU_AUCTIONS_YOU_NOT_HAVE_POINTS} ${POINTS_NAME}.`)

  const mine = bids.value.find((b) => b.account === login.value)
  // o UPDATE do lance existente só troca o valor (a data continua a do primeiro lance)
  if (mine) mine.amount = Math.trunc(value)
  else {
    const d = new Date()
    store.db.bids.push({ auction: a.id, account: login.value, amount: Math.trunc(value), lastBidDay: phpDate('d/m/Y', d), lastBidHour: phpDate('H:i:s', d) })
  }
  window.alert(P.LDPU_AUCTIONS_BID_SUCCESS_REGISTER)
  router.push(detailsPath(a.id))
}
</script>

<template>
  <h1 style="margin-top: 20px;">Leilões</h1>
  <br />

  <div id="etc">

    <div class="legend" style="margin-bottom: 25px;">
      <h3 class="legend-title"><span style="font-size: 13px;">Detalhes dos leilões:</span></h3>
      <div style="margin-bottom: 8px; margin-top: 10px;">

        <template v-if="detailsId === null">
          <table v-if="auctions.length === 0" style="width: 100%;"><tbody><tr><td style="width: 27px;">#</td><td>{{ P.LDPU_AUCTIONS_NAME }}</td><td>{{ P.LDPU_AUCTIONS_PREMIUM }}</td><td style="width: 100px;">{{ P.LDPU_AUCTIONS_STATUS }}</td></tr><tr><td style="text-align: center;" colspan="4">{{ P.LDPU_AUCTIONS_NOT_HAVE_AUCTIONS }}</td></tr></tbody></table>
          <table v-else style="width: 100%;"><tbody><tr><td style="width: 27px;">#</td><td>{{ P.LDPU_AUCTIONS_NAME }}</td><td>{{ P.LDPU_AUCTIONS_PREMIUM }}</td><td style="width: 150px;">{{ P.LDPU_AUCTIONS_STATUS }}</td></tr><tr v-for="a in auctions" :key="a.id">
              <td><RouterLink :to="detailsPath(a.id)">{{ a.id }}</RouterLink></td>
              <td><RouterLink :to="detailsPath(a.id)">{{ a.name }}</RouterLink></td>
              <td><RouterLink :to="detailsPath(a.id)">{{ premiumShort(a.premium) }}</RouterLink></td>
              <td><RouterLink :to="detailsPath(a.id)">{{ status(a, P.LDPU_AUCTIONS_STATUS_ERROR) }}</RouterLink></td>
            </tr></tbody></table>
        </template>
        <table v-else-if="!auction" style="width: 100%;"><tbody><tr><td style="text-align: center;" colspan="4">{{ P.LDPU_AUCTIONS_NOT_FOUND }}</td></tr></tbody></table>
        <template v-else>
          <table style="width: 100%;"><tbody><tr><td style="width: 150px;">{{ P.LDPU_AUCTIONS_ID }}:</td><td><strong>{{ auction.id }}</strong></td></tr><tr><td>{{ P.LDPU_AUCTIONS_NAME }}:</td><td><strong>{{ auction.name }}</strong></td></tr><tr><td>{{ P.LDPU_AUCTIONS_STATUS }}:</td><td><strong>{{ status(auction, 'Status desconhecido.') }}</strong></td></tr><tr><td>{{ P.LDPU_AUCTIONS_DATE_BEGIN }}:</td><td><strong>{{ date(auction.start) }}</strong></td></tr><tr><td>{{ P.LDPU_AUCTIONS_DATE_END }}:</td><td><strong>{{ date(auction.end) }}</strong></td></tr><tr><td>{{ P.LDPU_AUCTIONS_MIN_BID }}:</td><td><strong>{{ auction.minimumBid }} {{ POINTS_NAME }}</strong></td></tr><tr><td>{{ P.LDPU_AUCTIONS_PREMIUM }}:</td><td><strong>{{ auction.premium }}</strong></td></tr><tr><td>{{ P.LDPU_AUCTIONS_PHOTO }}:</td><td><a :id="`photoLarge_${auction.id}`" :href="auctionPhoto(auction, 'large')" @click.prevent="largePhoto = auctionPhoto(auction, 'large')">
            <img :src="auctionPhoto(auction, 'small')" /></a>
          </td></tr></tbody></table><br /><table style="width: 100%;"><tbody><tr><td style="width: 150px;">{{ P.LDPU_AUCTIONS_LAST_PUT_BID }}</td><td><strong>{{ lastUserBid }}</strong> - <RouterLink :to="detailsPath(auction.id)">[{{ P.LDPU_AUCTIONS_REFRESH }}]</RouterLink></td></tr><tr><td>{{ P.LDPU_AUCTIONS_AUCTION_VALUE }}:</td><td><strong>{{ lastValueBid }} {{ POINTS_NAME }}</strong></td></tr><tr><td>{{ P.LDPU_AUCTIONS_YOUR_LAST_BID }}:</td><td><strong>{{ yourBid }} {{ POINTS_NAME }}</strong></td></tr><tr>
            <td>{{ P.LDPU_AUCTIONS_PUT_TOUR_BID }}:</td>
            <td>
              <input id="valueToBid" v-model="valueToBid" type="text" class="inputbox" style="width: 50px;" />&nbsp;
              <input type="button" class="button" :value="P.LDPU_AUCTIONS_INTPUT_PUT_BID" @click="bid" />&nbsp;
              ({{ P.LDPU_AUCTIONS_YOU_HAVE }}: {{ account!.points }} {{ POINTS_NAME }})</td></tr></tbody></table>
          <table v-if="auction.winner === login" style="width: 100%; text-align: center;"><tbody><tr><td style="padding: 20px;"><strong>{{ P.LDPU_AUCTIONS_YOU_WINNER }}<br /><br />{{ auction.winnerText }}</strong></td></tr></tbody></table>
          <FancyBox v-if="largePhoto" :src="largePhoto" @closed="largePhoto = null" />
        </template>

      </div>
    </div>

  </div>
</template>
