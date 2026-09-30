<script setup lang="ts">
// ldRankings::geratePerfilGuild (?type=7&name=)
import { computed } from 'vue'
import { useDbStore } from '@/stores/db'
import { findCharacter, findGuild, guildMembers } from '@/lib/rankings'
import { guildLogo } from '@/data/placeholders'
import type { GuildMemberRow } from '@/data/db'
import { className } from '@/config/settings'
import * as L from '@/lang/ldrankings'

const props = defineProps<{ name: string }>()
const store = useDbStore()
const g = computed(() => findGuild(store.db, props.name))
const members = computed(() => guildMembers(store.db, props.name))
const char = (name: string) => findCharacter(store.db, name)

// G_Status 32 = Battle Master, 64 = Assistente, 128 = Guild Master; senão G_Level 1 = mestre
function position(m: GuildMemberRow): { strong: boolean; text: string } {
  if (m.status === 32) return { strong: true, text: L.RANKING_GUILD_BM }
  if (m.status === 64) return { strong: true, text: L.RANKING_GUILD_ASSISTANT }
  if (m.status === 128 || m.level === 1) return { strong: true, text: L.RANKING_GUILD_GM }
  return { strong: false, text: L.RANKING_GUILD_MEMBBER }
}

const th = 'background: #E4E1CB; padding: 1px 5px;'
</script>

<template>
  <h1>{{ L.RANKING_RESULT_OF }} {{ name }}</h1>
  <div class="quadros">
    <table width="100%" border="0">
      <tbody>
        <tr v-if="!g"><td>{{ L.RANKING_GUILD_NOT_FOUND }}</td></tr>
        <tr v-else>
          <td width="25%" align="center" valign="middle" style="padding-top: 2px;">
            <p><img :src="guildLogo(g.mark)" alt="" width="100" height="100" style="border: 2px solid #222222;" /></p>
          </td>
          <td width="75%">
            <strong>{{ L.RANKING_GUILD_SCORE }}</strong>: {{ g.score }} <br />
            <strong>{{ L.RANKING_GUILD_GM }}</strong>: <RouterLink :to="{ path: '/rankings-personagem', query: { name: g.master } }">{{ g.master }}</RouterLink> <br />
            <strong>{{ L.RANKING_TOTAL_MEMBERS }}</strong>: {{ members.length }} <br />
            <table width="100%" border="0" style="text-align: center;">
              <tbody>
                <tr>
                  <th width="26%" align="center" :style="th"><strong>{{ L.RANKING_CHARACTER }}</strong></th>
                  <th width="25%" align="center" :style="th"><strong>{{ L.RANKING_CLASS }}</strong></th>
                  <th width="23%" align="center" :style="th"><strong>{{ L.RANKING_RESETS }}</strong></th>
                  <th width="26%" align="center" :style="th"><strong>{{ L.RANKING_POSITION }}</strong></th>
                </tr>
                <tr v-for="m in members" :key="m.name">
                  <td><RouterLink :to="{ path: '/rankings-personagem', query: { name: m.name } }">{{ m.name }}</RouterLink></td>
                  <td>{{ className(char(m.name)?.class ?? 0) }}</td>
                  <td>{{ char(m.name)?.resets }}</td>
                  <td><strong v-if="position(m).strong">{{ position(m).text }}</strong><template v-else>{{ position(m).text }}</template></td>
                </tr>
              </tbody>
            </table>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
