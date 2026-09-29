<script setup lang="ts">
// ldRankings::geratePerfilGuild (?type=7&name=)
import { computed } from 'vue'
import { findGuild, type GuildMember } from '@/data/guilds'
import { findCharacter } from '@/data/characters'
import { className } from '@/config/settings'
import * as L from '@/lang/ldrankings'

const props = defineProps<{ name: string }>()
const g = computed(() => findGuild(props.name))

// G_Status 32 = Battle Master, 64 = Assistente, 128 = Guild Master; senão G_Level 1 = mestre
function position(m: GuildMember): { strong: boolean; text: string } {
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
            <p><img :src="g.logo" alt="" width="100" height="100" style="border: 2px solid #222222;" /></p>
          </td>
          <td width="75%">
            <strong>{{ L.RANKING_GUILD_SCORE }}</strong>: {{ g.score }} <br />
            <strong>{{ L.RANKING_GUILD_GM }}</strong>: <RouterLink :to="{ path: '/rankings-personagem', query: { name: g.master } }">{{ g.master }}</RouterLink> <br />
            <strong>{{ L.RANKING_TOTAL_MEMBERS }}</strong>: {{ g.members.length }} <br />
            <table width="100%" border="0" style="text-align: center;">
              <tbody>
                <tr>
                  <th width="26%" align="center" :style="th"><strong>{{ L.RANKING_CHARACTER }}</strong></th>
                  <th width="25%" align="center" :style="th"><strong>{{ L.RANKING_CLASS }}</strong></th>
                  <th width="23%" align="center" :style="th"><strong>{{ L.RANKING_RESETS }}</strong></th>
                  <th width="26%" align="center" :style="th"><strong>{{ L.RANKING_POSITION }}</strong></th>
                </tr>
                <tr v-for="m in g.members" :key="m.name">
                  <td><RouterLink :to="{ path: '/rankings-personagem', query: { name: m.name } }">{{ m.name }}</RouterLink></td>
                  <td>{{ className(findCharacter(m.name)?.class ?? 0) }}</td>
                  <td>{{ findCharacter(m.name)?.resets }}</td>
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
