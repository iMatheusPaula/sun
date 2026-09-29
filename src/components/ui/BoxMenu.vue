<script setup lang="ts">
defineProps<{ title: string }>()
</script>

<template>
  <div class="boxMenu">
    <div class="title"><h2>{{ title }}</h2></div>
    <div class="contentBox">
      <slot />
    </div>
    <div class="end"></div>
  </div>
</template>
