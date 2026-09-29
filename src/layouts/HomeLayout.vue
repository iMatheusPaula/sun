<script setup lang="ts">
// index.tpl.php: moldura da home — três colunas em #mainbody.
import SiteFooter from '@/components/layout/SiteFooter.vue'
import SiteHeader from '@/components/layout/SiteHeader.vue'
import SideBar from '@/components/sidebar/SideBar.vue'
</script>

<template>
  <div id="mainwrapper">
    <SiteHeader />
    <div id="mainbody">
      <SideBar />
      <div id="maincenterTop"></div>
      <div id="maincenter">
        <slot />
      </div>
      <div id="mainright">
        <slot name="right" />
      </div>
    </div>
    <div id="subbottom"></div>
    <SiteFooter />
  </div>
</template>
