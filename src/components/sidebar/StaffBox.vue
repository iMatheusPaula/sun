<script setup lang="ts">
// Caixa EQUIPE: [Mostrar] revela a lista (ajax require=membersstaff no original).
import { ref } from 'vue'
import BoxMenu from '@/components/ui/BoxMenu.vue'
import { staff } from '@/data/site'
import { STAFF_OFFLINE, STAFF_ONLINE } from '@/lang/ldajax'

const open = ref(false)
</script>

<template>
  <BoxMenu title="EQUIPE">
    <ul class="commonArrow">
      <li v-show="!open" id="but"><a href="javascript:;" @click.prevent="open = true">[Mostrar]</a></li>
      <div v-show="open" id="equipe"><div class="quadros"><template v-for="m in staff" :key="m.name">&nbsp; - <strong>{{ m.name }}</strong> - <strong :class="m.online ? 'staffOnline' : 'staffOffline'">{{ m.online ? STAFF_ONLINE : STAFF_OFFLINE }}</strong><br /></template></div></div>
    </ul>
  </BoxMenu>
</template>

<style>
/* <style> que o .tpl imprimia dentro desta caixa; como vale para a página inteira, fica global */
div.quadros {
  color: #C5C0AD;
}
</style>
