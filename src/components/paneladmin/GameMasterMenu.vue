<script setup lang="ts">
// menuPanelGamemaster.tpl.php: AdminCenter e três grupos (AbreMenu abre um e fecha os outros)
import { onUnmounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { adminPath } from '@/lib/paneladmin'

const open = ref('')
// cada página do original (inclusive clicar de novo na mesma) vinha com os grupos fechados
onUnmounted(useRouter().afterEach(() => (open.value = '')))
const to = (option: string) => adminPath('panelgamemaster', option)
</script>

<template>
  <div id="menu_cp">
    <ul>
      <li><RouterLink to="/panelgamemaster">AdminCenter</RouterLink></li>
      <li><a href="javascript:;" @click.prevent="open = 'div1'">Gerenciar contas</a></li>
      <li><a href="javascript:;" @click.prevent="open = 'div2'">Gerenciar personagens</a></li>
      <li><a href="javascript:;" @click.prevent="open = 'div3'">Suporte</a></li>
    </ul>
  </div>

  <div id="div1" :style="{ display: open === 'div1' ? 'block' : 'none' }">
    <div id="menu_cp">
      <ul>
        <li><RouterLink :to="to('MANAGER_BAN_ACCOUNT')">Banir / Desbanir conta</RouterLink></li>
      </ul>
      <span id="option_permission_GMC"></span>
    </div>
  </div>

  <div id="div2" :style="{ display: open === 'div2' ? 'block' : 'none' }">
    <div id="menu_cp">
      <ul>
        <li><RouterLink :to="to('MANAGER_BAN_CHARACTER')">Banir / Desbanir personagem</RouterLink></li>
      </ul>
      <span id="option_permission_GP"></span>
    </div>
  </div>

  <div id="div3" :style="{ display: open === 'div3' ? 'block' : 'none' }">
    <div id="menu_cp">
      <ul>
        <li><RouterLink :to="to('COMPLAINTS')">Denuncias</RouterLink></li>
        <li><RouterLink :to="to('TICKETS_OPERATION')">Tickets Abertos</RouterLink></li>
        <li><RouterLink :to="to('TICKETS_COMPLETING')">Tickets Encerrados</RouterLink></li>
      </ul>
    </div>
  </div>
</template>
