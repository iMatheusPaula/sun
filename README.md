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
  components/  layout/, sidebar/ (login, enquete, equipe), rankings/, notices/, ui/ (BoxMenu, GaugeBar)
  views/       uma por página; rota = nome da página do kit sem ".html"
  data/        dados de exemplo (inclusive as contas de teste em data/accounts.ts)
  stores/      sessão, contas, enquete, notícias/comentários, screenshots
  lib/         regras portadas das classes ld* (rankings) e date() do PHP
  config/      settings.ts — chaves de exibição da settings.php (sem banco, SMTP ou senhas)
  lang/        um módulo por .lang.php original; "// html" marca os que têm marcação (v-html)
  styles/      common.css global (cópia do kit, editável)
  dev/         ferramenta de paridade (só em dev)
public/        imagens, fontes e termos.html (carregado no iframe do cadastro)
reference/     kit estático gerado do template original — gabarito visual (ver reference/README.md)
```

`src/lang/` e `src/config/settings.ts` foram exportados uma vez dos arquivos do MuSite; daqui em diante são
editados à mão.

## Paridade com o kit

Cada página tem de ficar igual à do kit antes de qualquer mudança visual. Com o `bun run dev` rodando, no
console do navegador:

```js
await __parityAll()              // todas as rotas migradas (lista em src/dev/parity.ts)
await __parity('contact.html')   // só a página atual contra /reference/contact.html
```

Compara tag, atributos, texto e posição/tamanho de cada elemento de `#mainwrapper`. Equivalências aceitas
(o kit estático não tinha servidor): links de painel para visitante apontavam direto para `loginerror.html` /
`loginaccessdanied.html`; o cadastro postava para `register-resultado.html`; forms usavam `action="#"`; a lista
da Equipe só estava pré-renderizada na home.

## Páginas migradas

Home, fale conosco, downloads, rankings (resets, semanal, mensal, level, pk, master resets, guilds, gens das duas
famílias, perfil de personagem e de guild), cadastro, notícias (lista, leitura e comentários), screenshots (galeria,
imagem e voto), vips (vantagens, como comprar, preços), onlines, banidos, recuperar senha, login necessário e
acesso negado. Nos estados logados (usuário, GM, admin) a sidebar e os comentários batem com as páginas
`noticia-usuario/gm/admin` do kit.

Os painéis (`/paneluser…`, `/panelgamemaster…`, `/paneladmin…`) já respeitam sessão e privilégio como no
original (login necessário / acesso negado); com acesso, abrem a tela "em migração".

## Comportamento sem backend

- Login com as contas de `src/data/accounts.ts`; o cadastro cria contas no navegador (`localStorage`) que já
  podem logar — não há e-mail, então a ativação por e-mail não é exigida.
- Enquete, comentários (tamanho, intervalo de 1 min, exclusão pelo admin), votos em screenshot (1 a cada 24 h)
  e recuperação de senha seguem as regras das classes originais, com estado em memória.
- Personagens além do top 10 do kit (`src/data/characters.ts`) aparecem nas guilds, no gens Vanert e com
  `top` 50/100/200.
- Onde o original montava a mensagem numa variável que nunca era exibida (screenshot inexistente, recuperar
  senha sem login), o app mostra a mensagem.
