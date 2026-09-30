<script setup lang="ts">
// menuPanelUser.tpl.php: quatro grupos (AbreMenu abre um e fecha os outros) e, ao passar o mouse numa
// opção, o quadro "Permissões da Opção" (show_permission) com o que cada plano pode usar.
import { onUnmounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { ACTIVE_VIPS, CASH_NAME, CASH_NAME2, PANEL_PERMISSIONS, TITLE_SITE, VIP_NAMES, type PanelOption } from '@/config/settings'
import { optionPath } from '@/lib/paneluser'
import QuestionsEvent from './QuestionsEvent.vue'

defineProps<{ name: string }>()

const openGroup = ref(0)
// cada página do original (inclusive clicar de novo na mesma) vinha com os grupos fechados
onUnmounted(useRouter().afterEach(() => (openGroup.value = 0)))

type Area = 'GMC' | 'GP' | 'GSUP'
const hovered = ref<Record<Area, PanelOption | null>>({ GMC: null, GP: null, GSUP: null })

const groups: { area?: Area; items: [label: string, option: string, withPermission: boolean][] }[] = [
  {
    area: 'GMC',
    items: [
      ['Alterar Dados', 'MODIFY_DATA', true], ['Limpar Bau', 'CLEAN_VAULT', true], ['Bau Duplo', 'DOUBLE_VAULT', true],
      ['Bau virtual', 'VIRTUAL_VAULT', true], ['Log de Compras', 'LOG_BUYS', true], ['Alterar Personal ID', 'MODIFY_PERSONALID', true],
      ['Desconectar conta', 'GAME_DISCONNECT', true], ['Golden Archer', 'GOLDEN_ARCHER', true], ['Coletor de pontos', 'COLLECTOR_POINTS', true],
      ['Leilões', 'AUCTIONS', true], [`Transferir ${CASH_NAME}/${CASH_NAME2}`, 'TRANSFER_CASH', false], ['Screenshots', 'SCREENSHOT', false],
    ],
  },
  {
    area: 'GP',
    items: [
      ['Resetar Personagem', 'RESET', true], ['Master Reset', 'MASTER_RESET', true], ['Transferir Resets', 'RESET_TRANSFER', true],
      ['Limpar PK', 'CLEAN_PK', true], ['Distribuir Pontos', 'DISTRIBUTE_POINTS', true], ['Mover Personagem', 'MOVE_CHARACTER', true],
      ['Mudar Nick', 'CHANGE_NICK', true], ['Mudar Classe', 'CHANGE_CLASS', true], ['Redistribuir Pontos', 'REDISTRIBUTE_POINTS', true],
      ['Limpar Inventário', 'CLEAN_INVENTORY', true], ['Gerenciar foto', 'MANAGER_PHOTO', true],
    ],
  },
  { items: [['Comprar Vips', 'BUY_VIPS', false], ['Confirmar Pagamento', 'CONFIRM_PAYMENT', false]] },
  {
    area: 'GSUP',
    items: [
      ['Abrir Denuncias', 'OPEN_COMPLAINTS', true], ['Abrir Pedidos', 'OPEN_TICKET', true],
      ['Ver Pedidos', 'READ_TICKET', true], ['Enviar SMS para os ADMs', 'SEND_SMS', true],
    ],
  },
]

// show_permission: Free + planos ativos, "Sim" verde / "Não" vermelho
const permissionOf = (option: PanelOption) => [0, ...ACTIVE_VIPS].map((plan) => ({
  name: VIP_NAMES[plan],
  allowed: PANEL_PERMISSIONS[option][plan + 1] === 1,
  last: plan === ACTIVE_VIPS[ACTIVE_VIPS.length - 1],
}))

function show(area: Area | undefined, option: string, withPermission: boolean) {
  if (area && withPermission) hovered.value[area] = option as PanelOption
}
function clear(area: Area | undefined) {
  if (area) hovered.value[area] = null
}
</script>

<template>
  <div id="menu_cp">
    <ul>
      <li><a href="javascript:;" @click.prevent="openGroup = 1">Gerenciar Minha Conta</a></li>
      <li><a href="javascript:;" @click.prevent="openGroup = 2">Gerenciar Personagens</a></li>
      <li><a href="javascript:;" @click.prevent="openGroup = 3">Shop</a></li>
      <li><a href="javascript:;" @click.prevent="openGroup = 4">Suporte</a></li>
    </ul>
  </div>

  <!-- AbreMenu grava display block/none no grupo; um <li> por linha no .tpl (o espaço conta no menu em linha) -->
  <div v-for="(group, g) in groups" :id="`div${g + 1}`" :key="g" :style="{ display: openGroup === g + 1 ? 'block' : 'none' }">
    <div id="menu_cp">
      <ul>
        <li v-if="g === 0"><RouterLink to="/paneluser">Minha Conta</RouterLink></li>
        <template v-for="[label, option, withPermission] in group.items" :key="option"><li><RouterLink :to="optionPath(option)" @mouseover="show(group.area, option, withPermission)" @mouseout="clear(group.area)">{{ label }}</RouterLink></li>{{ ' ' }}</template>
      </ul>
      <span v-if="group.area" :id="`option_permission_${group.area}`"><div v-if="hovered[group.area]" style="margin-left: 20px; margin-top: 10px; margin-bottom: 10px;">Permissões da Opção:<br />
        <template v-for="p in permissionOf(hovered[group.area]!)" :key="p.name"> - {{ p.name }}: <span v-if="p.allowed" style="color:#009400">Sim</span><span v-else style="color:#BB0000">Não</span><br v-if="!p.last" /></template></div></span>
    </div>
  </div>

  <QuestionsEvent :name="name" :title="TITLE_SITE" />
</template>
