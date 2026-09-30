<script setup lang="ts">
// panelgamemaster.tpl.php / paneladmin.tpl.php + ldPanelAdmin::optionLoadAdminCenter. Os totais são os da caixa
// INFORMAÇÕES da sidebar; no admin cada linha tem o [Sincronizar] (VIPs vencidos / banimentos vencidos).
import { serverEnv } from '@/data/site'
import { useAdminPage } from '@/lib/paneladmin'
import { useServerInfo } from '@/lib/serverinfo'

const page = useAdminPage()
const { infos: serverInfos } = useServerInfo()
const sync = (type: 1 | 2) => ({ path: '/paneladmin-sincronize', query: { type } })
</script>

<template>
  <h1 style="margin-top: 20px;">AdminCenter</h1>

  <div class="legend" style="margin-top: 25px;">
    <h3 class="legend-title"><span style="font-size: 13px;">Informações do computador.</span></h3>
    <ul style="margin: 10px 0px 10px 0px;">
      <li>Sistema operacional base: <strong>{{ serverEnv.os }}</strong></li>
      <li>Software versão: <strong>{{ serverEnv.software }}</strong></li>
      <li>Administrador do servidor web: <strong>{{ serverEnv.admin }}</strong></li>
    </ul>
  </div>

  <div class="legend" style="margin-top: 25px;">
    <h3 class="legend-title"><span style="font-size: 13px;">Informações do banco de dados.</span></h3>
    <ul style="margin: 10px 0px 10px 0px;">
      <li>Total de contas: <strong>{{ serverInfos.accounts }}</strong></li>
      <li>Total de Personagens: <strong>{{ serverInfos.characters }}</strong></li>
      <li>Recorde Online: <strong>{{ serverInfos.recordOnline }}</strong></li>
      <template v-if="page === 'paneladmin'">
        <li v-for="[plan, count] in serverInfos.vips" :key="plan">{{ plan }}: <strong>{{ count }}</strong> <RouterLink :to="sync(1)">[Sincronizar]</RouterLink></li>
        <li>Personagens Banidos: <strong>{{ serverInfos.bannedCharacters }}</strong> <RouterLink :to="sync(2)">[Sincronizar]</RouterLink></li>
        <li>Contas Banidas: <strong>{{ serverInfos.bannedAccounts }}</strong> <RouterLink :to="sync(2)">[Sincronizar]</RouterLink></li>
      </template>
      <template v-else>
        <li v-for="[plan, count] in serverInfos.vips" :key="plan">{{ plan }}: <strong>{{ count }}</strong></li>
        <li>Personagens Banidos: <RouterLink to="/banned-personagens"><strong>{{ serverInfos.bannedCharacters }}</strong></RouterLink></li>
        <li>Contas Banidas: <RouterLink to="/banned-contas"><strong>{{ serverInfos.bannedAccounts }}</strong></RouterLink></li>
      </template>
    </ul>
  </div>
</template>
