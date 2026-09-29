<script setup lang="ts">
// ldRankings::geratePerfilCharacter (?type=6&name=)
import { computed } from 'vue'
import { findCharacter } from '@/data/characters'
import { guildOf } from '@/data/guilds'
import { NO_PHOTO } from '@/data/placeholders'
import { className, mapName, RANKING_STATS, VIP_NAMES } from '@/config/settings'
import * as L from '@/lang/ldrankings'

const props = defineProps<{ name: string }>()
const c = computed(() => findCharacter(props.name))
const guild = computed(() => (c.value ? guildOf(c.value.name) : undefined))
</script>

<template>
  <h1>{{ L.RANKING_RESULT_OF }} {{ name }}</h1>
  <div class="quadros">
    <table width="100%" border="0">
      <tbody>
        <tr v-if="!c"><td>{{ L.RANKING_CHARACTER_NOT_FOUND }}</td></tr>
        <tr v-else>
          <td width="25%" align="center" valign="middle" style="padding-top: 2px;">
            <p><img :src="c.photo ?? NO_PHOTO" alt="" width="100" height="100" style="border: 2px solid #222222;" /></p>
          </td>
          <td width="37%">
            <strong>{{ L.RANKING_CLASS }}:</strong> {{ className(c.class) }} <br />
            <strong>{{ L.RANKING_RESETS }}:</strong> {{ c.resets }} <br />
            <strong>{{ L.RANKING_LEVEL }}:</strong> {{ c.level }} <br />
            <strong>{{ L.RANKING_POINTS }}:</strong> {{ c.points }} <br />
            <template v-if="RANKING_STATS">
              <strong>{{ L.RANKING_MAP }}:</strong> {{ mapName(c.map) }} <br />
              <strong>{{ L.RANKING_MAP_POS_X }}:</strong> {{ c.x }} <br />
              <strong>{{ L.RANKING_MAP_POS_Y }}:</strong> {{ c.y }} <br />
            </template>
            <strong>{{ L.RANKING_ZEN }}:</strong> {{ c.zen }}
          </td>
          <td width="38%" valign="top">
            <template v-if="RANKING_STATS">
              <strong>{{ L.RANKING_STRENGHT }}:</strong> {{ c.strength }} <br />
              <strong>{{ L.RANKING_DEXTERITY }}:</strong> {{ c.dexterity }} <br />
              <strong>{{ L.RANKING_VITALITY }}:</strong> {{ c.vitality }} <br />
              <strong>{{ L.RANKING_ENERGY }}:</strong> {{ c.energy }} <br />
              <strong>{{ L.RANKING_LEADERSHIP }}:</strong> {{ c.leadership }} <br />
            </template>
            <strong>{{ L.RANKING_STATUS }}:</strong> {{ c.online ? L.RANKING_STATUS_ONLINE : L.RANKING_STATUS_OFFLINE }} <br />
            <strong>{{ L.RANKING_GUILD }}:</strong>
            <RouterLink v-if="guild" :to="{ path: '/rankings-guild', query: { name: guild.name } }">{{ guild.name }}</RouterLink>
            <template v-else>{{ L.RANKING_GUILD_EMPTY }}</template> <br />
            <strong>{{ L.RANKING_TYPE_ACCOUNT }}:</strong> {{ VIP_NAMES[c.vip] }}
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
