<script setup lang="ts">
// Rota ainda sem tela no app. Em dev, aponta para a página correspondente do kit (gabarito).
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import InnerLayout from '@/layouts/InnerLayout.vue'

const route = useRoute()
const reference = computed(() => `/reference${route.path}.html`)
const isDev = import.meta.env.DEV
</script>

<template>
  <InnerLayout location="EM MIGRAÇÃO" subtitle="Página em migração">
    <div id="etc">
      <div class="qdestaques2">
        A página <strong>{{ route.path }}</strong> ainda não foi migrada para o app.
        <template v-if="isDev"> Referência do kit: <a :href="reference" target="_blank">{{ reference }}</a></template>
      </div>
    </div>
  </InnerLayout>
</template>
