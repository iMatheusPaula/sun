<script setup lang="ts">
// loginaccessdanied.tpl.php: painel do GM/admin sem o privilégio (ldPanelAdmin::checkPermission)
import InnerLayout from '@/layouts/InnerLayout.vue'
</script>

<template>
  <InnerLayout location="LOGIN" subtitle="Acesso Negado">
    <div id="etc">
      <div class="qdestaques">Erro, você não tem permissão para acessar essa área do site.</div>
    </div>
  </InnerLayout>
</template>
