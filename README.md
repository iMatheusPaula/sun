# sunonline

Frontend (Vue 3 + Vite + TypeScript) do template **sunonline**, migrado do MuSite. Por enquanto é 100% front:
os dados são de exemplo (`src/data/`) e o estado fica no Pinia (`src/stores/`).

```bash
bun install
bun run dev        # http://localhost:5173
bun run typecheck
bun run build
```

## Estrutura

```
src/
  layouts/     HomeLayout (3 colunas) e InnerLayout (sidebar + breadcrumb/título)
  components/  layout/ (header, footer), sidebar/ (login, enquete, equipe), ui/ (BoxMenu, GaugeBar)
  views/       uma por página; rota = nome da página do kit sem ".html"
  data/        dados de exemplo (inclusive as contas de teste em data/accounts.ts)
  stores/      sessão (login/privilégio) e enquete
  lang/        textos das constantes do .lang.php original
  styles/      common.css global (cópia do kit, editável)
  dev/         ferramenta de paridade (só em dev)
reference/     kit estático gerado do template original — gabarito visual (ver reference/README.md)
```

## Paridade com o kit

Enquanto a migração não termina, cada página tem de ficar igual à do kit antes de qualquer mudança visual.
Com o `bun run dev` rodando, abra a rota no navegador e, no console:

```js
await __parity('contact.html')   // compara com /reference/contact.html
```

Ele compara tag, atributos, texto e posição/tamanho de cada elemento de `#mainwrapper`. Diferenças
intencionais conhecidas: o link "Painel" do menu vai para `/paneluser` (o kit estático apontava para
`loginerror.html`) e a lista da Equipe existe em todas as páginas (no kit, só na home).

## Páginas migradas

- `/` (home), `/contact`
- As demais rotas abrem uma página "em migração" com o link para a referência.
