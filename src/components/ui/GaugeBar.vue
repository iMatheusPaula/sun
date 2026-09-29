<script setup lang="ts">
// Barra de ldGeneral (servidores) e ldAjax::loadPoll (resultado da enquete).
// O id="gauge" repetido e o id do <img> são do original; ficam até a fase de limpeza.
defineProps<{ fillId: string; percent: number }>()
</script>

<template>
  <div class="gauge" id="gauge" style="width:150px; height:10px;">
    <div class="top"></div>
    <div class="content" style="height:8px;"><img :id="fillId" class="filling" src="/images/gauge.png" :style="{ width: `${percent}%`, height: '13px', border: 'none' }" alt="" title="" /></div>
    <div class="bottom"></div>
  </div>
</template>
