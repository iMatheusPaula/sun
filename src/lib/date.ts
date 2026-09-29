// date() do PHP para os formatos que o MuSite usa (d, j, m, n, Y, y, G, H, g, h, i, s, a, A; \x = literal).
const pad = (n: number) => String(n).padStart(2, '0')

export function phpDate(format: string, date: Date): string {
  let out = ''
  for (let i = 0; i < format.length; i++) {
    const ch = format[i]!
    if (ch === '\\') {
      out += format[++i] ?? ''
      continue
    }
    const h = date.getHours()
    switch (ch) {
      case 'd': out += pad(date.getDate()); break
      case 'j': out += date.getDate(); break
      case 'm': out += pad(date.getMonth() + 1); break
      case 'n': out += date.getMonth() + 1; break
      case 'Y': out += date.getFullYear(); break
      case 'y': out += pad(date.getFullYear() % 100); break
      case 'G': out += h; break
      case 'H': out += pad(h); break
      case 'g': out += h % 12 || 12; break
      case 'h': out += pad(h % 12 || 12); break
      case 'i': out += pad(date.getMinutes()); break
      case 's': out += pad(date.getSeconds()); break
      case 'a': out += h < 12 ? 'am' : 'pm'; break
      case 'A': out += h < 12 ? 'AM' : 'PM'; break
      default: out += ch
    }
  }
  return out
}
