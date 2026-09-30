<script setup lang="ts">
// paneluser[VIRTUAL_VAULT].tpl.php + ldPanelUser::loadOptionsVirtualVault: o baú do jogo e o baú virtual
// (ExtWareHouseVirtual). Clicar num item mostra os detalhes (o HTML que o ajax devolvia) com o botão de
// enviar para o outro baú; offline no jogo, o item muda de lista como faziam os scripts da resposta.
import { computed, ref } from 'vue'
import { usePanelAccount } from '@/lib/paneluser'
import { excellentNames, findSlot, itemLabel, itemSize, serialHex } from '@/lib/items'
import { itemName } from '@/data/itemdb'
import type { ItemData } from '@/data/db'
import * as P from '@/lang/ldpaneluser'

const { store, login, account, warehouse } = usePanelAccount()
// o item que volta para o baú do jogo entrava no topo da lista (prepend) até a página ser recarregada
const arrived = ref<number[]>([])
const gameItems = computed(() => {
  const items = warehouse.value?.items ?? []
  const top = arrived.value.map((s) => items.find((i) => i.slot === s)).filter((i) => i !== undefined)
  return [...top, ...items.filter((i) => !arrived.value.includes(i.slot)).sort((a, b) => a.slot - b.slot)]
})
const virtualItems = computed(() => store.db.virtualVault.filter((v) => v.account === login.value).sort((a, b) => b.number - a.number))
const details = ref('')
const selected = ref<{ vault: 'game' | 'virtual'; key: number } | null>(null)

function detailsHtml(item: ItemData, to: 'virtual' | 'game'): string {
  const yesNo = (v: boolean) => (v ? P.LDPU_YES : P.LDPU_NOT)
  let html = '<table style="width: 100%"><tr><td>'
  html += `<em>${P.LDPU_VIRTUAL_VAULT_TEXT_ITEM}:</em> <strong>${itemName(item.cat, item.idx)}</strong><br />`
  html += `<em>${P.LDPU_VIRTUAL_VAULT_TEXT_LEVEL}:</em> <strong>+${item.level}</strong><br />`
  html += `<em>${P.LDPU_VIRTUAL_VAULT_TEXT_OPTION}:</em> <strong>+${item.option * 4}</strong><br />`
  html += `<em>${P.LDPU_VIRTUAL_VAULT_TEXT_LUCK}:</em> <strong>${yesNo(item.luck)}</strong><br />`
  html += `<em>${P.LDPU_VIRTUAL_VAULT_TEXT_SKILL}:</em> <strong>${yesNo(item.skill)}</strong><br />`
  html += `<em>${P.LDPU_VIRTUAL_VAULT_TEXT_ANCIENT}:</em> <strong>${P.LDPU_NOT}</strong><br />`
  html += `<em>${P.LDPU_VIRTUAL_VAULT_TEXT_DURABILITY}:</em> <strong>${item.durability}</strong><br />`
  html += `<em>${P.LDPU_VIRTUAL_VAULT_TEXT_SERIAL}:</em> <strong>0x${serialHex(item.serial)}</strong><br />`
  const excellents = excellentNames(item)
  if (excellents.length === 0) html += `<em>${P.LDPU_VIRTUAL_VAULT_TEXT_EXCELLENT}:</em> <strong>${P.LDPU_NOT}</strong><br />`
  else html += `<br /><em>${P.LDPU_VIRTUAL_VAULT_TEXT_EXCELLENT}:</em><strong><br />${excellents.map((e) => `<em> - ${e}</em><br />`).join('')}</strong>`
  html += `<br /><em>${P.LDPU_VIRTUAL_VAULT_TEXT_REFINE}:</em> <strong>${P.LDPU_NOT}</strong><br />`
  html += `<br /><em>${P.LDPU_VIRTUAL_VAULT_TEXT_HARMONY}:</em> <strong>${P.LDPU_NOT}</strong><br />`
  // as <em> dos sockets ficam abertas no original
  html += `<br /><em>${P.LDPU_VIRTUAL_VAULT_TEXT_SOCKET}:</em><br />`
  for (let i = 1; i <= 5; i++) html += `<em> - ${P.LDPU_VIRTUAL_VAULT_TEXT_SOCKET_NAME} ${i}: No Socket<br />`
  html += '</td><td align="right" valign="top">'
  html += to === 'virtual'
    ? `<input type="button" class="button" value="${P.LDPU_VIRTUAL_VAULT_TEXT_SEND_TO_VAULT_VIRTUAL}" />`
    : `<input type="button" class="button" value="${P.LDPU_VIRTUAL_VAULT_TEXT_SEND_TO_VAULT_GAME}" /><br />`
  return html + '</td></tr></table>'
}

function showGame(slot: number) {
  const item = warehouse.value?.items.find((i) => i.slot === slot)
  selected.value = { vault: 'game', key: slot }
  details.value = item ? detailsHtml(item, 'virtual') : P.LDPU_VIRTUAL_VAULT_ITEM_NOT_FOUND
}

function showVirtual(number: number) {
  const item = virtualItems.value.find((v) => v.number === number)
  selected.value = { vault: 'virtual', key: number }
  details.value = item ? detailsHtml(item, 'game') : P.LDPU_VIRTUAL_VAULT_ITEM_NOT_FOUND
}

// o botão "Enviar…" vem dentro do HTML dos detalhes
function onDetailsClick(e: MouseEvent) {
  if ((e.target as HTMLElement).tagName !== 'INPUT' || !selected.value) return
  const { vault, key } = selected.value
  if (account.value!.online) {
    details.value = `<div class='qdestaques2'>${P.LDPU_YOU_MUST_BE_OFFLINE}</div>`
    return
  }
  selected.value = null
  if (vault === 'game') sendToVirtual(key)
  else sendToGame(key)
}

function sendToVirtual(slot: number) {
  const w = warehouse.value
  const i = w?.items.findIndex((it) => it.slot === slot) ?? -1
  if (!w || i < 0) {
    details.value = P.LDPU_VIRTUAL_VAULT_ITEM_NOT_FOUND
    return
  }
  const { slot: _slot, ...item } = w.items.splice(i, 1)[0]!
  arrived.value = arrived.value.filter((s) => s !== slot)
  const number = Math.max(0, ...store.db.virtualVault.map((v) => v.number)) + 1 // IDENT_CURRENT
  store.db.virtualVault.push({ account: login.value, number, ...item })
  details.value = ''
}

function sendToGame(number: number) {
  const w = warehouse.value
  const i = store.db.virtualVault.findIndex((v) => v.account === login.value && v.number === number)
  if (!w || i < 0) {
    details.value = P.LDPU_VIRTUAL_VAULT_ITEM_NOT_FOUND
    return
  }
  const { account: _account, number: _number, ...item } = store.db.virtualVault[i]!
  const slot = findSlot(w.items, ...itemSize(item))
  if (slot < 0) {
    details.value = `<div class='qdestaques'>${P.LDPU_VIRTUAL_VAULT_TEXT_SEND_ERROR_NO_SPACE}</div>`
    return
  }
  store.db.virtualVault.splice(i, 1)
  w.items.push({ slot, ...item })
  arrived.value.unshift(slot)
  details.value = ''
}

</script>

<template>
  <h1 style="margin-top: 20px;">Baú virtual</h1>

  <div id="etc">
    <table>
      <tbody>
      <tr>
        <td style="margin: 0px; padding: 0px; background-color: transparent;">
          <div class="vaultBox">
            <div id="vaultName">Baú atual no jogo</div>
            <TransitionGroup id="itemsVaultGame" tag="ul" name="vault-fade">
              <li v-for="it in gameItems" :id="`liItemVaultGame_${it.slot}`" :key="it.slot" @click="showGame(it.slot)">{{ itemLabel(it) }}</li>
            </TransitionGroup>
          </div>
        </td>
        <td style="margin: 0px; padding: 0px; width: 100%; background-color: transparent;"></td>
        <td style="margin: 0px; padding: 0px; background-color: transparent;">
          <div class="vaultBox">
            <div id="vaultName">Baú virtual</div>
            <TransitionGroup id="itemsVaultVirtual" tag="ul" name="vault-fade">
              <li v-for="it in virtualItems" :id="`liItemVaultVirtual_${it.number}`" :key="it.number" @click="showVirtual(it.number)">{{ itemLabel(it) }}</li>
            </TransitionGroup>
          </div>
        </td>
      </tr>
      <tr>
        <td colspan="3" style="background-color: transparent;">
          <div id="detailsVault" @click="onDetailsClick" v-html="details"></div>
        </td>
      </tr>
      </tbody>
    </table>
  </div>
</template>

<style scoped>
/* jQuery fadeOut('slow') do item que sai da lista */
.vault-fade-leave-active { transition: opacity 0.6s; }
.vault-fade-leave-to { opacity: 0; }
</style>
