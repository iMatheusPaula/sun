<script setup lang="ts">
// index.tpl.php: ldHome (notícias, fórum, tops, castle siege), chat (ajax chatrealtime) e módulo de screenshots.
import { computed } from 'vue'
import BoxMenu from '@/components/ui/BoxMenu.vue'
import HomeLayout from '@/layouts/HomeLayout.vue'
import { castleOwner, chatMessages, events, forumLink, forumTopics, topLists, trailerUrl } from '@/data/home'
import { screenshotThumb } from '@/data/placeholders'
import { guildRanking } from '@/lib/rankings'
import { phpDate } from '@/lib/date'
import { useScreenshotsStore } from '@/stores/screenshots'
import { useNoticesStore } from '@/stores/notices'
import { CASTLE_SIEGE, SCREENSHOTS } from '@/config/settings'

// $NOTICES['LAST'] = 10 notícias mais novas; $RANKING_HOME_CONFIGS['TOPAMOUNT'] = 4 guilds
const noticesStore = useNoticesStore()
const latestNews = computed(() => noticesStore.list.slice(0, 10))
const topGuilds = guildRanking(4)
const shots = useScreenshotsStore()
const screenshots = computed(() => shots.ranked().slice(0, SCREENSHOTS.TOP_HOME))

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
        <li v-for="(m, i) in chatMessages" :key="i">
          <div class="chatList" :class="m.type">
            <strong>[{{ m.label }}][{{ m.time }}] {{ m.name }}:</strong> {{ m.text }}
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
          <tr v-for="(row, r) in topLists" :key="r">
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
                      <td v-for="g in topGuilds" :key="g.name" align="center"><RouterLink :to="{ path: '/rankings-guild', query: { name: g.name } }"><strong>{{ g.name }}</strong><br /><img :src="g.logo" width="100" height="100" /><br />Pontos: {{ g.score }}</RouterLink></td>
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
            <td v-for="s in screenshots" :key="s.id"><RouterLink :to="{ path: '/screenshot', query: { id: s.id } }">{{ s.author }}<br /><img :src="screenshotThumb" style="width: 90px; height: 68px;" /><br />Voto{{ s.rate > 0 ? 's' : '' }}: {{ s.rate }}</RouterLink></td>
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
