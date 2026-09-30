// Grava reference/_build/seed.json com os dados de exemplo do front (src/data/seed.ts): o render.php gera o kit de
// referência com os mesmos dados. Datas (ms) viram "AAAA-MM-DD HH:MM:SS" no horário local — o PHP as lê com
// strtotime() e mostra os mesmos dígitos, qualquer que seja o fuso de quem gera.
import { writeFileSync } from 'node:fs'
import { seedDb } from '../src/data/seed'

const DATE_KEYS = new Set(['vipBegin', 'vipEnd', 'begin', 'end', 'start', 'date'])
const pad = (n: number) => String(n).padStart(2, '0')
function wallClock(ms: number): string {
  const d = new Date(ms)
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`
}

// 0 = sem data (conta que nunca teve vip)
const json = JSON.stringify(seedDb(), (key, value) => (DATE_KEYS.has(key) && typeof value === 'number' && value !== 0 ? wallClock(value) : value), 1)
const file = new URL('../reference/_build/seed.json', import.meta.url)
writeFileSync(file, json + '\n')
console.log(`seed.json: ${json.length} bytes`)
