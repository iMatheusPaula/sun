// Paridade com o kit estático (só em dev). No console da página do app:
//   await __parity('contact.html')
// Carrega reference/<pagina> num iframe do mesmo tamanho da janela, percorre #mainwrapper dos dois
// em paralelo e compara, elemento a elemento: tag, atributos, texto próprio e caixa renderizada
// (getBoundingClientRect). Ignora <script>, <style>, <template>, comentários e input hidden
// (mecanismos do kit/PHP, sem efeito visual), eventos on*, aria-current, classes router-link-*,
// value="" e o valor de atributos booleanos.
// Equivalências de link: o kit estático mandava o visitante direto para loginerror.html (painel do
// usuário) e loginaccessdanied.html (GM/admin); no app o link é o do painel e a rota decide o que mostrar.
// O cadastro do kit postava para register-resultado.html; no app o resultado aparece na própria /register.
// Variável de template sem valor ({#questionResponse} cru no bloco escondido do evento de perguntas, nas
// páginas em que o original não rodava o sorteio) não é reproduzida: o texto do kit é comparado sem ela.

import { useDbStore } from '@/stores/db'
import { useSessionStore } from '@/stores/session'
import { useQuestionsEventStore } from '@/stores/questionsEvent'

interface Diff {
  path: string
  kind: 'tag' | 'attrs' | 'text' | 'box' | 'children'
  app: string
  ref: string
}

const SKIP = new Set(['SCRIPT', 'STYLE', 'TEMPLATE', 'NOSCRIPT'])
const MAX_DIFFS = 40

function visibleChildren(el: Element): Element[] {
  return [...el.children].filter(
    (c) => !SKIP.has(c.tagName) && !(c.tagName === 'INPUT' && (c as HTMLInputElement).type === 'hidden'),
  )
}

// texto próprio do elemento: nós de texto vizinhos se juntam como o navegador os mostra; um filho
// elemento (ex.: <br>) conta como separador
function ownText(el: Element): string {
  return [...el.childNodes]
    .map((n) => (n.nodeType === Node.TEXT_NODE ? n.textContent ?? '' : n.nodeType === Node.ELEMENT_NODE ? ' ' : ''))
    .join('')
    .replace(/\s+/g, ' ')
    .trim()
}

// Páginas do kit que são estados de uma mesma rota do app (a variação fica na query ou no estado da tela)
const REF_ALIASES: [RegExp, string][] = [
  [/^\/register-resultado$/, '/register'],
  [/^\/paneluser-log-buys-(cash|vips)$/, '/paneluser-log-buys'],
  [/^\/paneluser-(.+)-kira$/, '/paneluser-$1'], // ?character=Kira
  [/^\/paneluser-read-ticket-view$/, '/paneluser-read-ticket'],
  [/^\/paneluser-auctions-\d+$/, '/paneluser-auctions'],
  // GM/admin: formulários já carregados (-kira01, -kira, -100, -4…), tickets e sincronizador
  [/^\/paneladmin-sincronize-(vips|bans)$/, '/paneladmin-sincronize'],
  [/^\/paneladmin-(edit-account|edit-character|transform-vip)-kira(01)?$/, '/paneladmin-$1'],
  [/^\/paneladmin-(modify-notice|modify-poll|edit-auctions|close-auctions)-\d+$/, '/paneladmin-$1'],
  [/^\/paneladmin-golden-archer-create-serial$/, '/paneladmin-golden-archer'],
]

// Mesmo endereço nos dois lados: resolve relativo à página e tira o prefixo /reference e o .html do kit.
function normUrl(value: string, base: string, isRef: boolean): string {
  if (/^(javascript|data):/i.test(value)) return decodeURIComponent(value).replace(/\s+/g, '')
  if (value.startsWith('#')) return value
  const url = new URL(value, base)
  if (url.origin !== location.origin) return url.href
  let path = url.pathname.replace(/\.html$/, '')
  if (isRef) {
    path = path.replace(/^\/reference/, '')
    if (path === '/index') path = '/'
    for (const [from, to] of REF_ALIASES) path = path.replace(from, to)
  } else {
    // o kit tem uma página só para o ticket aberto (…-tickets-view), vindo da lista de abertos ou de encerrados
    if (url.searchParams.has('Ticket')) path = path.replace(/^\/(panelgamemaster|paneladmin)-tickets-(operation|completing)$/, '/$1-tickets-view')
    if (!localStorage.getItem('sunonline:session')) {
      if (path.startsWith('/paneluser')) path = '/loginerror'
      else if (path.startsWith('/paneladmin') || path.startsWith('/panelgamemaster')) path = '/loginaccessdanied'
    }
  }
  return path + url.hash // a query não vira arquivo no kit (?id=, ?name= levam à mesma página)
}

const BOOLEAN_ATTRS = new Set(['disabled', 'checked', 'selected', 'readonly', 'multiple', 'allowfullscreen'])

function normStyle(el: Element, base: string, isRef: boolean): string {
  const css = (el as HTMLElement).style?.cssText ?? ''
  return css.replace(/url\("?([^")]+)"?\)/g, (_, u: string) => `url(${normUrl(u, base, isRef)})`)
}

// campos de texto: o que aparece é a propriedade .value (o Vue não grava o atributo value neles)
const DEFAULT_VALUE_INPUTS = new Set(['submit', 'button', 'reset', 'hidden', 'image', 'checkbox', 'radio'])
function textValue(el: Element): string | null {
  // tagName em vez de instanceof: os elementos do iframe são de outra janela
  if (el.tagName === 'TEXTAREA') return (el as HTMLTextAreaElement).value
  if (el.tagName === 'INPUT' && !DEFAULT_VALUE_INPUTS.has((el as HTMLInputElement).type)) return (el as HTMLInputElement).value
  return null
}

// skip: atributos que não entram na comparação deste par (ver compare)
function attrs(el: Element, base: string, isRef: boolean, skip: string[] = []): string {
  const out: string[] = []
  const shown = textValue(el)
  if (shown) out.push(`value=${JSON.stringify(shown)}`)
  // marcado/selecionado: o estado real (o Vue define a propriedade, não o atributo)
  const input = el as HTMLInputElement
  const toggle = el.tagName === 'OPTION' ? 'selected' : el.tagName === 'INPUT' && (input.type === 'radio' || input.type === 'checkbox') ? 'checked' : null
  if (toggle && (toggle === 'selected' ? (el as HTMLOptionElement).selected : input.checked)) out.push(`${toggle}=""`)
  for (const a of [...el.attributes]) {
    const name = a.name.toLowerCase()
    if (name === 'value' && shown !== null) continue
    if (name === toggle || skip.includes(name)) continue
    // action: o kit trocava o destino dos forms por "#" (sem servidor); no app o envio é tratado no Vue
    if (name.startsWith('on') || name.startsWith('data-v-') || name.startsWith('data-kit-') || name === 'aria-current' || name === 'action') continue
    let value = a.value
    if (name === 'value' && value === '') continue
    if (BOOLEAN_ATTRS.has(name)) value = ''
    else if (name === 'class') {
      value = value.split(/\s+/).filter((c) => c && !c.startsWith('router-link-')).sort().join(' ')
      if (!value) continue
    } else if (name === 'style') {
      value = normStyle(el, base, isRef)
      if (!value) continue
    } else if (name === 'href' || name === 'src' || name === 'action') {
      value = normUrl(value, base, isRef)
    }
    out.push(`${name}=${JSON.stringify(value)}`)
  }
  return out.sort().join(' ')
}

function box(el: Element): string {
  const r = el.getBoundingClientRect()
  return [r.x, r.y, r.width, r.height].map((n) => Math.round(n)).join(',')
}

function label(el: Element): string {
  const id = el.id ? `#${el.id}` : ''
  const cls = [...el.classList].filter((c) => !c.startsWith('router-link-')).map((c) => `.${c}`).join('')
  return `${el.tagName.toLowerCase()}${id}${cls}`
}

// ignore: seletores (no app) de elementos cujos atributos/texto diferem de propósito (correção documentada na tela ou
// defeito do próprio kit)
function compare(app: Element, ref: Element, refBase: string, path: string, diffs: Diff[], ignore: string[] = []) {
  if (diffs.length >= MAX_DIFFS) return
  const here = `${path} > ${label(app)}`
  if (app.tagName !== ref.tagName) {
    diffs.push({ path: here, kind: 'tag', app: label(app), ref: label(ref) })
    return
  }
  // o kit trocava por "#" os links que executavam uma ação (remover, desconectar…) e fazia dos formulários de "carregar"
  // um GET para a página do estado já carregado: nesses casos o destino/método do app (o do original) não é comparado
  const skip: string[] = []
  if (ref.getAttribute('href') === '#') skip.push('href')
  if (ref.tagName === 'FORM' && /\.html$/.test(ref.getAttribute('action') ?? '')) skip.push('method')
  const aAttrs = attrs(app, location.href, false, skip)
  const rAttrs = attrs(ref, refBase, true, skip)
  const ignored = ignore.some((selector) => app.matches(selector))
  if (aAttrs !== rAttrs && !ignored) diffs.push({ path: here, kind: 'attrs', app: aAttrs, ref: rAttrs })
  // textarea: o que aparece é o value (comparado nos atributos); o Vue não escreve o texto como filho
  const aText = app.tagName === 'TEXTAREA' ? '' : ownText(app)
  const rText = ref.tagName === 'TEXTAREA' ? '' : ownText(ref).replace(/\{#\w+\}/g, '').replace(/\s+/g, ' ').trim()
  if (aText !== rText && !ignored) diffs.push({ path: here, kind: 'text', app: aText, ref: rText })
  const aBox = box(app)
  const rBox = box(ref)
  if (aBox !== rBox) diffs.push({ path: here, kind: 'box', app: aBox, ref: rBox })

  // a lista da Equipe (ajax membersstaff) só foi pré-renderizada na home do kit; no app ela existe em toda página
  if (ref.id === 'equipe' && ref.children.length === 0) return

  const aKids = visibleChildren(app)
  const rKids = visibleChildren(ref)
  if (aKids.length !== rKids.length) {
    diffs.push({ path: here, kind: 'children', app: aKids.map(label).join(' '), ref: rKids.map(label).join(' ') })
  }
  for (let i = 0; i < Math.min(aKids.length, rKids.length); i++) compare(aKids[i]!, rKids[i]!, refBase, here, diffs, ignore)
}

async function settled(doc: Document) {
  await doc.fonts.ready
  await Promise.all(
    [...doc.images].filter((img) => !img.complete).map((img) => new Promise((ok) => {
      img.addEventListener('load', ok, { once: true })
      img.addEventListener('error', ok, { once: true })
    })),
  )
}

// prepare: mexe nas duas páginas antes de comparar (ex.: abrir o mesmo grupo do menu nas duas)
async function parity(page: string, prepare?: (app: Document, ref: Document) => void | Promise<void>, ignore: string[] = []) {
  window.scrollTo(0, 0)
  const frame = document.createElement('iframe')
  frame.style.cssText = `position:fixed; left:-20000px; top:0; width:${window.innerWidth}px; height:${window.innerHeight}px; border:0;`
  frame.src = `/reference/${page}`
  const loaded = new Promise((ok) => frame.addEventListener('load', ok, { once: true }))
  document.body.appendChild(frame)
  await loaded
  const refDoc = frame.contentDocument!
  await Promise.all([settled(document), settled(refDoc)])
  if (prepare) {
    await prepare(document, refDoc)
    await new Promise((ok) => setTimeout(ok, 100))
  }

  const app = document.getElementById('mainwrapper')
  const ref = refDoc.getElementById('mainwrapper')
  const diffs: Diff[] = []
  if (!app || !ref) throw new Error('#mainwrapper não encontrado')
  compare(app, ref, frame.contentWindow!.location.href, '', diffs, ignore)
  frame.remove()

  if (diffs.length === 0) console.info(`paridade OK: ${page}`)
  else console.table(diffs)
  return { page, ok: diffs.length === 0, diffs }
}

// Estado extra de uma entrada: pergunta do evento aberta no popup, grupo do menu aberto (AbreMenu('divN') no kit,
// o mesmo link no app) e o que fazer no app depois de abrir a rota (ex.: enviar um formulário).
export interface ParityExtra {
  question?: number
  menu?: string
  act?: () => void | Promise<void>
  ignore?: string[]
}

// abre o grupo do menu nas duas páginas: no kit chama o AbreMenu do .tpl; no app clica no link na mesma posição
function openMenu(div: string) {
  return (app: Document, ref: Document) => {
    const refLinks = [...ref.querySelectorAll('#etc > #menu_cp a')]
    const i = refLinks.findIndex((a) => a.getAttribute('onclick')?.includes(`'${div}'`))
    ;(app.querySelectorAll('#etc > #menu_cp a')[i] as HTMLElement).click()
    try {
      ;(ref.defaultView as unknown as { AbreMenu(d: string): void }).AbreMenu(div)
    } catch {
      // o AbreMenu do original para no primeiro divN que não existe (os grupos anteriores já foram trocados)
    }
  }
}

// act: preenche campos do formulário do app (#etc) e clica no botão de envio com esse texto
function submitForm(fields: Record<string, string>, button: string) {
  return async () => {
    for (const [name, value] of Object.entries(fields)) {
      const el = document.querySelector<HTMLInputElement>(`#etc [name="${name}"]`)!
      el.value = value
      el.dispatchEvent(new Event(el.tagName === 'SELECT' ? 'change' : 'input'))
    }
    await new Promise((ok) => setTimeout(ok, 50))
    ;[...document.querySelectorAll<HTMLInputElement>('#etc input[type=submit]')].find((b) => b.value === button)!.click()
  }
}

// Rota do app → página do kit [→ conta logada [→ estado extra]], para rodar tudo de uma vez: await __parityAll()
export const PARITY_PAGES: [route: string, page: string, login?: string, extra?: ParityExtra][] = [
  ['/', 'index.html'], ['/contact', 'contact.html'], ['/downloads', 'downloads.html'],
  ['/rankings', 'rankings.html'], ['/rankings-semanal', 'rankings-semanal.html'], ['/rankings-mensal', 'rankings-mensal.html'],
  ['/rankings-level', 'rankings-level.html'], ['/rankings-pk', 'rankings-pk.html'], ['/rankings-master-resets', 'rankings-master-resets.html'],
  ['/rankings-guilds', 'rankings-guilds.html'], ['/rankings-personagem?name=DarkLord', 'rankings-personagem.html'],
  ['/rankings-guild?name=Legends', 'rankings-guild.html'], ['/rankings-gens', 'rankings-gens.html'], ['/rankings-gens-vanert', 'rankings-gens-vanert.html'],
  ['/register', 'register.html'], ['/noticias', 'noticias.html'], ['/noticia?id=100', 'noticia.html'],
  ['/screenshots', 'screenshots.html'], ['/screenshot?id=1', 'screenshot.html'],
  ['/vips', 'vips.html'], ['/vips-vantagens', 'vips-vantagens.html'], ['/vips-como-comprar', 'vips-como-comprar.html'],
  ['/vips-como-comprar-vip', 'vips-como-comprar-vip.html'], ['/onlines', 'onlines.html'],
  ['/banned-personagens', 'banned-personagens.html'], ['/banned-contas', 'banned-contas.html'],
  ['/recovery', 'recovery.html'], ['/loginerror', 'loginerror.html'], ['/loginaccessdanied', 'loginaccessdanied.html'],
  // estados logados
  ['/noticia?id=100', 'noticia-usuario.html', 'kira01'],
  // painel do usuário (conta kira01, Vip Gold)
  ['/paneluser', 'paneluser.html', 'kira01'], ['/paneluser', 'paneluser-questions-event.html', 'kira01', { question: 1 }],
  ...['div1', 'div2', 'div3', 'div4'].map((menu): [string, string, string, ParityExtra] => ['/paneluser', 'paneluser.html', 'kira01', { menu }]),
  ['/paneluser-modify-data', 'paneluser-modify-data.html', 'kira01'],
  ['/paneluser-clean-vault', 'paneluser-clean-vault.html', 'kira01'], ['/paneluser-double-vault', 'paneluser-double-vault.html', 'kira01'],
  ['/paneluser-log-buys', 'paneluser-log-buys.html', 'kira01'], ['/paneluser-log-buys?Write=1', 'paneluser-log-buys-cash.html', 'kira01'],
  ['/paneluser-log-buys?Write=2', 'paneluser-log-buys-vips.html', 'kira01'], ['/paneluser-modify-personalid', 'paneluser-modify-personalid.html', 'kira01'],
  ['/paneluser-game-disconnect', 'paneluser-game-disconnect.html', 'kira01'], ['/paneluser-transfer-cash', 'paneluser-transfer-cash.html', 'kira01'],
  ['/paneluser-collector-points', 'paneluser-collector-points.html', 'kira01'],
  // gerenciar personagens: sem personagem e com Kira escolhido
  ...['reset', 'master-reset', 'clean-pk', 'distribute-points', 'move-character', 'change-nick', 'change-class',
    'redistribute-points', 'clean-inventory', 'manager-photo'].flatMap((o): [string, string, string][] => [
    [`/paneluser-${o}`, `paneluser-${o}.html`, 'kira01'],
    [`/paneluser-${o}?character=Kira`, `paneluser-${o}-kira.html`, 'kira01'],
  ]),
  ['/paneluser-reset-transfer', 'paneluser-reset-transfer.html', 'kira01'],
  ['/paneluser-reset-transfer?character1=Kira&character2=Kirinha', 'paneluser-reset-transfer-kira.html', 'kira01'],
  // shop e suporte
  ['/paneluser-buy-vips', 'paneluser-buy-vips.html', 'kira01'], ['/paneluser-confirm-payment', 'paneluser-confirm-payment.html', 'kira01'],
  ['/paneluser-open-complaints', 'paneluser-open-complaints.html', 'kira01'], ['/paneluser-open-ticket', 'paneluser-open-ticket.html', 'kira01'],
  ['/paneluser-read-ticket', 'paneluser-read-ticket.html', 'kira01'], ['/paneluser-read-ticket?Ticket=14', 'paneluser-read-ticket-view.html', 'kira01'],
  ['/paneluser-send-sms', 'paneluser-send-sms.html', 'kira01'], ['/paneluser-virtual-vault', 'paneluser-virtual-vault.html', 'kira01'],
  ['/paneluser-auctions', 'paneluser-auctions.html', 'kira01'], ['/paneluser-golden-archer', 'paneluser-golden-archer.html', 'kira01'],
  ['/paneluser-screenshot', 'paneluser-screenshot.html', 'kira01'],
  ...[1, 2, 3, 4].map((id): [string, string, string] => [`/paneluser-auctions?action=showDetailsAuction&id=${id}`, `paneluser-auctions-${id}.html`, 'kira01']),
  // painel do game master (gm01) e do admin (admin)
  ['/panelgamemaster', 'panelgamemaster.html', 'gm01'],
  ...['manager-ban-account', 'manager-ban-character', 'complaints', 'tickets-operation', 'tickets-completing']
    .map((o): [string, string, string] => [`/panelgamemaster-${o}`, `panelgamemaster-${o}.html`, 'gm01']),
  ['/panelgamemaster-tickets-operation?Ticket=14', 'panelgamemaster-tickets-view.html', 'gm01'],
  ...['div1', 'div2', 'div3'].map((menu): [string, string, string, ParityExtra] => ['/panelgamemaster', 'panelgamemaster.html', 'gm01', { menu }]),
  ['/paneladmin', 'paneladmin.html', 'admin'],
  ...['div1', 'div11', 'div2', 'div3', 'div4', 'div5', 'div10', 'div6', 'div7', 'div12', 'div8', 'div9']
    .map((menu): [string, string, string, ParityExtra] => ['/paneladmin', 'paneladmin.html', 'admin', { menu }]),
  ['/paneladmin-sincronize?type=1', 'paneladmin-sincronize-vips.html', 'admin'],
  ['/paneladmin-sincronize?type=2', 'paneladmin-sincronize-bans.html', 'admin'],
  ...['edit-account', 'delete-account', 'manager-ban-account', 'manager-accounts-transfer-cash', 'edit-character', 'delete-character',
    'manager-ban-character', 'complaints', 'tickets-operation', 'tickets-completing']
    .map((o): [string, string, string] => [`/paneladmin-${o}`, `paneladmin-${o}.html`, 'admin']),
  ['/paneladmin-edit-account', 'paneladmin-edit-account-kira01.html', 'admin', { act: submitForm({ account: 'kira01' }, 'Carregar dados.') }],
  // o select de classe vem com a classe atual marcada (no original, a primeira da lista)
  ['/paneladmin-edit-character', 'paneladmin-edit-character-kira.html', 'admin',
    { act: submitForm({ character: 'Kira' }, 'Carregar dados.'), ignore: ['select[name=Class] option'] }],
  ['/paneladmin-tickets-operation?Ticket=14', 'paneladmin-tickets-view.html', 'admin'],
  ...['add-vip', 'delete-vip', 'transform-vip', 'add-notice', 'remove-notice', 'modify-notice', 'add-poll', 'remove-poll', 'modify-poll',
    'add-cash', 'remove-cash', 'deposits-in-operation', 'deposits-completing', 'deposits-false']
    .map((o): [string, string, string] => [`/paneladmin-${o}`, `paneladmin-${o}.html`, 'admin']),
  ['/paneladmin-transform-vip', 'paneladmin-transform-vip-kira01.html', 'admin', { act: submitForm({ account: 'kira01' }, 'Prosseguir.') }],
  ['/paneladmin-modify-notice', 'paneladmin-modify-notice-100.html', 'admin', { act: submitForm({ id: '100' }, 'Carregar.') }],
  ['/paneladmin-modify-poll?action=modify&idPoll=1', 'paneladmin-modify-poll-1.html', 'admin'],
  ['/paneladmin-modify-poll?action=modify&idPoll=2', 'paneladmin-modify-poll-2.html', 'admin'],
  // o nome sugerido do backup tem a data de agora (o kit fixou a do build)
  ['/paneladmin-gerate-backups', 'paneladmin-gerate-backups.html', 'admin', { ignore: ['input[name=filename]'] }],
  ...['game-msg-all', 'game-msg-specific', 'game-disconnect', 'game-chat-server', 'add-auctions', 'edit-auctions', 'delete-auctions',
    'close-auctions', 'verify-update', 'golden-archer']
    .map((o): [string, string, string] => [`/paneladmin-${o}`, `paneladmin-${o}.html`, 'admin']),
  ['/paneladmin-edit-auctions', 'paneladmin-edit-auctions-4.html', 'admin', { act: submitForm({ auction: '4' }, 'Carregar') }],
  ['/paneladmin-close-auctions', 'paneladmin-close-auctions-2.html', 'admin', { act: submitForm({ auction: '2' }, 'Fechar') }],
  // nomes coreanos do item.txt: o site (Latin-1) mostrava byte a byte; o kit, gerado em UTF-8, juntou alguns pares de bytes
  ['/paneladmin-golden-archer?action=createSerial', 'paneladmin-golden-archer-create-serial.html', 'admin', { ignore: ['select[name=item] option'] }],
]

// Roda cada página com o banco nos dados de exemplo e a conta indicada (ou deslogado), sem o sorteio do evento
// de perguntas (só a pergunta pedida); no fim, restaura a sessão.
async function parityAll(pages = PARITY_PAGES) {
  const { default: router } = await import('@/router')
  const db = useDbStore()
  const session = useSessionStore()
  const questions = useQuestionsEventStore()
  const previous = session.login
  const results: { route: string; diffs: number; first: Diff[] }[] = []
  questions.lottery = false
  for (const [route, page, login, extra = {}] of pages) {
    db.reset()
    if (login) session.startSession(login)
    else session.signOut()
    questions.reset()
    if (extra.question) questions.force(extra.question)
    await router.push(route)
    await new Promise((ok) => setTimeout(ok, 250))
    if (extra.act) {
      await extra.act()
      await new Promise((ok) => setTimeout(ok, 250))
    }
    const r = await parity(page, extra.menu ? openMenu(extra.menu) : undefined, extra.ignore)
    const label = [route, login && `(${login})`, extra.menu && `[${extra.menu}]`, extra.act && '[act]'].filter(Boolean).join(' ')
    results.push({ route: label, diffs: r.diffs.length, first: r.diffs.slice(0, 5) })
  }
  db.reset()
  if (previous) session.startSession(previous)
  else session.signOut()
  questions.reset()
  questions.lottery = true
  console.table(results.map(({ route, diffs }) => ({ route, diffs })))
  return results
}

declare global {
  interface Window {
    __parity: typeof parity
    __parityAll: typeof parityAll
    __resetDb: () => void
  }
}

window.__parity = parity
window.__parityAll = parityAll
window.__resetDb = () => useDbStore().reset()
