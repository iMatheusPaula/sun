<script setup lang="ts">
// paneladmin[GOLDEN_ARCHER].tpl.php + ldPanelAdmin::optionLoadGoldenArcher: ?action=createSerial abre o formulário
// do item (todos os itens do item.txt, level, adicional, excelentes, ancient, refine, harmony e sockets) e Criar
// serial gera o serial para o login, que ele registra no Golden Archer do painel. Como no original, o harmony lista
// as opções de arma três vezes (getHarmony das famílias 1, 2 e 3 cai sempre em armas) e o login não é conferido.
import { reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useDbStore } from '@/stores/db'
import { isEmpty } from '@/lib/paneladmin'
import { ITEM_DB } from '@/data/itemdb'
import { HARMONY, SOCKET_EMPTY, SOCKET_NOT, SOCKETS } from '@/data/itemoptions'

const route = useRoute()
const store = useDbStore()
const creating = route.query.action === 'createSerial'

const items = Object.entries(ITEM_DB).flatMap(([cat, list]) => Object.entries(list).map(([idx, [name]]) => [`${cat},${idx}`, name] as const))
const harmony = [1, 2, 3].flatMap(() => HARMONY[1].flatMap((levels, type) => levels.map((text, level) => [`${type},${level}`, text] as const)))
// valor = tipo + (slot - 1) * 50; o texto traz o valor do socket naquele slot
const sockets = [1, 2, 3, 4, 5].map((slot) => SOCKETS.filter(([n]) => n !== SOCKET_NOT && n !== SOCKET_EMPTY)
  .map(([n, type, name, args]) => [String(n + (slot - 1) * 50), `${type}: ${name} + ${args[slot - 1]}`] as const))

const blank = () => ({
  item: items[0]![0] as string, itemLevel: '0', itemOption: '0', itemSkill: false, itemLuck: false, excellent: [false, false, false, false, false, false],
  ancient: '0', refine: false, harmony: harmony[0]![0] as string, sockets: [SOCKET_NOT, SOCKET_NOT, SOCKET_NOT, SOCKET_NOT, SOCKET_NOT].map(String), memb___id: '',
})
const form = reactive(blank())
const result = ref<'form' | 'fill' | { serial: string }>(creating ? 'form' : 'fill')
const showResult = ref(creating)

// createSerial: três grupos de 4 hexadecimais sem repetir um serial já cadastrado
function createSerial(): [string, string, string] {
  const part = () => Math.floor(Math.random() * 0x10000).toString(16).toUpperCase().padStart(4, '0')
  for (;;) {
    const serial: [string, string, string] = [part(), part(), part()]
    if (!store.db.goldenArcher.some((g) => g.serial.join('') === serial.join(''))) return serial
  }
}

// action=createSerialWrite
function create() {
  const f = { ...form, excellent: [...form.excellent], sockets: [...form.sockets] }
  Object.assign(form, blank())
  if (isEmpty(f.memb___id)) return (result.value = 'fill')
  const [cat, idx] = f.item.split(',').map(Number)
  const [hType, hLevel] = f.harmony.split(',').map(Number)
  const serial = createSerial()
  store.db.goldenArcher.push({
    id: Math.max(0, ...store.db.goldenArcher.map((g) => g.id)) + 1, account: f.memb___id, serial, status: 0,
    item: {
      cat: cat!, idx: idx!, level: Number(f.itemLevel), option: Number(f.itemOption), skill: f.itemSkill, luck: f.itemLuck,
      excellent: f.excellent, ancient: Number(f.ancient), refine: f.refine, harmony: [hType!, hLevel!], sockets: f.sockets.map(Number),
    },
  })
  result.value = { serial: serial.join('-') }
}
</script>

<template>
  <h1 style="margin-top: 20px;">Golden Archer</h1>

  <div class="legend" style="margin-top: 25px;">
    <h3 class="legend-title"><span style="font-size: 13px;">Selecione a opção desejada abaixo</span></h3>
    <ul style="margin: 10px 0px 10px 0px;">
      <li><RouterLink :to="{ path: '/paneladmin-golden-archer', query: { action: 'createSerial' } }">Criar serial para um determinado login</RouterLink></li>
    </ul>
  </div>

  <div class="legend" style="margin-top: 25px;">
    <div v-if="showResult && result === 'form'" style="padding: 10px;"><ul><li>Digite abaixo os dados do item que você deseja gerar.</li></ul><br /><form action="" method="post" @submit.prevent="create(); showResult = true"><table style="width: 100%;"><tbody><tr><td style="width: 150px;"><strong>O serial equivale ao item:</strong></td><td><select v-model="form.item" name="item"><option v-for="[value, name] in items" :key="value" :value="value">{{ name }}</option></select></td></tr><tr><td style="width: 150px;"><strong>Level:</strong></td><td><select v-model="form.itemLevel" name="itemLevel"><option v-for="l in 16" :key="l" :value="String(l - 1)">+{{ l - 1 }}</option></select></td></tr><tr><td style="width: 150px;"><strong>Adicional:</strong></td><td><select v-model="form.itemOption" name="itemOption"><option v-for="o in 8" :key="o" :value="String(o - 1)">+{{ (o - 1) * 4 }}</option></select></td></tr><tr><td style="width: 150px;"><strong>Skill:</strong></td><td><input v-model="form.itemSkill" name="itemSkill" type="checkbox" value="1"></td></tr><tr><td style="width: 150px;"><strong>Luck:</strong></td><td><input v-model="form.itemLuck" name="itemLuck" type="checkbox" value="1"></td></tr><tr>
      <td style="width: 150px;"><strong>Excelente:</strong></td><td>
        <template v-for="n in 6" :key="n"><input v-model="form.excellent[n - 1]" :name="`excellent${n}`" type="checkbox" value="1"> Opção excelente {{ n }}<br v-if="n < 6" />
        </template></td>
    </tr><tr>
      <td style="width: 150px;"><strong>Anciente:</strong></td><td>
        <select v-model="form.ancient" name="ancient">
          <option value="0">Não</option>
          <option value="1">Ancient 1</option>
          <option value="2">Ancient 2</option>
        </select>
      </td>
    </tr><tr><td style="width: 150px;"><strong>Refine:</strong></td><td><input v-model="form.refine" name="refine" type="checkbox" value="1"></td></tr><tr><td style="width: 150px;"><strong>Harmony:</strong></td><td><select v-model="form.harmony" name="harmony"><option v-for="([value, text], i) in harmony" :key="i" :value="value">{{ text }}</option></select>(Atenção para não selecionar uma opção não suportada pelo item)</td></tr><tr>
      <td style="width: 150px;"><strong>Sockets:</strong></td>
      <td>
        <template v-for="(list, s) in sockets" :key="s">
          Socket {{ s + 1 }}: <select v-model="form.sockets[s]" :name="`socket${s + 1}`"><option :value="String(SOCKET_NOT)">No Socket</option><option :value="String(SOCKET_EMPTY)">Empty Socket</option><option v-for="[value, text] in list" :key="value" :value="value">{{ text }}</option></select><br />
        </template>
      </td>
    </tr><tr><td colspan="2"><strong>Tenha bastante atenção ao selecionar as opções acima para não selecionar alguma opção que o item desejado não tenha suporte para a mesma dentro do jogo.</strong></td></tr><tr><td style="width: 150px;"><strong>Login contemplado:</strong></td><td><input v-model="form.memb___id" name="memb___id" type="text" maxlength="10"></td></tr><tr><td colspan="2"><input type="submit" value="Criar serial" class="button" /></td></tr></tbody></table></form></div>
    <div v-else-if="showResult && result === 'fill'" style="padding: 10px;"><ul><li><strong>Preencha o login corretamente!</strong></li></ul></div>
    <div v-else-if="showResult && typeof result === 'object'" style="padding: 10px;"><ul><li><strong>Serial gerado com sucesso!</strong></li><li>Serial: <strong>{{ result.serial }}</strong></li></ul></div>
  </div>
</template>
