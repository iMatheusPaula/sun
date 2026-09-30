// DLImage (modules/classes/dlimage.class.php): o que o site fazia com as imagens enviadas, no navegador.

// extensões que uploadImage aceitava (o original não passava a extensão para minúsculas e recusava
// FOTO.JPG; aqui ela é comparada em minúsculas)
const VALID_EXTENSIONS = ['jpg', 'jpeg', 'gif', 'png', 'bmp']

export function validImageName(name: string): boolean {
  return VALID_EXTENSIONS.includes(name.split('.').pop()!.toLowerCase())
}

export function loadImage(file: Blob): Promise<HTMLImageElement | null> {
  return new Promise((resolve) => {
    const url = URL.createObjectURL(file)
    const img = new Image()
    img.onload = () => {
      URL.revokeObjectURL(url)
      resolve(img)
    }
    img.onerror = () => {
      URL.revokeObjectURL(url)
      resolve(null)
    }
    img.src = url
  })
}

// resizeImage: sem altura, mantém a proporção; com as duas medidas, preenche e corta o centro
export function resizeImage(src: CanvasImageSource & { width: number; height: number }, width: number, height = 0): HTMLCanvasElement {
  if (!height) height = Math.round(src.height / (src.width / width))
  const canvas = document.createElement('canvas')
  canvas.width = width
  canvas.height = height
  const ctx = canvas.getContext('2d')!
  const averageWidth = src.width / width
  const averageHeight = src.height / height
  if (averageWidth > averageHeight) {
    const newWidth = src.width / averageHeight
    ctx.drawImage(src, -(newWidth / 2 - width / 2), 0, newWidth, height)
  } else {
    const newHeight = src.height / averageWidth
    ctx.drawImage(src, 0, -(newHeight / 2 - height / 2), width, newHeight)
  }
  return canvas
}

// saveImage(..., 'jpg'): JPEG com a qualidade padrão da classe (80)
export const toJpeg = (canvas: HTMLCanvasElement) => canvas.toDataURL('image/jpeg', 0.8)
