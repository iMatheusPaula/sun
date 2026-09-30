<script setup lang="ts">
// menuPanelAdmin.tpl.php: AdminCenter, o ldManager (ferramenta à parte, fora do template) e doze grupos
// (AbreMenu abre um e fecha os outros). A ordem dos grupos no HTML não é a mesma dos links.
import { onUnmounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { CASH_NAME, CASH_NAME2 } from '@/config/settings'
import { adminPath } from '@/lib/paneladmin'

const open = ref('')
// cada página do original (inclusive clicar de novo na mesma) vinha com os grupos fechados
onUnmounted(useRouter().afterEach(() => (open.value = '')))
const to = (option: string) => adminPath('paneladmin', option)

const toggles: [div: string, label: string][] = [
  ['div1', 'Gerenciar banco de dados'], ['div11', 'Ferramentas para o jogo'], ['div2', 'Gerenciar contas'],
  ['div3', 'Gerenciar personagens'], ['div4', 'Gerenciar vips'], ['div5', 'Gerenciar noticias'], ['div10', 'Gerenciar enquetes'],
  ['div6', `Gerenciar ${CASH_NAME}`], ['div7', 'Gerenciar Depósitos'], ['div12', 'Gerenciar leilões'], ['div8', 'Suporte'], ['div9', 'Outros'],
]

const groups: { div: string; permission?: string; items: [option: string, label: string][] }[] = [
  { div: 'div1', permission: 'GMC', items: [['GERATE_BACKUPS', 'Gerar backups']] },
  {
    div: 'div11', permission: 'GMC', items: [
      ['GAME_MSG_ALL', 'Enviar mensagem global para todos online'], ['GAME_MSG_SPECIFIC', 'Enviar mensagem para determinados logins online'],
      ['GAME_DISCONNECT', 'Desconectar contas onlines'], ['GAME_CHAT_SERVER', 'Ver Chat em tempo real'],
    ],
  },
  {
    div: 'div12', permission: 'GMC', items: [
      ['ADD_AUCTIONS', 'Adicionar leilão'], ['EDIT_AUCTIONS', 'Modificar leilão'], ['DELETE_AUCTIONS', 'Remover leilão'], ['CLOSE_AUCTIONS', 'Fechar leilão'],
    ],
  },
  {
    div: 'div2', permission: 'GP', items: [
      ['EDIT_ACCOUNT', 'Editar conta'], ['DELETE_ACCOUNT', 'Deletar conta'], ['MANAGER_BAN_ACCOUNT', 'Banir / Desbanir conta'],
      ['MANAGER_ACCOUNTS_TRANSFER_CASH', `Liberar / Travar conta para transferir ${CASH_NAME}/${CASH_NAME2}`],
    ],
  },
  { div: 'div3', items: [['EDIT_CHARACTER', 'Editar personagem'], ['DELETE_CHARACTER', 'Deletar personagem'], ['MANAGER_BAN_CHARACTER', 'Banir / Desbanir personagem']] },
  { div: 'div4', items: [['ADD_VIP', 'Adicionar Vips'], ['DELETE_VIP', 'Remover Vips'], ['TRANSFORM_VIP', 'Transformar plano do vip']] },
  { div: 'div5', items: [['ADD_NOTICE', 'Adicionar noticia'], ['REMOVE_NOTICE', 'Remover noticia'], ['MODIFY_NOTICE', 'Alterar noticia']] },
  { div: 'div10', items: [['ADD_POLL', 'Adicionar enquete'], ['REMOVE_POLL', 'Remover enquete'], ['MODIFY_POLL', 'Alterar enquete']] },
  { div: 'div6', items: [['ADD_CASH', `Adicionar ${CASH_NAME}`], ['REMOVE_CASH', `Remover ${CASH_NAME}`]] },
  { div: 'div7', items: [['DEPOSITS_IN_OPERATION', 'Depósitos em andamento'], ['DEPOSITS_COMPLETING', 'Depósitos concluidos'], ['DEPOSITS_FALSE', 'Depósitos falsos']] },
  { div: 'div8', items: [['COMPLAINTS', 'Denuncias'], ['TICKETS_OPERATION', 'Tickets Abertos'], ['TICKETS_COMPLETING', 'Tickets Encerrados']] },
  { div: 'div9', items: [['VERIFY_UPDATE', 'Verificar atualização'], ['GOLDEN_ARCHER', 'Golden Archer']] },
]
</script>

<template>
  <div id="menu_cp">
    <ul>
      <li><RouterLink to="/paneladmin">AdminCenter</RouterLink></li>
      <li><a href="#">ldManager</a></li>
      <!-- um <li> por linha no .tpl: o espaço entre eles conta no layout em linha do menu -->
      <template v-for="[div, label] in toggles" :key="div"><li><a href="javascript:;" @click.prevent="open = div">{{ label }}</a></li>{{ ' ' }}</template>
    </ul>
  </div>

  <!-- AbreMenu grava display block/none no grupo -->
  <div v-for="g in groups" :id="g.div" :key="g.div" :style="{ display: open === g.div ? 'block' : 'none' }">
    <div id="menu_cp">
      <ul>
        <template v-for="[option, label] in g.items" :key="option"><li><RouterLink :to="to(option)">{{ label }}</RouterLink></li>{{ ' ' }}</template>
      </ul>
      <span v-if="g.permission" :id="`option_permission_${g.permission}`"></span>
    </div>
  </div>
</template>
