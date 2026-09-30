<script setup lang="ts">
// Moldura das telas do painel do usuário: menu (menuPanelUser.tpl.php) + conteúdo, dentro de #etc.
import InnerLayout from './InnerLayout.vue'
import PanelUserMenu from '@/components/paneluser/PanelUserMenu.vue'
import { usePanelAccount } from '@/lib/paneluser'

const { account } = usePanelAccount()
</script>

<template>
  <InnerLayout location="PAINEL DO USUÁRIO" subtitle="Painel do usuário">
    <div id="etc">
      <PanelUserMenu :name="account?.name ?? ''" />
      <slot />
    </div>
  </InnerLayout>
</template>
