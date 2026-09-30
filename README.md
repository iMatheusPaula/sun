# sunonline

Frontend (Vue 3 + Vite + TypeScript) do template **sunonline**, migrado do MuSite. Por enquanto é 100% front:
um "banco" de exemplo (`src/data/seed.ts`) guardado no navegador faz o papel do SQL Server, e o estado fica no
Pinia (`src/stores/`).

```bash
bun install
bun run dev        # http://localhost:5173
bun run typecheck
bun run build
bun run kit:seed   # grava o seed em reference/_build/seed.json (dados do kit de referência)
```

## Estrutura

```
src/
  layouts/     HomeLayout (3 colunas), InnerLayout (sidebar + breadcrumb/título), PanelUserLayout e PanelAdminLayout
               (moldura com o menu de cada painel)
  components/  layout/, sidebar/ (login, enquete, equipe), rankings/, notices/, paneluser/ (menu, evento de
               perguntas, escolha de personagem), paneladmin/ (menus do GM e do admin), ui/ (BoxMenu, GaugeBar, FancyBox)
  views/       uma por página; rota = nome da página do kit sem ".html"; views/paneluser/ e views/paneladmin/ = as
               opções dos painéis (o GM usa as telas do admin de banimentos, denúncias e tickets)
  data/        db.ts (tabelas do "banco" do front) e seed.ts (os dados de exemplo — a fonte única do site
               inteiro); conteúdo fixo da moldura e da home (site.ts, home.ts); placeholders de imagem; itens em
               itemdb.ts e itemoptions.ts, exportados uma vez do ldItemClass
  stores/      sessão, contas, banco (db), evento de perguntas, enquete, notícias/comentários, screenshots
  lib/         regras portadas das classes ld* (rankings e guilds, contadores da lateral, reset, painéis, itens/baú,
               leilões, imagens) e date() do PHP
  config/      settings.ts — chaves de exibição da settings.php (sem banco, SMTP ou senhas)
  lang/        um módulo por .lang.php original; "// html" marca os que têm marcação (v-html)
  styles/      common.css global (cópia do kit, editável) e fancybox.css (lightbox do painel)
  dev/         ferramenta de paridade (só em dev)
scripts/       export-seed.ts (`bun run kit:seed`)
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
__resetDb()                      // volta o banco do front aos dados de exemplo
```

O kit é gerado com os mesmos dados do app. Mexeu em `src/data/seed.ts`? Rode `bun run kit:seed` e, da raiz do
MuSite-template, `php sunonline-static/reference/_build/render.php` antes de comparar.

Compara tag, atributos, texto e posição/tamanho de cada elemento de `#mainwrapper`. Equivalências aceitas
(o kit estático não tinha servidor): links de painel para visitante apontavam direto para `loginerror.html` /
`loginaccessdanied.html`; o cadastro postava para `register-resultado.html`; forms usavam `action="#"`; a lista
da Equipe só estava pré-renderizada na home; páginas do kit que são estados de uma rota (`-kira`, `-auctions-N`,
`log-buys-cash`…) comparam com a rota e a query do app; o `{#questionResponse}` que o kit mostra cru no bloco
escondido do evento de perguntas não é reproduzido. O `__parityAll` desliga o sorteio do evento e abre a pergunta
só na página `paneluser-questions-event`.

Nos painéis de GM/admin o kit trocou por `#` os links que executavam uma ação (remover, desconectar…) e fez dos
formulários de "carregar" (editar conta/personagem, transformar vip, alterar notícia, modificar/fechar leilão) um GET
para a página do estado já carregado: nesses casos o destino e o método não são comparados, e a entrada do
`__parityAll` envia o formulário no app (`act`). Cada grupo dos menus também é comparado aberto (`menu`, chamando o
`AbreMenu` do kit). Diferenças de propósito ficam listadas na entrada (`ignore`): o select de classe do Editar
personagem vem com a classe atual marcada, o nome sugerido do backup tem a data de agora e alguns nomes coreanos
do item.txt que o kit, gerado em UTF-8, juntou em outro caractere.

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

**Painel do game master** (`/panelgamemaster…`, conta `gm01`) e **do admin** (`/paneladmin…`, conta `admin`):
AdminCenter, banir/desbanir conta e personagem, denúncias, tickets (responder, fechar, reabrir) e, no admin,
backup, ferramentas do jogo (mensagens, desconectar, chat), contas (editar, deletar, liberar transferência),
personagens (editar, deletar), sincronizar vips/banimentos vencidos, vips, notícias, enquetes, SGolds, depósitos,
leilões, verificar atualização e Golden Archer (criar serial). Sem login ou sem privilégio abrem o acesso negado.

Endereço desconhecido abre a home, como o `default` do `index.php` original.

## Comportamento sem backend

- Um banco só para o site inteiro (`src/data/seed.ts`, guardado no `localStorage` em `sunonline:db`): rankings,
  tops e chat da home, perfis de personagem e guild, gens, onlines, banidos, equipe, screenshots e os contadores da
  lateral saem das mesmas tabelas que os painéis alteram — reset no painel muda o ranking, o ban do admin aparece em
  banidos, a screenshot enviada entra na galeria, desconectar tira dos onlines. Mudou o seed ou a estrutura? Suba
  `VERSION` em `src/stores/db.ts` (o estado salvo antigo é descartado).
- As consultas seguem as das classes originais (filtros, `ORDER BY`, `JOIN`s; sem `ORDER BY`, a ordem da tabela).
  Os rankings leem os personagens direto (o original lia as tabelas que o cron gerava, TOP 200 sem a equipe), então
  mudam na hora. A lotação das salas (caixa SERVIDORES) vinha do ConnectServer e o fórum é outro sistema: esses
  continuam de exemplo.
- Contadores da lateral: como o `ldGeneral`, são os do começo da página — o que a própria página altera (Sincronizar,
  um formulário) aparece na próxima.
- As datas de exemplo já passaram ou ficam em 2030: nada vence sozinho com o tempo (vip da `kira01`, banimentos).
- Login com as contas de teste do seed (`kira01`, `gm01`, `admin`); o cadastro cria contas no navegador que já
  podem logar — não há e-mail, então a ativação por e-mail não é exigida.
- Enquete, comentários (tamanho, intervalo de 1 min, exclusão pelo admin), votos em screenshot (1 a cada 24 h)
  e recuperação de senha seguem as regras das classes originais; a trava de voto em screenshot fica em memória.
- Onde o original montava a mensagem numa variável que nunca era exibida (screenshot inexistente, recuperar
  senha sem login, coletor de pontos com a conta online), o app mostra a mensagem.

### Painel do usuário

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
- Trocar nick respeita a guild (quem está em uma não troca), como no original.

### Painéis do GM e do admin

- Sem JoinServer: desconectar tira a conta do jogo e as mensagens para os onlines são dadas como enviadas (os
  `alert()` são os do original). Sem SQL Server: o backup baixa o banco do front em JSON com o nome do arquivo.
  Verificar atualização não tem mais servidor (o do autor saiu do ar), então nunca há atualização.
- As ações que eram links no original (remover conta liberada, desconectar, apagar denúncia, remover enquete ou
  resposta, fechar/reabrir ticket, sincronizar, apagar o log do chat) continuam sendo endereços com a query
  (`?action=…`), executados ao abrir a tela, como no PHP.
- Fotos de leilão enviadas pelo admin ficam no próprio leilão (800 px de largura + miniatura 150×150).
- Deletar conta ou personagem tira o personagem da guild; o mestre leva a guild inteira junto (membros e guild),
  como no original.
- Corrigidos: banir conta pelo personagem (o original validava o login antes de buscá-lo e sempre dava "Login
  inválido"), a classe e o mapa marcados no Editar personagem (salvar sem mexer trocava para Dark Wizard/Lorencia),
  o status do leilão no Fechar leilão (saía sempre "Aguardando premiação") e o comentário do admin nos depósitos
  (voltava com `<br />` acumulado a cada salvamento).
- Mantidos: o formulário de resposta do ticket aparece também no ticket encerrado (é por ele que se reabre), as
  listas de hora/minuto/segundo do Transformar vip (sem o 0 e com a hora de 12 h), fechar leilão desclassifica ao
  abrir a tela quem não tem mais os pontos do lance, o nome da coluna "Conectou as" do Desconectar sai como o nome da
  constante que faltava no .lang.php, e o log de ações do admin (arquivos no servidor) não existe no front.
