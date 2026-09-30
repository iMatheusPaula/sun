<script setup lang="ts">
// index.tpl.php: ldHome (notícias, fórum, tops, castle siege), chat (ajax chatrealtime) e módulo de screenshots.
import { computed } from 'vue'
import BoxMenu from '@/components/ui/BoxMenu.vue'
import HomeLayout from '@/layouts/HomeLayout.vue'
import { events, forumLink, forumTopics, trailerUrl } from '@/data/home'
import { guildLogo } from '@/data/placeholders'
import { characterRanking, guildRanking, HOME_TOPS } from '@/lib/rankings'
import { phpDate } from '@/lib/date'
import { screenshotImage, useScreenshotsStore } from '@/stores/screenshots'
import { useNoticesStore } from '@/stores/notices'
import { useDbStore } from '@/stores/db'
import { CASTLE_SIEGE, SCREENSHOTS } from '@/config/settings'
import * as A from '@/lang/ldajax'
import { CASTLE_SIEGE_OCCUPY_GUILD } from '@/lang/ldhome'

// $NOTICES['LAST'] = 10 notícias mais novas; $RANKING_HOME_CONFIGS['TOPAMOUNT'] = 4 (tops de personagens e guilds)
const TOP_AMOUNT = 4
const store = useDbStore()
const noticesStore = useNoticesStore()
const latestNews = computed(() => noticesStore.list.slice(0, 10))
const tops = computed(() => HOME_TOPS.map((row) => row.map((t) => ({
  ...t, entries: characterRanking(store.db, t.type, t.period, TOP_AMOUNT).map((c): [string, number] => [c.name, t.value(c)]),
}))))
const topGuilds = computed(() => guildRanking(store.db, TOP_AMOUNT))
// loadCastleSiege: CASTLE_OCCUPY 0 → "Sem dono"
const castleOwner = computed(() => (store.db.castle.occupied ? store.db.castle.owner : CASTLE_SIEGE_OCCUPY_GUILD))

// ajax chatrealtime: TOP 50 dos tipos de $CHAT_REAL_TIME['TYPES'] (todos ligados), da mensagem mais nova para a mais antiga
const CHAT_TYPES: [label: string, cls: string][] = [
  [A.CHAT_MSG_CHAT, 'chat'], [A.CHAT_MSG_PARTY, 'party'], [A.CHAT_MSG_GUILD, 'guild'], [A.CHAT_MSG_GLOBAL, 'msgglobal'],
  [A.CHAT_MSG_ANUNGUILD, 'anunguild'], [A.CHAT_MSG_WHISPER, 'whisper'], [A.CHAT_MSG_ALlIANCE, 'aliance'], [A.CHAT_MSG_GM, 'gamemaster'],
  [A.CHAT_MSG_POST, 'post'],
]
const chat = computed(() => store.db.chat.filter((m) => CHAT_TYPES[m.type]).sort((a, b) => b.id - a.id).slice(0, 50))

const shots = useScreenshotsStore()
const screenshots = computed(() => shots.ranked.slice(0, SCREENSHOTS.TOP_HOME))

const dotted = 'border-bottom-color:#C2CAD3; border-bottom-width:thin; border-bottom-style:dotted;'
const topCell = 'position: relative; clear: both;'
const legendBg = 'background-image: url(/images/common/temp/bg_pattern.jpg);'
const seeAll = 'text-align: right; background: none;'
</script>

<template>
  <HomeLayout>
    <div class="newsforum">
      <div class="nf_title">
        <h3 class="custom">Últimas notícias</h3>
      </div>
      <ul>
        <li v-for="n in latestNews" :key="n.id"><div :style="dotted"><RouterLink :to="{ path: '/noticia', query: { id: n.id } }">{{ n.subject }}</RouterLink> - [{{ phpDate('d/m/Y g:i a', n.date) }}]</div></li>
        <li><span class="w_date"><RouterLink to="/noticias">[Ver todas]</RouterLink></span></li>
      </ul>
    </div>

    <div class="newsforum">
      <div class="nf_title">
        <h3 class="custom">Últimas do forum</h3>
      </div>
      <ul>
        <li v-for="t in forumTopics" :key="t.id"><div :style="dotted"><a :href="`#topic-${t.id}`" target="_blank">{{ t.title }}</a></div></li>
        <li><span class="w_date"><a :href="forumLink" target="_blank">[Ver todas]</a></span></li>
      </ul>
    </div>

    <div class="newsforum">
      <div class="nf_title">
        <h3 class="custom">Chat do jogo em tempo real</h3>
        <a href="javascript: void(0);" style="margin-top: -30px;" @click.prevent>[Atualizar chat]</a>
      </div>
      <ul style="height: 150px; overflow: scroll;" id="chatRealTimeUl">
        <li v-for="m in chat" :key="m.id">
          <div class="chatList" :class="CHAT_TYPES[m.type]![1]">
            <strong>[{{ CHAT_TYPES[m.type]![0] }}][{{ m.hour }}] {{ m.name }}:</strong> {{ m.message }}
          </div>
        </li>
      </ul>
      <br />
    </div>

    <div class="newsforum">
      <div class="nf_title">
        <h3 class="custom">Tops players</h3>
      </div>
      <table style="width: 100%;" id="topsServerHome">
        <tbody>
          <tr v-for="(row, r) in tops" :key="r">
            <td v-for="(top, c) in row" :key="top.title" :width="c === 2 ? '34%' : '33%'" :style="topCell">
              <div class="legend">
                <h3 class="legend-title" :style="legendBg"><span style="font-size: 13px;">{{ top.title }}</span></h3>
                <!-- o id="notice" repetido é do original (só a primeira lista não tem) -->
                <ul :id="r === 0 && c === 0 ? undefined : 'notice'">
                  <li v-for="[name, value] in top.entries" :key="name"><RouterLink :to="{ path: '/rankings-personagem', query: { name } }">{{ name }}</RouterLink> - {{ value }}</li>
                  <li :style="seeAll"><RouterLink :to="top.ranking">[Ver ranking]</RouterLink></li>
                </ul>
              </div>
            </td>
          </tr>
          <tr>
            <td width="100%" colspan="3" :style="topCell">
              <div class="legend">
                <h3 class="legend-title" :style="legendBg"><span style="font-size: 13px;">Top Guilds:</span></h3>
                <br />
                <table>
                  <tbody>
                    <tr>
                      <td v-for="g in topGuilds" :key="g.name" align="center"><RouterLink :to="{ path: '/rankings-guild', query: { name: g.name } }"><strong>{{ g.name }}</strong><br /><img :src="guildLogo(g.mark)" width="100" height="100" /><br />Pontos: {{ g.score }}</RouterLink></td>
                    </tr>
                  </tbody>
                </table>
                <ul id="notice">
                  <li :style="seeAll"><RouterLink to="/rankings-guilds">[Ver ranking]</RouterLink></li>
                </ul>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="newsforum">
      <div class="nf_title">
        <h3 class="custom">Castle Siege</h3>
      </div>
      <div style="background: url(/images/common/temp/siege.png) no-repeat center center; height: 120px">
        <div style="position:relative; top:49px; left:290px; width: 200px;"><RouterLink :to="{ path: '/rankings-guild', query: { name: castleOwner } }">{{ castleOwner }}</RouterLink></div>
        <div style="position:relative; top:61px; left:290px; width: 200px;">{{ CASTLE_SIEGE.CONFRONTATION }}</div>
      </div>
    </div>

    <div class="newsforum">
      <div class="nf_title">
        <h3 class="custom">ScreenShots</h3>
      </div>
      <table style="width: 100%; margin: 5px; text-align: center;">
        <tbody>
          <tr>
            <td v-if="screenshots.length === 0">Nenhuma Screenshot foi enviada.</td>
            <td v-for="s in screenshots" :key="s.id"><RouterLink :to="{ path: '/screenshot', query: { id: s.id } }">{{ shots.author(s) }}<br /><img :src="screenshotImage(s, 'small')" :style="{ width: `${s.sw}px`, height: `${s.sy}px` }" /><br />Voto{{ s.rate > 0 ? 's' : '' }}: {{ s.rate }}</RouterLink></td>
          </tr>
          <tr>
            <td align="right" colspan="5"><RouterLink to="/screenshots">[Ver todas]</RouterLink></td>
          </tr>
        </tbody>
      </table>
    </div>

    <template #right>
      <BoxMenu title="Eventos">
        <ul style="text-align: center;">
          <li v-for="e in events" :key="e.name"><RouterLink to="/"><img :src="e.image" :alt="e.name" /></RouterLink></li>
        </ul>
      </BoxMenu>

      <ul class="rightbnr">
        <div class="adminarea">
          <div class="trailer">
            <div class="bgtop"></div>
            <div class="trailer_mov">
              <iframe width="206" height="167" :src="trailerUrl" title="Trailer" frameborder="0" allowfullscreen style="display:block;"></iframe>
            </div>
            <div class="bgbottom"></div>
          </div>
        </div>
      </ul>
    </template>
  </HomeLayout>
</template>

<style>
/* <style> que o .tpl imprimia no bloco do chat (vale para a página inteira) */
.chatList.chat {
  color: #000;
}
.chatList.party {
  color: #000;
  background-color: #4682B4;
}
.chatList.post, .chatList.whisper, .chatList.aliance {
  color: #000;
  background-color: #DCC17C;
}
.chatList.guild {
  color: #000;
  background-color: #76F6C5;
}
</style>
