<script setup lang="ts">
// menuLeft.tpl.php: coluna esquerda de todas as páginas. Contadores: @/lib/serverinfo (ldGeneral::__construct).
import BoxMenu from '@/components/ui/BoxMenu.vue'
import GaugeBar from '@/components/ui/GaugeBar.vue'
import LoginPanel from './LoginPanel.vue'
import PollBox from './PollBox.vue'
import StaffBox from './StaffBox.vue'
import { serverConfigs, servers } from '@/data/site'
import { useServerInfo } from '@/lib/serverinfo'

const { infos } = useServerInfo()
</script>

<template>
  <div id="mainleft">
    <LoginPanel />
    <div class="downloadbtn"><RouterLink to="/downloads"><img src="/images/common/temp/bt_sub_download.jpg" width="214" height="54" alt="DOWNLOAD" /></RouterLink></div>

    <BoxMenu title="SERVIDORES">
      <div class="gaugeDiv">
        <ul>
          <li v-for="s in servers" :key="s.name">{{ s.name }}: <RouterLink :to="{ path: '/onlines', query: { room: s.name } }">{{ s.percent }}</RouterLink>% <br />
            <GaugeBar :fill-id="s.name" :percent="s.percent" />
          </li>
          <li>Total online: <RouterLink to="/onlines"><strong>{{ infos.online }}</strong></RouterLink></li>
        </ul>
      </div>
    </BoxMenu>

    <BoxMenu title="CONFIGURAÇÕES">
      <ul class="commonArrow">
        <li v-for="[label, value] in serverConfigs" :key="label">{{ label }}: <strong>{{ value }}</strong></li>
      </ul>
    </BoxMenu>

    <BoxMenu title="INFORMAÇÕES">
      <ul class="commonArrow">
        <li>Total de Contas: <strong>{{ infos.accounts }}</strong></li>
        <li>Total de Chars: <strong>{{ infos.characters }}</strong></li>
        <li>Recorde Online: <strong>{{ infos.recordOnline }}</strong></li>
        <li v-for="[plan, count] in infos.vips" :key="plan">{{ plan }}: <strong>{{ count }}</strong></li>
        <li>Personagens Banidos: <RouterLink to="/banned-personagens"><strong>{{ infos.bannedCharacters }}</strong></RouterLink></li>
        <li>Contas Banidas: <RouterLink to="/banned-contas"><strong>{{ infos.bannedAccounts }}</strong></RouterLink></li>
      </ul>
    </BoxMenu>

    <PollBox />
    <StaffBox />
  </div>
</template>
