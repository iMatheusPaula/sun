// Leilões (webAuctions): o que o painel do usuário e o do admin compartilham.
import type { Auction } from '@/data/db'
import { loadImage, resizeImage, toJpeg, validImageName } from './images'

type Status = { end: string; waitBegin: string; inProgress: string; waitPremium: string; unknown: string }

// o status que as duas telas calculam (textos do .lang.php de cada painel)
export function auctionStatus(a: Auction, t: Status, now = Date.now()): string {
  if (a.closed) return t.end
  if (a.start > now) return t.waitBegin
  if (a.start < now && a.end > now) return t.inProgress
  if (a.end < now) return t.waitPremium
  return t.unknown
}

// modules/uploads/auctions/<id>.small|large.jpg: as de exemplo estão em public/images/auctions; as enviadas pelo
// admin ficam no próprio leilão (data URL)
export function auctionPhoto(a: Pick<Auction, 'id' | 'photo'>, size: 'small' | 'large'): string {
  return a.photo?.[size] ?? `/images/auctions/${a.id}.${size}.jpg`
}

// ldPanelAdmin::uploadPicture(foto, 800, 600): "large" com no máximo 800 de largura e miniatura 150x150 (JPEG)
export async function auctionPhotoUpload(file: File): Promise<Auction['photo'] | null> {
  const img = validImageName(file.name) ? await loadImage(file) : null
  if (!img) return null
  const large = img.width > 800 ? resizeImage(img, 800) : resizeImage(img, img.width, img.height)
  return { large: toJpeg(large), small: toJpeg(resizeImage(large, 150, 150)) }
}

// "16/10/2011 14:00:00" → mktime(hora, min, seg, mês, dia, ano); partes que faltam valem 0
export function parseAuctionDate(text: string): number {
  const [date = '', time = ''] = text.split(' ')
  const [d, m, y] = date.split('/').map((n) => parseInt(n, 10) || 0)
  const [h, i, s] = time.split(':').map((n) => parseInt(n, 10) || 0)
  return new Date(y ?? 0, (m ?? 0) - 1, d ?? 0, h ?? 0, i ?? 0, s ?? 0).getTime()
}
