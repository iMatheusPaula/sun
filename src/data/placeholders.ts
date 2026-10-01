// Imagens que o servidor gerava ou que os jogadores enviavam → placeholders.

export function svg(body: string, w: number, h: number): string {
  return 'data:image/svg+xml;utf8,' + encodeURIComponent(
    `<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 ${w} ${h}' width='${w}' height='${h}'>${body}</svg>`,
  )
}

// logos de guild (logoGuildDecode.php)
export const guildLogos = [
  svg("<rect width='8' height='8' fill='#e8e2cf'/><path fill='#b4322e' d='M1 1h6v1H1zM1 6h6v1H1z'/><path fill='#333' d='M3 2h2v4H3z'/>", 8, 8),
  svg("<rect width='8' height='8' fill='#222'/><path fill='#fcda96' d='M2 2h4v4H2z'/><path fill='#222' d='M3 3h2v2H3z'/>", 8, 8),
  svg("<rect width='8' height='8' fill='#4682b4'/><path fill='#fff' d='M0 0h4v4H0zM4 4h4v4H4z'/>", 8, 8),
  svg("<rect width='8' height='8' fill='#76f6c5'/><path fill='#291f12' d='M1 1h2v2H1zM5 1h2v2H5zM1 5h2v2H1zM5 5h2v2H5z'/>", 8, 8),
]

// Guild.G_Mark (no banco do front, o índice do logo de exemplo)
export function guildLogo(mark: number): string {
  return guildLogos[mark % guildLogos.length]!
}

// screenshots enviadas (modules/uploads/screenshots/<id>.small.jpg e .common.jpg)
export const screenshotThumb = svg("<rect width='90' height='68' fill='#343336'/><text x='45' y='38' font-family='Arial' font-size='9' fill='#c5c0ad' text-anchor='middle'>screenshot</text>", 90, 68)
export const screenshotLarge = svg("<rect width='640' height='480' fill='#343336'/><text x='320' y='245' font-family='Arial' font-size='22' fill='#c5c0ad' text-anchor='middle'>screenshot 640x480</text>", 640, 480)

// foto enviada por jogador (modules/uploads/photos/<arquivo>) e foto padrão (arquivo real do MuSite)
export const uploadedPhoto = svg("<rect width='100' height='100' fill='#343336'/><circle cx='50' cy='38' r='18' fill='#c5c0ad'/><path d='M18 92c4-22 18-32 32-32s28 10 32 32z' fill='#c5c0ad'/>", 100, 100)
export const NO_PHOTO = '/images/photos/nophoto.jpg'

// Character.image: vazio = foto padrão; data URL = enviada no painel; outro nome = arquivo que não vem no repositório
export function characterPhoto(image: string): string {
  if (!image) return NO_PHOTO
  return image.startsWith('data:') ? image : uploadedPhoto
}

// captcha do cadastro (?page=captcha, JPEG 135x25)
export const captchaImage = svg("<rect width='135' height='25' fill='#000'/><text x='30' y='18' font-family='monospace' font-size='14' fill='#fff'>7f3a9c1e</text>", 135, 25)
