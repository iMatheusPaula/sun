<script setup lang="ts">
// Moldura das telas dos painéis do game master e do admin: menu (menuPanelGamemaster / menuPanelAdmin) +
// conteúdo, dentro de #etc.
import InnerLayout from './InnerLayout.vue'
import GameMasterMenu from '@/components/paneladmin/GameMasterMenu.vue'
import AdminMenu from '@/components/paneladmin/AdminMenu.vue'
import type { AdminPage } from '@/lib/paneladmin'

defineProps<{ page: AdminPage }>()
</script>

<template>
  <InnerLayout v-if="page === 'panelgamemaster'" location="PAINEL DO GAME MASTER" subtitle="Painel do game master">
    <div id="etc">
      <GameMasterMenu />
      <slot />
    </div>
  </InnerLayout>
  <InnerLayout v-else location="PAINEL DO ADMIN" subtitle="Painel do admin">
    <div id="etc">
      <AdminMenu />
      <slot />
    </div>
  </InnerLayout>
</template>
