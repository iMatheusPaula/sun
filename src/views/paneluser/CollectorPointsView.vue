<script setup lang="ts">
// paneluser[COLLECTOR_POINTS].tpl.php + ldPanelUser::loadOptionsCollectorPoints (?action=collect):
// troca cada unidade do item da settings no baú principal por pontos
import { ref } from 'vue'
import { usePanelAccount } from '@/lib/paneluser'
import { COLLECTOR_POINTS } from '@/config/panel'
import { itemName } from '@/data/itemdb'
import { sprintf } from '@/lang/format'
import * as P from '@/lang/ldpaneluser'

const { account, warehouse } = usePanelAccount()
const name = itemName(COLLECTOR_POINTS.idCategorie, COLLECTOR_POINTS.idItem)
const response = ref<{ cls: string; html: string } | null>(null)

function collect() {
  // o original deixava a tag vazia quando a conta estava online (return antes do set); aqui a mensagem aparece
  if (account.value!.online) return (response.value = { cls: 'qdestaques2', html: P.LDPU_YOU_MUST_BE_OFFLINE })
  const w = warehouse.value
  const isTarget = (i: { cat: number; idx: number }) => i.cat === COLLECTOR_POINTS.idCategorie && i.idx === COLLECTOR_POINTS.idItem
  const found = w ? w.items.filter(isTarget).length : 0
  if (found === 0) return (response.value = { cls: 'qdestaques', html: sprintf(P.LDPU_COLLECTOR_POINTS_ERROR, name) })
  const points = COLLECTOR_POINTS.premiumInPoints * found
  w!.items = w!.items.filter((i) => !isTarget(i))
  account.value!.points += points
  response.value = { cls: 'qdestaques2', html: sprintf(P.LDPU_COLLECTOR_POINTS_SUCCESS, found, name, points) }
}
</script>

<template>
  <h1 style="margin-top: 20px;">Coletor de pontos</h1>
  <br />

  <div id="etc">
    <div class="legend" style="margin-bottom: 25px;">
      <h3 class="legend-title"><span style="font-size: 13px;">Leia as instruçães abaixo:</span></h3>
      <div style="margin-bottom: 8px; margin-top: 10px;">
        O coletor de pontos é um serviço oferecido no site para trocar determinados itens do jogo por pontos no site.<br />
        Esses pontos podem ser utilizados nos eventos do site, como bolões, leilões e entre outros.<br /><br />

        <strong>O sistema atualmente, esta oferecendo <u>{{ COLLECTOR_POINTS.premiumInPoints }}</u> pontos por cada unidade que você dispor do item <u>{{ name }}</u> (não importa suas condições como level e etc.).</strong><br /><br />

        Caso você possua alguma unidade desse item no seu bau principal do jogo, clique no botão abaixo e converta os mesmos por pontos no site.<br /><br />
        <form action="" method="post" @submit.prevent="collect">
          <input type="submit" value="Coletar itens e converter em pontos" class="button" />
        </form>
        <br />
        <div v-if="response" :class="response.cls" v-html="response.html"></div>
      </div>
    </div>
  </div>
</template>
