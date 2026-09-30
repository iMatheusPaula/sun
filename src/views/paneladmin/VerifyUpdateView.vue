<script setup lang="ts">
// paneladmin[VERIFY_UPDATE].tpl.php + ldPanelAdmin::optionLoadVerifyUpdate: a lista de arquivos vinha do servidor
// do autor do MuSite, que não existe mais (o kit também mostra que não há atualizações); ?Download=true baixava os
// arquivos diferentes. Sem lista, nunca há o que atualizar.
import * as PA from '@/lang/ldpaneladmin'

const result = `<div class='qdestaques2'><em><strong>${PA.LDPA_VERIFY_UPDATE_NOT_AVAILABLE}</strong></em></div>`
</script>

<template>
  <h1 style="margin-top: 20px;">Verificar atualização</h1>
  <br />
  <div class="legend" style="margin-bottom: 25px;">
    <h3 class="legend-title"><span style="font-size: 13px;">Procurando atualizações para o site:</span></h3>
    <div style="margin-bottom: 8px; margin-top: 10px;">
      <div id="logs_" class="quadrosOut" v-html="result"></div>
    </div>
  </div>
</template>
