<script setup lang="ts">
// paneladmin[ADD_AUCTIONS|EDIT_AUCTIONS|DELETE_AUCTIONS|CLOSE_AUCTIONS].tpl.php + ldPanelAdmin::optionLoadAuctionsAdd /
// Edit / Remove / Close. Fechar mostra o leilão com os lances (quem não tem mais os pontos do lance é desclassificado
// ao abrir essa tela, como no original) e, ao salvar, desconta os pontos do maior lance e grava o vencedor. O status
// dessa tela usava uma variável errada no original (saía sempre "Aguardando premiação"); aqui é o do leilão.
import { computed, reactive, ref } from 'vue'
import { useDbStore } from '@/stores/db'
import { useAccountsStore } from '@/stores/accounts'
import { isEmpty, isNumeric } from '@/lib/paneladmin'
import { auctionPhoto, auctionPhotoUpload, auctionStatus, parseAuctionDate } from '@/lib/auctions'
import { phpDate } from '@/lib/date'
import { POINTS_NAME } from '@/config/settings'
import type { Auction } from '@/data/db'
import FancyBox from '@/components/ui/FancyBox.vue'
import * as PA from '@/lang/ldpaneladmin'

const props = defineProps<{ mode: 'add' | 'edit' | 'remove' | 'close' }>()
const store = useDbStore()
const accounts = useAccountsStore()
const response = ref('')
const photoInput = ref<HTMLInputElement>()
const largePhoto = ref<string | null>(null)
const red = (m: string) => `<div class="qdestaques">${m}</div>`
const blue = (m: string) => `<div class="qdestaques2">${m}</div>`
const date = (ms: number) => phpDate('d/m/Y G:i:s', new Date(ms))

const all = computed(() => [...store.db.auctions].sort((a, b) => b.id - a.id))
const open = computed(() => all.value.filter((a) => !a.closed))
const selected = ref(String((props.mode === 'close' ? open.value : all.value)[0]?.id ?? ''))

// campos do formulário de adicionar/modificar
const blank = () => ({ name: '', premium: '', startDate: '', endDate: '', active: false, minimumBid: '' })
const form = reactive(blank())
const showForm = ref(props.mode === 'add')

function validate(edit: boolean): string | null {
  if (isEmpty(form.name)) return edit ? PA.LDPA_AUCTIONS_EDIT_FILL_ACTION_NAME : PA.LDPA_AUCTIONS_ADD_FILL_NAME
  if (isEmpty(form.premium)) return edit ? PA.LDPA_AUCTIONS_EDIT_PREMIUM : PA.LDPA_AUCTIONS_ADD_FILL_PREMIUM
  if (!edit && !photoInput.value?.files?.length) return PA.LDPA_AUCTIONS_ADD_FILL_PHOTO
  if (isEmpty(form.startDate)) return edit ? PA.LDPA_AUCTIONS_EDIT_FILL_DATE_BEGIN : PA.LDPA_AUCTIONS_ADD_FILL_DATE_BEGIN
  if (isEmpty(form.endDate)) return edit ? PA.LDPA_AUCTIONS_EDIT_FILL_DATE_END : PA.LDPA_AUCTIONS_ADD_FILL_DATE_END
  if (isEmpty(form.minimumBid)) return edit ? PA.LDPA_AUCTIONS_EDIT_FILL_MIN_BID : PA.LDPA_AUCTIONS_ADD_FILL_MIN_BID
  if (!isNumeric(form.minimumBid) || Number(form.minimumBid) <= 0) return edit ? PA.LDPA_AUCTIONS_EDIT_FILL_BID_VALID_NUMBER : PA.LDPA_AUCTIONS_ADD_FILL_BID_VALID_NUMBER
  return null
}

// Adicionar (Write=true): o erro volta com o formulário preenchido (menos a foto); o sucesso aparece sozinho
const formError = ref('')
async function add() {
  const fail = (m: string) => {
    formError.value = m
    photoInput.value!.value = ''
  }
  const error = validate(false)
  if (error) return fail(error)
  const photo = await auctionPhotoUpload(photoInput.value!.files![0]!)
  if (!photo) return fail(PA.LDPA_AUCTIONS_ADD_ERROR_SEND_PHOTO)
  store.db.auctions.push({
    id: Math.max(0, ...store.db.auctions.map((a) => a.id)) + 1, name: form.name, premium: form.premium,
    start: parseAuctionDate(form.startDate), end: parseAuctionDate(form.endDate), active: form.active,
    minimumBid: Number(form.minimumBid), closed: false, winner: '', winnerText: '', photo,
  })
  showForm.value = false
  response.value = blue(PA.LDPA_AUCTIONS_ADD_AUCTION_ADD_SUCCESS)
}

// Modificar: action=load (escolhido no select) e o formulário com Write=true
const editing = ref<Auction | null>(null)
function load() {
  response.value = ''
  const a = store.db.auctions.find((x) => x.id === (parseInt(selected.value, 10) || 0))
  if (!a) return (response.value = red(PA.LDPA_AUCTIONS_EDIT_AUCTION_NOT_FOUND))
  Object.assign(form, { name: a.name, premium: a.premium, startDate: date(a.start), endDate: date(a.end), active: a.active, minimumBid: String(a.minimumBid) })
  editing.value = a
}
async function save() {
  const a = editing.value!
  editing.value = null // a resposta do original saía sem o formulário (ele era montado depois do salvamento)
  const error = validate(true)
  if (error) return (response.value = red(error))
  Object.assign(a, { name: form.name, premium: form.premium, start: parseAuctionDate(form.startDate), end: parseAuctionDate(form.endDate), active: form.active, minimumBid: Number(form.minimumBid) })
  const file = photoInput.value?.files?.[0]
  if (file) {
    const photo = await auctionPhotoUpload(file)
    if (!photo) return (response.value = red(PA.LDPA_AUCTIONS_EDIT_ERROR_SEND_PHOTO))
    a.photo = photo
  }
  response.value = blue(PA.LDPA_AUCTIONS_EDIT_SUCCESS_EDIT)
}

// Remover (action=remove): o leilão e os lances
const removed = ref(false)
function remove() {
  const id = parseInt(selected.value, 10) || 0
  removed.value = true
  if (!store.db.auctions.some((a) => a.id === id)) return (response.value = red(PA.LDPA_AUCTIONS_REMOVE_NOT_FOUND))
  store.db.auctions = store.db.auctions.filter((a) => a.id !== id)
  store.db.bids = store.db.bids.filter((b) => b.auction !== id)
  response.value = blue(PA.LDPA_AUCTIONS_REMOVE_SUCCESS_REMOVE)
}

// Fechar: action=close mostra o leilão; action=save grava o vencedor
const closing = ref<Auction | null>(null)
const closed = ref(false)
const textPlayer = ref('')
function details() {
  const id = parseInt(selected.value, 10) || 0
  const a = store.db.auctions.find((x) => x.id === id)
  closed.value = true
  if (!a) return (response.value = red(PA.LDPA_AUCTIONS_NOT_FOUND))
  // lances de quem não tem mais os pontos são apagados
  store.db.bids = store.db.bids.filter((b) => b.auction !== id || (accounts.find(b.account)?.points ?? 0) >= b.amount)
  closing.value = a
}
const bids = computed(() => (closing.value ? store.db.bids.filter((b) => b.auction === closing.value!.id).sort((a, b) => b.amount - a.amount) : []))
const top = computed(() => bids.value[0])
function saveClose() {
  const a = closing.value!
  const winner = top.value
  closing.value = null
  if (a.closed || !store.db.auctions.includes(a)) return (response.value = red(PA.LDPA_AUCTIONS_CLOSE_NOT_FOUND_OR_CLOSED))
  const account = winner && accounts.find(winner.account)
  if (account) account.points -= winner.amount
  Object.assign(a, { winner: winner?.account ?? '', winnerText: textPlayer.value, closed: true })
  response.value = blue(PA.LDPA_AUCTIONS_CLOSE_SUCCESS_CLOSED)
}

const statusOf = (a: Auction) => auctionStatus(a, {
  end: PA.LDPA_AUCTIONS_STATUS_END, waitBegin: PA.LDPA_AUCTIONS_STATUS_WAIT_BEGIN, inProgress: PA.LDPA_AUCTIONS_STATUS_IN_PROGRESS,
  waitPremium: PA.LDPA_AUCTIONS_STATUS_WAIT_PREMIUM, unknown: PA.LDPA_AUCTIONS_STATUS_ERROR,
})
const TITLES = { add: 'Adicionar Leilão', edit: 'Modificar Leilão', remove: 'Remover Leilão', close: 'Fechar Leilão' }
</script>

<template>
  <h1 style="margin-top: 20px;">{{ TITLES[mode] }}</h1>

  <div class="legend" style="margin-top: 25px; padding: 10px;">
    <h3 v-if="mode === 'add'" class="legend-title"><span style="font-size: 13px;">Preencha os dados abaixo:</span></h3>

    <!-- Adicionar / Modificar: o formulário -->
    <div v-if="(mode === 'add' && showForm) || (mode === 'edit' && editing)" class="quadrosOut">
      <form action="" method="post" enctype="multipart/form-data" @submit.prevent="mode === 'add' ? add() : save()">
        <em>{{ PA.LDPA_AUCTIONS_ADD_NAME_AUCTION }}:</em><br /><input v-model="form.name" name="name" type="text" class="inputbox" /> <br />
        <em>{{ PA.LDPA_AUCTIONS_ADD_PREMIUM }}:</em><br /><input v-model="form.premium" name="premium" type="text" class="inputbox" /> <br />
        <em>{{ PA.LDPA_AUCTIONS_ADD_PHOTO }}:</em><br /><template v-if="editing"><img :src="auctionPhoto(editing, 'small')" /><br /></template><input ref="photoInput" name="photo" type="file" class="inputbox" /> <br />
        <em>{{ PA.LDPA_AUCTIONS_ADD_DATE_BEGIN }}:</em><br /><input v-model="form.startDate" name="startDate" type="text" class="inputbox" /> {{ PA.LDPA_AUCTIONS_ADD_SAMPLE1 }} <br />
        <em>{{ PA.LDPA_AUCTIONS_ADD_DATE_END }}:</em><br /><input v-model="form.endDate" name="endDate" type="text" class="inputbox" /> {{ PA.LDPA_AUCTIONS_ADD_SAMPLE2 }} <br />
        <em>{{ PA.LDPA_AUCTIONS_ADD_ACTIVE }}:</em><input v-model="form.active" name="active" type="checkbox" class="inputbox" value="1" /> <br />
        <em>{{ PA.LDPA_AUCTIONS_ADD_MIN_BID }}:</em><br /><input v-model="form.minimumBid" name="minimumBid" type="text" class="inputbox" /> <br />
        <template v-if="editing"><input name="auction" type="hidden" :value="editing.id" />
          <input name="Write" type="hidden" value="true" />
        </template>
        <input type="submit" :value="mode === 'add' ? PA.LDPA_AUCTIONS_ADD_SUBMIT : PA.LDPA_AUCTIONS_EDIT_EDIT_SUBMIT" class="button" style="margin-top: 6px;" />
      </form>
      <div v-if="formError" class="qdestaques">{{ formError }}</div>
    </div>

    <!-- Fechar: os detalhes do leilão escolhido -->
    <div v-else-if="mode === 'close' && closing" class="quadrosOut">
      <table style="width: 100%;"><tbody><tr><td style="width: 150px;">{{ PA.LDPA_AUCTIONS_CLOSE_ID }}:</td><td><strong>{{ closing.id }}</strong></td></tr><tr><td>{{ PA.LDPA_AUCTIONS_CLOSE_NAME }}:</td><td><strong>{{ closing.name }}</strong></td></tr><tr><td>{{ PA.LDPA_AUCTIONS_CLOSE_STATUS }}:</td><td><strong>{{ statusOf(closing) }}</strong></td></tr><tr><td>{{ PA.LDPA_AUCTIONS_CLOSE_DATE_BEGIN }}:</td><td><strong>{{ date(closing.start) }}</strong></td></tr><tr><td>{{ PA.LDPA_AUCTIONS_CLOSE_DATE_END }}:</td><td><strong>{{ date(closing.end) }}</strong></td></tr><tr><td>{{ PA.LDPA_AUCTIONS_CLOSE_MIN_BID }}:</td><td><strong>{{ closing.minimumBid }} {{ POINTS_NAME }}</strong></td></tr><tr><td>{{ PA.LDPA_AUCTIONS_CLOSE_PREMIUM }}:</td><td><strong>{{ closing.premium }}</strong></td></tr><tr><td>{{ PA.LDPA_AUCTIONS_CLOSE_PHOTO }}:</td><td><a :id="`photoLarge_${closing.id}`" :href="auctionPhoto(closing, 'large')" @click.prevent="largePhoto = auctionPhoto(closing, 'large')">
        <img :src="auctionPhoto(closing, 'small')" /></a>
      </td></tr></tbody></table><br /><table style="width: 100%;"><tbody><tr><td style="width: 150px;">{{ PA.LDPA_AUCTIONS_CLOSE_TOTAL_PLAYERS }}:</td><td><strong>{{ bids.length }}</strong></td></tr><tr><td style="width: 150px;">{{ PA.LDPA_AUCTIONS_CLOSE_MAX_BID }}:</td><td><strong>{{ top?.account }} - {{ top?.amount }} {{ POINTS_NAME }}</strong></td></tr></tbody></table><br /><table style="width: 100%; text-align: center;"><tbody><tr><td colspan="3"><strong>{{ PA.LDPA_AUCTIONS_CLOSE_TOP_10_BID }}</strong></td></tr><tr><td style="width: 150px;">{{ PA.LDPA_AUCTIONS_CLOSE_USERNAME }}</td><td>{{ PA.LDPA_AUCTIONS_CLOSE_BID_IN }} {{ POINTS_NAME }}</td><td>{{ PA.LDPA_AUCTIONS_CLOSE_DATE_HOUR }}</td></tr><tr v-for="(b, i) in bids" :key="i"><td>{{ b.account }}</td><td>{{ b.amount }}</td><td>{{ b.lastBidDay }} {{ b.lastBidHour }}</td></tr></tbody></table><br /><form action="" method="post" @submit.prevent="saveClose"><table style="width: 100%; text-align: center;"><tbody><tr><td><strong>{{ PA.LDPA_AUCTIONS_CLOSE_TEXT_WINNER }}:</strong></td><td><input v-model="textPlayer" type="text" class="inputbox" name="textPlayer" style="width: 480px;" /></td></tr><tr><td colspan="2">
        <input name="auction" type="hidden" :value="closing.id" />
        <input name="winner" type="hidden" :value="top?.account ?? ''" />
        <input name="winnerAmount" type="hidden" :value="top?.amount ?? ''" />
        <input class="button" type="submit" :value="PA.LDPA_AUCTIONS_CLOSE_CLOSE_SAVE" /></td></tr></tbody></table></form>
      <FancyBox v-if="largePhoto" :src="largePhoto" @closed="largePhoto = null" />
    </div>

    <!-- as listas de escolha (Modificar, Remover, Fechar) ou a resposta -->
    <div v-else-if="mode !== 'add' && !response && !(mode === 'remove' && removed) && !(mode === 'close' && closed)" class="quadrosOut">
      <form action="" method="post" @submit.prevent="mode === 'edit' ? load() : mode === 'remove' ? remove() : details()">
        {{ mode === 'edit' ? PA.LDPA_AUCTIONS_EDIT_SELECT_AUCTION_FOR_EDIT : mode === 'remove' ? PA.LDPA_AUCTIONS_REMOVE_SELECT_AUCTION_FOR_REMOVE : PA.LDPA_AUCTIONS_CLOSE_SELECT_AUCTION_FOR_CLOSE }}:<br />
        <select v-model="selected" name="auction">
          <option v-for="a in (mode === 'close' ? open : all)" :key="a.id" :value="String(a.id)">{{ a.name }}</option>
        </select>
        <input type="submit" :value="mode === 'edit' ? PA.LDPA_AUCTIONS_EDIT_SUBMIT_LOAD : mode === 'remove' ? PA.LDPA_AUCTIONS_REMOVE_SUBMIT_REMOVE : PA.LDPA_AUCTIONS_CLOSE_INPUT_SELECT_CLOSE" class="button" style="margin-top: 6px;" />
      </form>
    </div>
    <div v-else class="quadrosOut" v-html="response"></div>
  </div>
</template>
