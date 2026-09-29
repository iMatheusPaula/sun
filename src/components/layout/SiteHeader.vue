<script setup lang="ts">
import { mainMenu } from '@/data/site'
</script>

<template>
  <div class="mainvisual">
    <div class="centerimages"></div>
    <div class="leftimages"></div>
    <div class="rightimages"></div>
  </div>

  <div id="mainheader">
    <div class="menu">
      <ul>
        <li v-for="(item, i) in mainMenu" :key="item.label" :style="i === mainMenu.length - 1 ? 'margin-right: 0px;' : undefined">
          <a v-if="item.external" :href="item.to">{{ item.label }}</a>
          <RouterLink v-else :to="item.to">{{ item.label }}</RouterLink>
        </li>
      </ul>
    </div>
    <div class="mainBtnStartGame">
      <div class="main_visual_obj"></div>
    </div>
  </div>
</template>
