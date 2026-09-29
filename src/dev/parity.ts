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
    if (path === '/register-resultado') path = '/register'
  } else if (!localStorage.getItem('sunonline:session')) {
    if (path.startsWith('/paneluser')) path = '/loginerror'
    else if (path.startsWith('/paneladmin') || path.startsWith('/panelgamemaster')) path = '/loginaccessdanied'
  }
  return path + url.hash // a query não vira arquivo no kit (?id=, ?name= levam à mesma página)
}

const BOOLEAN_ATTRS = new Set(['disabled', 'checked', 'selected', 'readonly', 'multiple', 'allowfullscreen'])

function normStyle(el: Element, base: string, isRef: boolean): string {
  const css = (el as HTMLElement).style?.cssText ?? ''
  return css.replace(/url\("?([^")]+)"?\)/g, (_, u: string) => `url(${normUrl(u, base, isRef)})`)
}

function attrs(el: Element, base: string, isRef: boolean): string {
  const out: string[] = []
  for (const a of [...el.attributes]) {
    const name = a.name.toLowerCase()
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

function compare(app: Element, ref: Element, refBase: string, path: string, diffs: Diff[]) {
  if (diffs.length >= MAX_DIFFS) return
  const here = `${path} > ${label(app)}`
  if (app.tagName !== ref.tagName) {
    diffs.push({ path: here, kind: 'tag', app: label(app), ref: label(ref) })
    return
  }
  const aAttrs = attrs(app, location.href, false)
  const rAttrs = attrs(ref, refBase, true)
  if (aAttrs !== rAttrs) diffs.push({ path: here, kind: 'attrs', app: aAttrs, ref: rAttrs })
  const aText = ownText(app)
  const rText = ownText(ref)
  if (aText !== rText) diffs.push({ path: here, kind: 'text', app: aText, ref: rText })
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
  for (let i = 0; i < Math.min(aKids.length, rKids.length); i++) compare(aKids[i]!, rKids[i]!, refBase, here, diffs)
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

async function parity(page: string) {
  window.scrollTo(0, 0)
  const frame = document.createElement('iframe')
  frame.style.cssText = `position:fixed; left:-20000px; top:0; width:${window.innerWidth}px; height:${window.innerHeight}px; border:0;`
  frame.src = `/reference/${page}`
  const loaded = new Promise((ok) => frame.addEventListener('load', ok, { once: true }))
  document.body.appendChild(frame)
  await loaded
  const refDoc = frame.contentDocument!
  await Promise.all([settled(document), settled(refDoc)])

  const app = document.getElementById('mainwrapper')
  const ref = refDoc.getElementById('mainwrapper')
  const diffs: Diff[] = []
  if (!app || !ref) throw new Error('#mainwrapper não encontrado')
  compare(app, ref, frame.contentWindow!.location.href, '', diffs)
  frame.remove()

  if (diffs.length === 0) console.info(`paridade OK: ${page}`)
  else console.table(diffs)
  return { page, ok: diffs.length === 0, diffs }
}

// Rota do app → página do kit, para rodar tudo de uma vez: await __parityAll()
export const PARITY_PAGES: [route: string, page: string][] = [
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
]

async function parityAll(pages = PARITY_PAGES) {
  const { default: router } = await import('@/router')
  const results: { route: string; diffs: number; first: Diff[] }[] = []
  for (const [route, page] of pages) {
    await router.push(route)
    await new Promise((ok) => setTimeout(ok, 250))
    const r = await parity(page)
    results.push({ route, diffs: r.diffs.length, first: r.diffs.slice(0, 5) })
  }
  console.table(results.map(({ route, diffs }) => ({ route, diffs })))
  return results
}

declare global {
  interface Window {
    __parity: typeof parity
    __parityAll: typeof parityAll
  }
}

window.__parity = parity
window.__parityAll = parityAll
