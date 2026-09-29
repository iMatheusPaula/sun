// sprintf() mínimo para as constantes do .lang.php: %s, %d e %% (na ordem dos argumentos).
export function sprintf(format: string, ...args: (string | number)[]): string {
  let i = 0
  return format.replace(/%([sd%])/g, (_, type: string) => {
    if (type === '%') return '%'
    const value = args[i++]
    return type === 'd' ? String(Math.trunc(Number(value))) : String(value ?? '')
  })
}
