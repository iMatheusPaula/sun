<script setup lang="ts">
// Entrada dos painéis. Painel do usuário sem sessão → loginerror (ldPanelUser);
// GM/admin sem privilégio → loginaccessdanied (checkPermission falha antes do teste de sessão).
// Com acesso, por enquanto a tela "em migração".
import { useSessionStore } from '@/stores/session'
import AccessDeniedView from './AccessDeniedView.vue'
import LoginErrorView from './LoginErrorView.vue'
import NotMigratedView from './NotMigratedView.vue'

defineProps<{ required: 0 | 1 | 2 }>()
const session = useSessionStore()
</script>

<template>
  <LoginErrorView v-if="required === 0 && !session.isLogged" />
  <AccessDeniedView v-else-if="session.privilege < required" />
  <NotMigratedView v-else />
</template>
