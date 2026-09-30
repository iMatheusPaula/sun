<script setup lang="ts">
// paneluser[GOLDEN_ARCHER].tpl.php + ldPanelUser::loadOptionsGoldenArcher: registra um serial (dos eventos,
// como o de perguntas) e coloca o item do prêmio no baú do jogo. O ajax action=check respondia em #serialResponse.
import { ref } from 'vue'
import { usePanelAccount } from '@/lib/paneluser'
import { findSlot, itemDurability, itemSize, nextItemSerial } from '@/lib/items'
import { itemInfo } from '@/data/itemdb'
import * as P from '@/lang/ldpaneluser'

const { store, login, account, warehouse } = usePanelAccount()
const serial = ref('0000-0000-0000')
const response = ref('')

// action=check: devolve a mensagem de erro, ou mostra o sucesso e devolve undefined
function register(): string | undefined {
  if (account.value!.online) return P.LDPU_YOU_MUST_BE_OFFLINE
  if (serial.value === '' || serial.value === '0') return P.LDPU_GOLDEN_ARCHER_FILL_SERIAL // empty()
  const code = serial.value.replace(/-/g, '')
  if (code.length !== 12) return P.LDPU_GOLDEN_ARCHER_INVALID_SERIAL
  const parts = [code.slice(0, 4), code.slice(4, 8), code.slice(8, 12)].map((p) => p.toUpperCase())
  // o SQL Server compara sem diferenciar maiúsculas
  const row = store.db.goldenArcher.find((g) => g.serial.every((p, i) => p.toUpperCase() === parts[i]))
  if (!row) return P.LDPU_GOLDEN_ARCHER_INVALID_SERIAL
  if (row.account !== login.value) return P.LDPU_GOLDEN_ARCHER_SERIAL_NOT_BELONG
  if (row.status === 1) return P.LDPU_GOLDEN_ARCHER_SERIAL_IS_ACTIVE
  const info = itemInfo(row.item.cat, row.item.idx)
  if (!info) return P.LDPU_GOLDEN_ARCHER_ITEM_NOT_EXISTS
  const w = warehouse.value
  if (!w) return P.LDPU_GOLDEN_ARCHER_ACCOUNT_NOT_VAULT

  // ldItemMake::makeHexItem sem durabilidade base; serial novo do servidor
  const { item } = row
  const slot = findSlot(w.items, ...itemSize(item))
  if (slot === -1) return P.LDPU_GOLDEN_ARCHER_VAULT_NOT_SPACE
  const durability = itemDurability(item.cat, item.idx, 0, item.level, item.excellent.some(Boolean))
  w.items.push({ slot, ...item, excellent: [...item.excellent], durability, serial: nextItemSerial(store.db) })
  row.status = 1

  response.value = `<p class="yellow">${P.LDPU_GOLDEN_ARCHER_REGISTER_SUCCESS}</p><p class="yellow">${P.LDPU_GOLDEN_ARCHER_ITEM_RECEIVER}: ${info[0]}</p>`
  return undefined
}

function submit() {
  const error = register()
  if (error !== undefined) response.value = `<p class="white">${error}</p>`
}
</script>

<template>
  <h1 style="margin-top: 20px;">Golden Archer</h1>

  <div id="etc">
    <table>
      <tbody>
      <tr>
        <td style="margin: 0px; padding: 0px; background-color: transparent;">
          <form id="goldeArcherForm" @submit.prevent="submit">
            <div class="goldenArcherBox">
              <div id="goldenArcherName">Golden Archer</div>
              <div id="serialText">
                <p>Aqui você pode registrar os seriais que você possui em troca de vários itens!</p>
                <p>&nbsp;</p>
                <p>Os seriais podem ser obtidos em:</p>
                <p>- Eventos no site</p>
                <p>- Eventos no jogo</p>
                <p>- Eventos no forum</p>
                <p>E também pode ser concedidos como brindes por compras de golds e vips!</p>
                <p>&nbsp;</p>
                <p>Os itens aqui obtidos através dos seriais serão colocados no seu baú 0 do jogo.</p>
                <p>&nbsp;</p>
                <p>Os seriais são únicos! Ou seja, seu serial não pode ser registrado pelo login do seu amigo, ele pertence somente a você!</p>
                <p>&nbsp;</p>
                <p class="goldText bold">Digite o seu serial abaixo,<br />depois clique em Registrar serial.</p>
                <p>&nbsp;</p>
                <p class="goldText">Por favor tenha certeza de diferenciar a letra O e número 0, a letra I e numero 1.</p>
              </div>
              <div id="serialInput"><input id="serial" v-model="serial" type="text" name="serial" maxlength="14" @keyup="serial = serial.toUpperCase()"></div>
              <div id="serialCheck"><input type="submit" value="Registrar serial"></div>
              <div id="serialResponse" v-html="response"></div>
            </div>
          </form>
        </td>
      </tr>
      </tbody>
    </table>
  </div>
</template>
