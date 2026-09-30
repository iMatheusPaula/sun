<script setup lang="ts">
// O lightbox jQuery FancyBox 1.3.4 que o site usava (foto do leilão, popup do evento de perguntas), com a
// mesma marcação, o mesmo CSS e as mesmas contas de tamanho/posição, sem jQuery. Com `src` mostra a imagem;
// sem ela, o conteúdo do slot (medido como o fancybox media o HTML: autoDimensions). Abre ao montar;
// fecha no X, no Esc ou clicando fora, e então emite `closed`.
import { computed, nextTick, onMounted, onUnmounted, ref, type CSSProperties } from 'vue'
import '@/styles/fancybox.css'

const props = defineProps<{ src?: string }>()
const emit = defineEmits<{ closed: [] }>()

// $.fn.fancybox.defaults
const PADDING = 10
const MARGIN = 40
const SPEED = 300 // speedIn / speedOut
const OVERLAY_OUT = 200 // overlay.fadeOut('fast')

type Phase = 'loading' | 'measure' | 'before' | 'in' | 'open' | 'out'
const phase = ref<Phase>(props.src ? 'loading' : 'measure')
const inner = ref<HTMLElement>()
const pos = ref({ top: 0, left: 0, width: 0, height: 0 })
const contentHeight = ref<number | 'auto'>('auto')
const overlay = ref<{ height: number; opacity: number } | null>(null)
const frame = ref(0)
let spinner = 0
const timers: number[] = []
const later = (ms: number, fn: () => void) => timers.push(window.setTimeout(fn, ms))

// [largura, altura, esquerda, topo] da área útil da janela (U)
function viewport(): [number, number, number, number] {
  const doc = document.documentElement
  return [doc.clientWidth - MARGIN * 2, doc.clientHeight - MARGIN * 2, window.scrollX + MARGIN, window.scrollY + MARGIN]
}

const documentHeight = () =>
  Math.max(document.body.scrollHeight, document.documentElement.scrollHeight, document.body.offsetHeight, document.documentElement.offsetHeight, document.documentElement.clientHeight)

// X: tamanho final (com o padding) e posição centralizada; imagem grande é reduzida mantendo a proporção
function zoomTo(width: number, height: number, image: boolean) {
  const [vw, vh, vx, vy] = viewport()
  const k = PADDING * 2
  let w = width + k
  let h = height + k
  if (w > vw || h > vh) {
    if (image) {
      const ratio = width / height
      if (w > vw) {
        w = vw
        h = Math.trunc((w - k) / ratio + k)
      }
      if (h > vh) {
        h = vh
        w = Math.trunc((h - k) * ratio + k)
      }
    } else {
      w = Math.min(w, vw)
      h = Math.min(h, vh)
    }
  }
  return {
    width: w,
    height: h,
    top: Math.trunc(Math.max(vy - 20, vy + (vh - h - 40) * 0.5)),
    left: Math.trunc(Math.max(vx - 20, vx + (vw - w - 40) * 0.5)),
  }
}

function show(width: number, height: number, image: boolean) {
  const to = zoomTo(width, height, image)
  pos.value = to
  contentHeight.value = image ? to.height - PADDING * 2 : 'auto'
  overlay.value = { height: documentHeight(), opacity: 0.7 }
  // fadeIn: entra transparente na posição final e a opacidade sobe em SPEED ms
  phase.value = 'before'
  later(20, () => {
    phase.value = 'in'
    later(SPEED, () => (phase.value = 'open'))
  })
}

// center(): reposiciona pelo tamanho atual do conteúdo (ao redimensionar a janela)
function center() {
  if (phase.value !== 'open') return
  const content = document.getElementById('fancybox-content')
  if (!content) return
  const [vw, vh, vx, vy] = viewport()
  const top = Math.max(vy - 20, vy + (vh - content.clientHeight - 40) * 0.5 - PADDING)
  const left = Math.max(vx - 20, vx + (vw - content.clientWidth - 40) * 0.5 - PADDING)
  pos.value = { ...pos.value, top: Math.trunc(top), left: Math.trunc(left) }
  if (overlay.value) overlay.value = { ...overlay.value, height: documentHeight() }
}

function close() {
  if (phase.value !== 'open' && phase.value !== 'in' && phase.value !== 'before') return
  phase.value = 'out'
  later(SPEED, () => {
    if (overlay.value) overlay.value = { ...overlay.value, opacity: 0 }
    later(OVERLAY_OUT, () => emit('closed'))
  })
}

// clique no "carregando" antes da imagem chegar: cancela
function cancel() {
  clearInterval(spinner)
  emit('closed')
}

function onKey(e: KeyboardEvent) {
  if (e.key === 'Escape') {
    e.preventDefault()
    close()
  }
}

onMounted(async () => {
  document.addEventListener('keydown', onKey)
  window.addEventListener('resize', center)
  if (props.src) {
    spinner = window.setInterval(() => (frame.value = (frame.value + 1) % 12), 66)
    const img = new Image()
    img.onload = () => {
      clearInterval(spinner)
      show(img.width, img.height, true)
    }
    img.onerror = () => {
      clearInterval(spinner)
      emit('closed')
    }
    img.src = props.src
  } else {
    await nextTick()
    const el = inner.value!
    show(el.offsetWidth, el.offsetHeight, false)
  }
})

onUnmounted(() => {
  document.removeEventListener('keydown', onKey)
  window.removeEventListener('resize', center)
  clearInterval(spinner)
  timers.forEach(clearTimeout)
})

// os estilos na ordem em que o jQuery os gravava; aberto, sem opacity (o fadeIn devolve o valor original)
const wrapStyle = computed<CSSProperties>(() => {
  const p = phase.value
  if (p === 'loading') return {}
  if (p === 'measure') return { display: 'block', visibility: 'hidden' }
  const { top, left, width, height } = pos.value
  const place = { width: `${width}px`, height: p === 'open' || p === 'out' ? 'auto' : `${height}px`, top: `${top}px`, left: `${left}px`, display: 'block' }
  return p === 'open' ? place : { ...place, opacity: p === 'in' ? 1 : 0, transition: `opacity ${SPEED}ms` }
})

const contentStyle = computed<CSSProperties>(() => {
  if (phase.value === 'measure') return { borderWidth: `${PADDING}px`, width: 'auto', height: 'auto' }
  const h = contentHeight.value
  return { borderWidth: `${PADDING}px`, width: `${pos.value.width - PADDING * 2}px`, height: h === 'auto' ? 'auto' : `${h}px` }
})

// o fundo aparece de uma vez (show) e só some com fade (fadeOut 'fast')
const overlayStyle = computed<CSSProperties>(() => ({
  backgroundColor: '#777',
  opacity: overlay.value?.opacity ?? 0,
  cursor: 'pointer',
  height: `${overlay.value?.height ?? 0}px`,
  display: 'block',
  ...(phase.value === 'out' ? { transition: `opacity ${OVERLAY_OUT}ms` } : {}),
}))
</script>

<template>
  <Teleport to="body">
    <div id="fancybox-loading" :style="{ display: phase === 'loading' ? 'block' : 'none' }" @click="cancel"><div :style="{ top: `${frame * -40}px` }"></div></div>
    <div v-if="overlay" id="fancybox-overlay" :style="overlayStyle" @click="close"></div>
    <div id="fancybox-wrap" :style="wrapStyle">
      <div id="fancybox-outer">
        <div id="fancybox-bg-n" class="fancybox-bg"></div><div id="fancybox-bg-ne" class="fancybox-bg"></div><div id="fancybox-bg-e" class="fancybox-bg"></div><div id="fancybox-bg-se" class="fancybox-bg"></div><div id="fancybox-bg-s" class="fancybox-bg"></div><div id="fancybox-bg-sw" class="fancybox-bg"></div><div id="fancybox-bg-w" class="fancybox-bg"></div><div id="fancybox-bg-nw" class="fancybox-bg"></div>
        <div id="fancybox-content" :style="contentStyle">
          <template v-if="src"><img v-if="phase !== 'loading'" id="fancybox-img" :src="src" alt="" /></template>
          <div v-else ref="inner" style="width:auto;height:auto;overflow: auto;position:relative;"><slot /></div>
        </div>
        <a id="fancybox-close" :style="{ display: phase === 'open' ? 'inline' : 'none' }" @click="close"></a>
        <!-- título e setas de galeria: criados pelo fancybox mesmo sem uso (escondidos) -->
        <div id="fancybox-title" style="display: none;"></div><a href="javascript:;" id="fancybox-left"><span class="fancy-ico" id="fancybox-left-ico"></span></a><a href="javascript:;" id="fancybox-right"><span class="fancy-ico" id="fancybox-right-ico"></span></a>
      </div>
    </div>
  </Teleport>
</template>
