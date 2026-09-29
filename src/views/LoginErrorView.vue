<script setup lang="ts">
// loginerror.tpl.php: o que ldPanelUser abre sem sessão (e ?page=loginerror)
import InnerLayout from '@/layouts/InnerLayout.vue'
</script>

<template>
  <InnerLayout location="LOGIN" subtitle="Efetue o Login">
    <div id="etc">
      <div class="qdestaques">Você precisa estar logado para acessar essa página.</div>
    </div>
  </InnerLayout>
</template>
