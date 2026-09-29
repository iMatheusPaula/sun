<script setup lang="ts">
// Moldura das páginas internas: sidebar + #subright com a localização (breadcrumb) e o título.
import SiteFooter from '@/components/layout/SiteFooter.vue'
import SiteHeader from '@/components/layout/SiteHeader.vue'
import SideBar from '@/components/sidebar/SideBar.vue'

defineProps<{
  location: string
  subtitle?: string
  locationStyle?: string
}>()
</script>

<template>
  <div id="mainwrapper">
    <SiteHeader />
    <div id="subbody">
      <SideBar />
      <div id="subright">
        <div class="locationtitle">
          <div class="location">
            <RouterLink class="home" to="/">INÍCIO</RouterLink> &gt; <strong :style="locationStyle">{{ location }}</strong>
          </div>
          <h3 v-if="subtitle" class="subtitle">{{ subtitle }}</h3>
        </div>
        <div class="sub_wrap">
          <slot />
        </div>
      </div>
    </div>
    <div id="subbottomPanel"></div>
    <SiteFooter />
  </div>
</template>
