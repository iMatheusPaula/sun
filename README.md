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
  layouts/     HomeLayout (3 colunas), InnerLayout (sidebar + breadcrumb/título) e PanelUserLayout (menu do painel)
  components/  layout/, sidebar/ (login, enquete, equipe), rankings/, notices/, paneluser/ (menu, evento de
               perguntas, escolha de personagem), ui/ (BoxMenu, GaugeBar, FancyBox)
  views/       uma por página; rota = nome da página do kit sem ".html"; views/paneluser/ = uma por opção do painel
  data/        dados de exemplo (contas de teste em data/accounts.ts; "banco" do painel em data/db.ts)
  stores/      sessão, contas, banco do painel (db), evento de perguntas, enquete, notícias/comentários, screenshots
  lib/         regras portadas das classes ld* (rankings, reset, painel, itens/baú, imagens) e date() do PHP
  config/      settings.ts — chaves de exibição da settings.php (sem banco, SMTP ou senhas)
  lang/        um módulo por .lang.php original; "// html" marca os que têm marcação (v-html)
  styles/      common.css global (cópia do kit, editável) e fancybox.css (lightbox do painel)
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
__resetDb()                      // volta o banco do painel aos dados de exemplo
```

Compara tag, atributos, texto e posição/tamanho de cada elemento de `#mainwrapper`. Equivalências aceitas
(o kit estático não tinha servidor): links de painel para visitante apontavam direto para `loginerror.html` /
`loginaccessdanied.html`; o cadastro postava para `register-resultado.html`; forms usavam `action="#"`; a lista
da Equipe só estava pré-renderizada na home; páginas do kit que são estados de uma rota (`-kira`, `-auctions-N`,
`log-buys-cash`…) comparam com a rota e a query do app; o `{#questionResponse}` que o kit mostra cru no bloco
escondido do evento de perguntas não é reproduzido. O `__parityAll` desliga o sorteio do evento e abre a pergunta
só na página `paneluser-questions-event`.

## Páginas migradas

Home, fale conosco, downloads, rankings (resets, semanal, mensal, level, pk, master resets, guilds, gens das duas
famílias, perfil de personagem e de guild), cadastro, notícias (lista, leitura e comentários), screenshots (galeria,
imagem e voto), vips (vantagens, como comprar, preços), onlines, banidos, recuperar senha, login necessário e
acesso negado. Nos estados logados (usuário, GM, admin) a sidebar e os comentários batem com as páginas
`noticia-usuario/gm/admin` do kit.

**Painel do usuário** (`/paneluser` e `/paneluser-<opção>`, conta de teste `kira01`): todas as opções do
`menuPanelUser` — dados da conta, baús (limpar, duplo, virtual), Golden Archer, desconectar, coletor de pontos,
leilões, transferir SGolds, logs de compras, personal ID, gerenciar personagens (reset, master reset,
transferir resets, limpar PK, distribuir/redistribuir pontos, mover, trocar nick/classe, limpar inventário,
foto), comprar vips, confirmar pagamento, denúncias, tickets, SMS, screenshots — e o evento de perguntas
("Show do Milhão") no popup. Opção desligada, plano sem permissão e plano vencido se comportam como no original.

Os painéis de GM e admin (`/panelgamemaster…`, `/paneladmin…`) respeitam sessão e privilégio (login necessário /
acesso negado); com acesso, ainda abrem a tela "em migração".

## Comportamento sem backend

- Login com as contas de `src/data/accounts.ts`; o cadastro cria contas no navegador (`localStorage`) que já
  podem logar — não há e-mail, então a ativação por e-mail não é exigida.
- Enquete, comentários (tamanho, intervalo de 1 min, exclusão pelo admin), votos em screenshot (1 a cada 24 h)
  e recuperação de senha seguem as regras das classes originais, com estado em memória.
- Personagens além do top 10 do kit (`src/data/characters.ts`) aparecem nas guilds, no gens Vanert e com
  `top` 50/100/200.
- Onde o original montava a mensagem numa variável que nunca era exibida (screenshot inexistente, recuperar
  senha sem login, coletor de pontos com a conta online), o app mostra a mensagem.

### Painel do usuário

- O "banco" (`src/data/db.ts`: contas, personagens, baús, tickets, leilões, seriais do Golden Archer…) começa
  com os dados do FakeDB do kit e as alterações ficam no navegador (`localStorage`, `sunonline:db`). Mudou o
  seed ou a estrutura? Suba `VERSION` em `src/stores/db.ts`.
- Arquivos enviados (foto do personagem, comprovante, denúncia, screenshot) viram data URL no banco; a
  screenshot é reduzida como o módulo original fazia (até 640 px de largura + miniatura 100×100 em JPEG).
- O SMS não sai daqui (o original mandava e-mail para a operadora); a tela responde como no envio.
- O evento de perguntas sorteia a cada página do painel aberta (5%, com espera de 180 s), não nos envios de
  formulário; o estado da pergunta vale para a aba (`sessionStorage`), como a sessão do PHP.
- Mantidos do original: o lance no leilão não desconta pontos; o item do Golden Archer nasce sem durabilidade
  base; no log de compras de SGolds, o comprovante rejeitado aparece como "Erro de Status" (o ternário do
  original testava o status 0 duas vezes).
- Corrigidos (quebravam a opção): a checagem invertida do "desconectar do jogo" e a extensão da screenshot em
  maiúsculas (`FOTO.JPG`), que o original recusava.
- As screenshots do painel ainda não aparecem na galeria pública, e os personagens do painel não entram nos
  rankings: o kit usava dados de exemplo diferentes em cada página (unificação prevista para a limpeza).
