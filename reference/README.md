# sunonline-static

Casca visual do template **sunonline** (MuSite 2.5.4) extraída como HTML/CSS estático,
sem dependência do backend PHP/MSSQL.

## Páginas

| Arquivo | Origem | Conteúdo dinâmico reproduzido |
|---|---|---|
| `index.html` | `index.tpl.php` (home, moldura `#mainbody`) | `ldHome` (notícias, fórum, tops, castle siege), `ldGeneral` (gauges, enquete), `ldAjax` (chat, equipe, respostas da enquete), módulo screenshots |
| `contact.html` | `contact.tpl.php` | — (texto fixo no `.tpl`) |
| `downloads.html` | `downloads.tpl.php` | 4 constantes (`ldDownloads`) |
| `rankings.html` | `rankings.tpl.php` | `ldRankings::gerateRankingResets` (`?type=1&period=0`, Top 10 Resets) |
| `rankings-semanal.html`, `rankings-mensal.html` | `rankings.tpl.php` | `gerateRankingResets` com `period=1/2` (coluna de valor = `resetsweek`/`resetsmonth`) |
| `rankings-level.html`, `rankings-pk.html`, `rankings-master-resets.html` | `rankings.tpl.php` | `gerateRankingLevel` / `gerateRankingPks` / `gerateRankingMasterResets` (`type=2/4/5`): mesma tabela, outra coluna destacada e outra ordenação |
| `rankings-guilds.html` | `rankings.tpl.php` | `ldRankings::gerateRankingGuilds` (`type=3`): nome, GM, assistente, battle master, score |
| `rankings-personagem.html` | `rankings.tpl.php` | `ldRankings::geratePerfilCharacter` (`?type=6&name=`) |
| `rankings-guild.html` | `rankings.tpl.php` | `ldRankings::geratePerfilGuild` (`?type=7&name=`) — logo, score, GM, tabela de membros com `<th>` |
| `rankings-gens.html` | `rankings.tpl.php` | `ldRankings::gerateRankingGens` (`?type=8`, família Duprian) — logos de família/patente de `images/gens/`, graduação pela tabela de faixas do `GENS_MANUFACTURER=1` |
| `rankings-gens-vanert.html` | `rankings.tpl.php` | idem, `type=9` (família Vanert, `images/gens/vanert/`) |
| `register.html` | `register.tpl.php` | `{#REGISTER_OPTIONS_BONUS}` com kits e nomes de itens reais |
| `register-resultado.html` | `register.tpl.php` | `{#RESULT_REGISTER}` pós-cadastro conforme a settings (bônus VIP + item + e-mail de ativação) |
| `termos.html` | `termos.tpl.php` | — (carregado no `<iframe>` do cadastro) |
| `noticias.html` | `readNotice.tpl.php` (sem `id`) | `ldNotice::listAllNotices`; o bloco de comentários mostra "Noticia inválida." (é o que o ajax devolvia sem `id`) |
| `noticia.html` | `readNotice.tpl.php` (`?id=100`) | `ldNotice::openNoticeId` + comentários de `ldAjax::loadNoticeSelectComments` nos 3 formatos de autor (personagem sem foto, conta sem personagem, personagem com foto enviada) |
| `noticia-usuario.html`, `noticia-gm.html`, `noticia-admin.html` | idem | **estados logados**: `div[logout]` + `{#MENU_LOGIN}` do privilégio 0 / 1 / 2 (1, 2 e 3 painéis + Sair). Só o admin vê IP e [Excluir] nos comentários (`ldPanelAdmin::checkPermission`, privilégio ≥ 2). Não são linkadas: todo link do kit leva às páginas deslogadas |
| `screenshots.html` | `screenshots.tpl.php` via `loadModule…viewAll` | `screenshots::viewAll` (5 por linha) |
| `screenshot.html` | `screenshots.tpl.php` via `loadModule…viewImage&id=1` | `screenshots::viewScreenshot` + form de voto |
| `vips.html` | `vips.tpl.php` | — |
| `vips-vantagens.html` | `vips[advantages].tpl.php` | `ldVips::loadAdvantages` (8 tabelas) |
| `vips-como-comprar.html` | `vips[howtobuy].tpl.php` | — |
| `vips-como-comprar-vip.html` | `vips[howToBuyVips].tpl.php` | `ldVips::loadHowToBuyVips` (preços) |
| `onlines.html` | `onlines.tpl.php` | `ldOnlines` (`?room=all`): personagem, hora de conexão, resets, tipo de conta, servidor + "Total Onlines:" |
| `banned-personagens.html`, `banned-contas.html` | `banned.tpl.php` | `ldBanned` (`type=characters/accounts`): nome, expira, banido por, motivo |
| `recovery.html` | `recovery[password].tpl.php` | — (estado inicial do form; `{#ResultTpl}` vazio) |
| `loginerror.html` | `loginerror.tpl.php` | — ("Você precisa estar logado…"; é onde "Painel" leva quando deslogado) |
| `loginaccessdanied.html` | `loginaccessdanied.tpl.php` | — ("Acesso Negado"; no original aparece ao abrir o painel admin/GM sem privilégio — não é linkada) |
| `paneluser.html` | `paneluser.tpl.php` + `menuPanelUser.tpl.php` | `ldPanelUser::loadOptionsDefault` ("Minha Conta": plano, saldos, última conexão, personagens, aviso de personagem banido) + `setPermissions` (quadro "Permissões da Opção" no hover do menu). Logado como usuário comum; conta de exemplo **Vip Gold** (com Free, 8 opções cairiam em "Acesso negado") |
| `paneluser-desactive.html`, `paneluser-accessdanied.html` | `paneluser[DESACTIVE]` / `paneluser[ACCESSDANIED].tpl.php` | — ("Opção desativada" na settings / "Acesso negado" pelo plano da conta) |
| `paneluser-modify-data.html`, `-clean-vault`, `-double-vault`, `-modify-personalid`, `-game-disconnect`, `-transfer-cash`, `-collector-points` | `paneluser[<OPÇÃO>].tpl.php` | Grupo A ("Gerenciar Minha Conta") no **estado inicial**: formulários com os dados da conta de exemplo (nome, telefone, e-mail, pergunta secreta, saldos; item e pontos do coletor vêm da settings + banco de itens) |
| `paneluser-log-buys.html`, `-log-buys-cash`, `-log-buys-vips` | `paneluser[LOG_BUYS].tpl.php` | links de log (`Write=1/2`) e as duas listagens (`div.quadros` em `#logs_`), com os 3 status de compra |
| `paneluser-reset.html` / `-reset-kira`, `-master-reset`, `-clean-pk`, `-distribute-points`, `-move-character`, `-change-nick`, `-change-class`, `-redistribute-points`, `-clean-inventory`, `-manager-photo` (cada uma com `-kira`) | `paneluser[<OPÇÃO>].tpl.php` | Grupo B ("Gerenciar Personagens"), **gerado executando os métodos originais** (`_build/pumock.php`): sem personagem (só o `<select>`) e com Kira escolhido (`&character=Kira`) — caixa com requisitos/antes/depois do reset, formulários de pontos, mapas, classes, foto. Os scripts de validação que o PHP emite funcionam |
| `paneluser-reset-transfer.html`, `-reset-transfer-kira` | `paneluser[RESET_TRANSFER].tpl.php` | passo 1 (dois `<select>`) e passo 2 (Kira → Kirinha, campo de quantidade) |
| `paneluser-buy-vips.html`, `-confirm-payment`, `-open-complaints`, `-open-ticket`, `-send-sms` | `paneluser[<OPÇÃO>].tpl.php` | Grupo C ("Shop" e "Suporte"), executando os métodos originais: saldo e preços dos 5 VIPs (abrem com `AbreMenuV2`), formulário de comprovante com os 6 bancos (`show_hide_blocks`), denúncia com upload, pedido com personagens e setores, SMS com destinatários da settings |
| `paneluser-read-ticket.html`, `-read-ticket-view` | `paneluser[READ_TICKET].tpl.php` | lista de pedidos (aberto/fechado) e um pedido aberto com o histórico de respostas (mais nova primeiro) e o campo de resposta |
| `paneluser-virtual-vault.html` | `paneluser[VIRTUAL_VAULT].tpl.php` | Baú virtual: os dois baús (`images/vault/vault.png`) com itens reais do banco de itens do MuSite, gerados pelo `ldItemMake` e lidos pelo `ldItemParse` originais (dbVersion 3 / Season 6: level, opções, luck, skill, excelentes, serial, sockets). Clicar num item mostra o painel de detalhes que o ajax `action=details` devolvia (A23); o botão "Enviar" não faz nada |
| `paneluser-questions-event.html` | `paneluser.tpl.php` + `menuPanelUser.tpl.php` (`#questionsEvent`) | Evento de perguntas ("Show do Milhão"): "Minha Conta" no instante em que o sorteio acontece — o popup **fancybox original** abre sozinho com a pergunta (`webQuestions` de exemplo), 4 alternativas e o contador de 60 s. "Responder" mostra a resposta que o ajax devolvia conforme a opção marcada (A24): nenhuma, errada ou certa (prêmio sorteado da lista da settings + serial para o Golden Archer). Única página com jQuery (A2) |
| `paneluser-screenshot.html` | `paneluser[SCREENSHOT].tpl.php` via `?page=loadModule&module=screenshots&action=panelManager` | Gerenciador de screenshots, executando `panelManager()` do **módulo** original (`screenshots.module.php`, classe renomeada no build para não colidir com o stub da home): quantidade enviada, formulário de envio (legenda + arquivo) e lista para apagar. Aberta pelo `ldModules`, sem `setPermissions()` — ver desvios |
| `paneluser-auctions.html`, `paneluser-auctions-4/3/2/1.html` | `paneluser[AUCTIONS].tpl.php` | Leilões, executando `loadOptionsAuctions()`: a lista (4 status: em andamento, aguardando início, aguardando premiação, encerrado) e um leilão aberto por status — detalhes, foto (miniatura que abre a grande no **fancybox original**), último lance, seu lance e campo de lance; no 1, vencido pela conta, o bloco "Você ganhou este leilão!". Fotos: placeholders JPG gerados no build em `images/auctions/`. O botão de lance não faz nada (A22) |
| `paneluser-golden-archer.html` | `paneluser[GOLDEN_ARCHER].tpl.php` | Golden Archer: a arte original (`images/goldenarcher/interface.jpg`) com o texto, o campo de serial e "Registrar serial". O botão mostra a resposta que o ajax `action=check` devolvia para o serial digitado (A25), gerada executando `loadOptionsGoldenArcher()`: `4E6C-9713-01F7` (o serial que o evento de perguntas dá) → "Serial registrado! Recebido: Serpent Sword"; `7F21-B0C4-9E3A` → já ativado; `55D0-1A2B-C3E9` → de outra conta; vazio → preencha; qualquer outro → inválido |
| `panelgamemaster.html` | `panelgamemaster.tpl.php` + `menuPanelGamemaster.tpl.php` | Painel do GM (logado com privilégio 1): AdminCenter — dados do servidor web (`$_ENV['OS']`, `SERVER_SOFTWARE`, `SERVER_ADMIN`: valores de exemplo) e do banco (contas, personagens, VIPs, banidos) |
| `panelgamemaster-manager-ban-account.html`, `-manager-ban-character`, `-complaints`, `-tickets-operation`, `-tickets-completing`, `-tickets-view` | `panelgamemaster[<OPÇÃO>].tpl.php` | As 5 telas do GM, executando os métodos do `ldPanelAdmin` que o `ldPanelGameMaster` chama: formulários de ban, denúncias (foto placeholder em `images/complaints/`), pedidos abertos/encerrados (borda vermelha quando a última resposta não é do "Suporte") e um pedido aberto com histórico e resposta |
| `panelgamemaster-mensagens.html` | **página do kit** (moldura do AdminCenter; `gmCatalog()` em `_build/paneladmin.php`) | respostas das 5 telas do GM, extraídas de `ldpaneladmin.class.php` |
| `paneladmin.html` | `paneladmin.tpl.php` + `menuPanelAdmin.tpl.php` | Painel do admin (logado com privilégio 2): AdminCenter igual ao do GM + links [Sincronizar]; menu com 12 abas (`AbreMenu`, funciona) |
| `paneladmin-sincronize-vips.html`, `-sincronize-bans.html` | `paneladmin[SINCRONIZE].tpl.php` | [Sincronizar] `type=1` (VIPs vencidos: zeus, elfa) e `type=2` (banimentos vencidos: 1 conta, 1 personagem) |
| `paneladmin-edit-account.html`, `-edit-account-kira01.html`, `-delete-account`, `-manager-ban-account`, `-manager-accounts-transfer-cash` | `paneladmin[<OPÇÃO>].tpl.php` | Gerenciar contas: busca de login → formulário preenchido com a conta kira01 (a busca leva a ele); deletar; banir; lista de contas liberadas para transferir SGolds (zeus, elfa) |
| `paneladmin-edit-character.html`, `-edit-character-kira.html`, `-delete-character`, `-manager-ban-character` | `paneladmin[<OPÇÃO>].tpl.php` | Gerenciar personagens: busca → formulário do Kira (classes da `VESION_MUSERVER` 2, mapas da settings); deletar; banir |
| `paneladmin-complaints.html`, `-tickets-operation`, `-tickets-completing`, `-tickets-view` | `paneladmin[<OPÇÃO>].tpl.php` | Suporte: os mesmos métodos do painel do GM, com os links em `page=paneladmin` |
| `paneladmin-add-vip.html`, `-delete-vip`, `-transform-vip`, `-transform-vip-kira01` | `paneladmin[<OPÇÃO>].tpl.php` | Gerenciar vips: formulários; Transformar: busca → datas de início/término e plano da conta kira01 (a busca leva a ele) |
| `paneladmin-add-notice.html`, `-remove-notice`, `-modify-notice`, `-modify-notice-100` | `paneladmin[<OPÇÃO>].tpl.php` | Gerenciar notícias: as 5 notícias da home nos selects; Alterar: escolher → formulário da notícia 100 (qualquer escolha leva a ela) |
| `paneladmin-add-poll.html`, `-remove-poll`, `-modify-poll`, `-modify-poll-1`, `-modify-poll-2` | `paneladmin[<OPÇÃO>].tpl.php` | Gerenciar enquetes: a da home (id 1, ativa) e uma inativa (id 2); Alterar → uma página por enquete, com as respostas e os votos |
| `paneladmin-add-cash.html`, `-remove-cash` | `paneladmin[<OPÇÃO>].tpl.php` | Gerenciar SGolds: formulários |
| `paneladmin-deposits-in-operation.html`, `-deposits-completing`, `-deposits-false` | `paneladmin[<OPÇÃO>].tpl.php` | Gerenciar depósitos: os 3 do Log de Compras da kira01 (um por status) + 1 da zeus sem comprovante e com valor zerado ("Atenção no valor"); comprovantes → placeholders JPG em `images/deposits/` |
| `paneladmin-gerate-backups.html` | `paneladmin[GERATE_BACKUPS].tpl.php` | Gerar backup: bancos de `exec sp_databases` (master, model, msdb, MuOnline, tempdb) e nome sugerido com a data do build |
| `paneladmin-game-msg-all.html`, `-game-msg-specific`, `-game-disconnect`, `-game-chat-server` | `paneladmin[<OPÇÃO>].tpl.php` | Ferramentas para o jogo: contas online (zeus, elfa, mago) para mensagem e desconexão; chat do servidor com os 9 tipos de mensagem (decifradas por `getChatText()`); Atualizar/Carregar voltam à mesma página |
| `paneladmin-add-auctions.html`, `-edit-auctions`, `-edit-auctions-4`, `-delete-auctions`, `-close-auctions`, `-close-auctions-2` | `paneladmin[<OPÇÃO>].tpl.php` | Gerenciar leilões (os 4 do painel do usuário): Modificar → formulário do leilão 4; Fechar → leilão 2 com lances e foto (fancybox) — qualquer escolha leva a eles |
| `paneladmin-verify-update.html` | `paneladmin[VERIFY_UPDATE].tpl.php` | "Não estão disponiveis atualizações!" (o servidor de atualização do autor não existe mais); a lista de arquivos e o download estão no catálogo |
| `paneladmin-golden-archer.html`, `-golden-archer-create-serial` | `paneladmin[GOLDEN_ARCHER].tpl.php` | Golden Archer: menu → formulário de criar serial (todos os itens do `item.txt`, 768 harmonias, sockets) |
| `paneladmin-mensagens.html` | **página do kit** (moldura do AdminCenter; `paCatalog()`) | respostas das telas do admin já feitas; `alert()` aparece como texto |
| `paneluser-mensagens.html` | **página do kit** (moldura de `paneluser[DESACTIVE]`; conteúdo em `puCatalog()` de `_build/paneluser.php`) | respostas de cada opção do painel, no contêiner real de cada tela. Grupo A escrito à mão; grupos B, C e D **extraídos automaticamente** de cada método (inclusive textos fixos no PHP, como os do módulo de screenshots) (`puMessagesFrom()`): toda mensagem montada numa linha só, com a condição PHP que a dispara |
| `mensagens.html` | **página do kit** (moldura de `loginerror.tpl.php`; conteúdo em `_build/catalog.php`) | catálogo de **todas** as mensagens de resposta e estados vazios/de erro (~60): login, enquete, cadastro (resultado, ativação e validação campo a campo), recuperação de senha, comentários, screenshots, rankings/banidos/online/notícias vazios, home, e-mails enviados e saídas sem template. Texto da constante + marcação da classe PHP, dentro de uma réplica do contêiner real; login e enquete aparecem respondidos na própria sidebar. Constantes que não existem são sinalizadas |

O que ficou de fora (painéis, assets) e por quê: ver [O que não foi copiado](#o-que-não-foi-copiado).

## O que não foi copiado

Inventário completo do que existe no template original e não está no kit, agrupado pelo **motivo**.
Regra: se algo aparece no original e não aparece aqui, tem de estar nesta lista.

### 1. Pendente — dá para fazer, só não foi feito

Nada. Os três painéis estão completos e o `common.css` foi podado (ver [CSS](#css--_buildcssphp)).

### 2. Removido porque depende de servidor

| Item | No kit |
|---|---|
| Todo ajax (`?page=ajax&require=`: login, registerCheck, membersstaff, poll, chatrealtime, noticeselectcomments, noticecomment, noticedeletecomment) | chat, equipe e comentários → conteúdo de exemplo (A7, A15); login e enquete → a resposta real pré-renderizada, injetada no clique (A17, A19); todas as respostas possíveis → `mensagens.html` |
| Envio de formulários (cadastro, voto em screenshot, comentário) | cadastro → `register-resultado.html` (A12); voto → `onsubmit="return false;"` (A16); comentário → nada |
| Imagens geradas pelo PHP ou enviadas por jogadores: logo de guild (`logoGuildDecode.php`), captcha (`?page=captcha`), screenshots e fotos de personagem (`modules/uploads/`) | SVG `data:` (A10); fotos de leilão → JPG gerados no build (`images/auctions/`), porque o fancybox só abre arquivos de imagem |
| Dados do banco (contadores, nomes, notícias, tops, castle siege, chat, screenshots) | dados de exemplo |
| Destinos que não existem no kit: `SHOPPING_LINK` (a settings já traz `#LinkShop`), fórum (`LINK_FORUM`/`LINKS_TOPICS`), links de download (URLs mortas), "[Atualizar chat]", `ldManager/` no menu do admin (outra aplicação, não vem no repositório), botões de ação do painel do usuário (resetar, limpar, dar lance, enviar item) e do admin (salvar, remover, desconectar, apagar log, baixar atualização) | `#` / `#topic-N` / `javascript: void(0);` |
| Parâmetros que só mudam os dados: `top=50/100/200` (mais linhas), `onlines&room=<sala>` (filtra linhas), `name=` da busca de ranking (qualquer nome) | mesma página: top 10 / todas as salas / perfil de exemplo |
| Rotas sem tela própria: `captcha`, `ajax`, `custonPage` (abre qualquer `.tpl` pelo nome da URL), `loadModule` do `sample.module.php` (esqueleto vazio) | não se aplicam |
| Dados pessoais do autor original (conta bancária, telefone, nome) | placeholders (A13, A14); no painel, o nome do destinatário de SMS da settings (`$Config_SMS[0]['Name']`) vira "Administrador" em `puGroupCPages()` — o número já vem zerado na settings |

### 3. Descartado — morto ou obsoleto já no original

| Item | Por quê |
|---|---|
| jQuery, `ajax.js` | só serviam ao ajax. Exceção: `jquery.js` (1.4.2) volta em `js/`, junto com o fancybox, só nas páginas que abrem um fancybox (`paneluser-questions-event.html`, `paneluser-auctions-*.html`) — ver A2 |
| Cufon (`cufon-yui.js`, `cufon-replace.js`, 3 `*.font.js`) | trocado por `@font-face` + 6 regras CSS |
| `MAIAN.TTF` / `Maiandra_GD_400.font.js` | nenhum seletor do `cufon-replace.js` usa |
| `Common.js`, `jsGeneralFunction.js`, `jsBoardFunction.js`, `jsMenuLinkFunction.js`, `jsGameController.js`, `jsRegistText-pt.js` (Webzen) | nenhum `.tpl`/classe do MuSite chama essas funções, exceto as 2 reimplementadas em `main.js` (`input.login`, `raiseEnterAction`); `itemLayerOpen` só aparece em markup comentado |
| fancybox | só `ldpaneluser` e `ldpaneladmin` usam. Copiado para `js/fancybox/` (CSS, `pack.js`, mousewheel, imagens) e carregado só onde um fancybox abre (evento de perguntas, leilões abertos no painel do usuário e Fechar leilão do admin) |
| `global_gnb20101104.css` + `#global_gnb` | era só o aviso de IE6 (A2/A3) |
| Markup comentado da Webzen (item shop, banner central, notice/events, seletor de ranking, community, worldlink, servertime) | já era invisível no original; todos os links externos da Webzen (Twitter, Facebook, YouTube, payment, etc.) moravam nesses comentários — o único link externo vivo era o do aviso de IE6 |
| `images/shop`, `news`, `media`, `ranking`, `plugin`, `download`, `guide` | só seletores da Webzen no `common.css` os usam; 0 referências em `.tpl`/PHP |
| `images/pansite` | fora do CSS, só o ícone do aviso de IE6 (`header.tpl.php`, removido na A3) usa |
| 21 arquivos referenciados no `common.css` (`images/common/icon`, `common/temp`, `media`, `shop`, `ranking`, `../../../images/`) | não existem nem no original. Depois da poda, o CSS do kit aponta para só 2 ausentes (`../../../images/grad-1.gif` e `input-bg.gif`, em regras que o kit usa — ausentes também no original) |
| 82 imagens de `images/common` (`icon`, `temp`, `btn`, `title`), `images/main` e `images/event` | depois da poda do CSS, nenhuma página, CSS ou JS do kit as usa: 70 só serviam às regras podadas; 12 não são usadas nem no original (fora o `nav_pattern_bg.gif`, do CSS do aviso de IE6). Ficam em `templates/sunonline/images/`. Os ícones de patente 5–9 das gens (o ranking os escolhe pela patente) e o `loading.gif` (o `ajax.js` o mostra durante o carregamento) ficam no kit mesmo sem página que os use |
| 729 das 880 regras do `common.css` (803 dos 980 seletores) | seções da Webzen (shop, BBS, media, download, guide, ranking AC, eventos) e markup comentado: nenhum seletor casa com nenhuma página do kit; podadas no build (`_build/css.php`) |
| `Cópia de common.css` | backup do autor |
| Flash do YouTube | substituído por `<iframe>` do mesmo vídeo (A9) |

### 4. Fora do escopo

Os outros templates do repositório (`darkstyle` — que é o `TEMPLATE_DIR` ativo na settings —,
`refresh`, `ja_sanidine_free`), `changelog.html` (não é template) e outros idiomas (só existe `pt-br`).

## Como ver

```bash
python3 -m http.server 8765 --directory sunonline-static/reference
```

## Como é gerado — `_build/render.php`

As páginas **não são editadas à mão**: são renderizadas pelo harness a partir dos `.tpl.php`
originais, com a `settings.php` e os `languages/pt-br/*.lang.php` reais.

```bash
php sunonline-static/reference/_build/render.php              # todas
php sunonline-static/reference/_build/render.php index.html   # uma
```

O harness faz, nesta ordem:

1. Carrega `modules/settings.php` e os `.lang.php` (convertidos ISO-8859-1 → UTF-8).
2. Resolve `{#INCLUDE:x}` e `{#DIV[LOGIN_LOGOUT]}` como o `ldTpl` faz — `div[login]` (deslogado) ou,
   com `'login' => privilégio` na página, `div[logout]` + `{#MENU_LOGIN}` (`ldGeneral::loadDivLoginLogout`).
3. Substitui os `{#TAGS}` pelos valores que as classes `ld*` gerariam. **Estrutura HTML e textos
   são os das classes/constantes**; só os *dados* (nomes, números, datas) são de exemplo.
4. Avalia o PHP inline dos `.tpl` (`<?php if($PANELUSER_MODULE…) ?>`) no escopo global, com a
   settings real — por isso a sidebar mostra 5 planos VIP, o filtro de ranking tem Gens, etc.
5. Aplica as **adaptações estáticas** abaixo e grava o `.html`.

**Painel — `_build/pumock.php`.** A partir do grupo B as telas do painel não reimplementam o PHP: o harness
carrega o `ldpaneluser.class.php` original e **executa o método da tela** (via Reflection, sem o construtor)
contra um banco falso. O `FakeDB` lê a lista de colunas e a tabela de cada `SELECT` e responde com os
registros de exemplo (conta `kira01`, personagens Kira/Kirinha/KiraMG, VIP, saldos, pedidos de suporte),
com filtro por igualdades do `WHERE` e `ORDER BY` da primeira coluna; `UPDATE`/`INSERT`/`EXEC` não fazem nada. As tags que o método passa para `$ldTpl->set()` vão para o template. Resultado: marcação,
textos e cálculos (modos de reset, faixas, requisitos) saem do código original com a settings real.
Polyfills: `mssql_*`, `eregi`; constantes que a classe usa e nenhum `.lang.php` define viram o próprio nome,
como no PHP 5 (hoje só `LDPU_MOVE_CHARCTER_COORD_y`). Transformações na cópia carregada (nunca nos arquivos):
`exit(` vira uma exceção capturável (respostas de ajax saem por `exit`), e no `ldItemClass` a passagem por
referência na chamada (`f(&$x)`, PHP 5.3) perde o `&`.

**Segurança:** os métodos rodam com o diretório atual num espelho temporário da raiz do site
(`sys_get_temp_dir()/sunonline-static-build/site`) e com o log do painel desligado — o código original grava
arquivos (o `ldItemDatabase` regrava o banco de itens quando o `fileHash` não bate com o `item.txt`, o que
acontece com o banco que veio no site; no espelho o hash é acertado). Nada fora de `sunonline-static/reference/` é escrito.

Painéis do GM e do admin: `_build/paneladmin.php`. O `ldpaneladmin.class.php` é carregado com as mesmas proteções;
o GM chama `ldPanelAdmin::metodo()` estaticamente (no PHP 5 o `$this` era o do chamador; no PHP 8 é Error), então os
métodos rodam numa instância de `ldPanelAdmin` (`puRunClass()`), e as chamadas internas `ldPanelAdmin::x()` viram
`$this->x()` na cópia carregada. `$str{$i}` (removido no PHP 8) vira `$str[$i]`. O admin chama
`ldPanelUser::classNameDefine()` (Editar personagem) do mesmo jeito: roda numa instância de `ldPanelUser` sem construtor.
As linhas do banco falso são objetos com colunas sem distinção de maiúsculas, como no SQL Server (`SELECT *` e o
código lê `$obj->cLevel`). `tabela.coluna` vale como `coluna` (o JOIN das contas online vem pronto na linha do
`MEMB_STAT`), `exec sp_databases` devolve uma lista de bancos e `FakeDB::$fail` faz um comando falhar (mensagem de erro
do backup).

**Rede:** o Verificar atualização do admin baixa uma lista (e arquivos) de `daldegamserver.com` com o login e a senha
de `PASSWORD_UPDATE` da settings. Na cópia carregada, o `file_get_contents("http…")` e o `copy("http…")` viram stubs
locais (`puFakeRemoteGet/Copy`), e o build para se as duas trocas não acontecerem ou se sobrar qualquer chamada remota
no código carregado. Os envios ao JoinServer (mensagens, desconectar) usam socket e só rodam em ações que o build não
executa. O build não acessa a rede.

### CSS — `_build/css.php`

No build completo, depois das páginas, `buildCss()` gera `css/common.css`: bloco do kit + `common.css` original podado.
Cada seletor do original é testado contra as 144 páginas geradas (`Dom\HTMLDocument::querySelector`, PHP 8.4+), com as
respostas em `<template>` injetadas no alvo onde o `main.js` as coloca; `:hover`/`:visited`/`::before`… são testados
sem o estado (vale se o elemento existir). Fica o seletor que casa com alguma página; regra sem seletor sai;
comentário de seção sem regra e regra comentada saem; o resto (ordem, formatação, comentários) fica como no original.
Resultado: 151 de 880 regras, 100 KB → 20 KB.

Conferido no navegador: o estilo computado de todos os elementos das 144 páginas (54.692, com os templates
injetados, e o `content` de `::before`/`::after`) é idêntico com o CSS completo e com o podado; um controle negativo
(tirar uma regra usada) acusa diferença. Toda classe/id que as fontes originais geram (`.tpl`/PHP fora de comentários)
com estilo no CSS aparece em alguma página — os 14 seletores com essas classes que saíram apontam para elementos que só
existem em markup comentado.

Ao adicionar uma página que use regra já podada, o próximo build completo a traz de volta sozinho.

Para a próxima tela: adicionar a entrada em `$PAGES` (com `puRun('loadOptionsX', $_GET, $_POST)` no painel),
rodar, e comparar com o `.tpl`.

## Adaptações estáticas (todas em `adapt()` no `render.php`)

| # | O que | Por quê |
|---|---|---|
| A1 | Doctype XHTML 1.0 Strict / `charset=iso-8859-1` → HTML5 / `utf-8` | encoding |
| A2 | (Exceção: páginas em `KIT_JQUERY_PAGES` mantêm `jquery.js` e os 3 arquivos do fancybox originais, com caminho `js/`.) Removidos `global_gnb20101104.css` (só o aviso de IE6; referenciava `image.webzen.com`), CSS/JS do fancybox e **todos** os `<script src="_Script/…">` (Cufon, `*.font.js`, jQuery, `ajax.js`, `Common.js`, `jsMenuLinkFunction.js`, `jsGameController.js`, `jsRegistText-pt.js`, `jsBoardFunction.js`, `jsGeneralFunction.js`) | sem backend/ajax; Cufon → `@font-face` |
| A3 | Removidos o bloco condicional `<!--[if IE 6]>` e os blocos de markup comentados herdados do site da Webzen (community, worldlink, itemshop, servertime, etc.) | ruído |
| A4 | `Cufon.now()` no footer → `<script src="js/main.js">` | ver `js/main.js` |
| A5 | `href="?page=…"` → `.html` correspondente (`staticHref()`): `rankings&type=1..9[&period=]` → uma página por tipo/período; `readNotice[&id]` → noticias/noticia; `loadModule…viewAll/viewImage` → screenshots/screenshot; `onlines` → onlines; `banned&type=` → banned-personagens/contas; `recovery` → recovery; `logout` → index (o original faz `session_destroy` + volta à home); `paneluser[&option=]` → `loginerror.html` deslogado (é o que `ldPanelUser` abre sem sessão) e `paneluser[-<option>].html` logado, se já gerada (senão `#`); `paneladmin`/`panelgamemaster` → `loginaccessdanied.html` deslogado (`checkPermission` falha antes do teste de sessão) e `<painel>[-<option>].html` logado, se já gerada (`Ticket=` → `-tickets-view`, `SINCRONIZE&type=1/2` → `-sincronize-vips/bans`, buscas (`Write=true`: Editar conta/personagem, Transformar vip, Alterar notícia) e escolhas (`action=load/close/createSerial`: leilões, Golden Archer) → tela do exemplo, `MODIFY_POLL&action=modify&idPoll=N` → `-modify-poll-N`, `GAME_CHAT_SERVER&loadLasts=` → a mesma página; demais ações → `#`); `loadModule…panelManager` → `#` (gerenciador de screenshots do painel). Aplicada **por último**, depois das inserções por página | navegação estática |
| A6 | Atributos `onclick`/`onblur` que chamavam `Verify('?page=ajax…')` removidos (login, enquete, "[Atualizar chat]", validação campo a campo do cadastro, "Postar comentário", "[Excluir]" de comentário). Na caixa Equipe só a chamada `Verify` cai; o toggle fica. Aplicada por último | sem ajax |
| A7 | Home: `<script>Verify(…chatrealtime…)</script>` → 5 itens de exemplo (`li > div.chatList.<tipo>`), `#equipe` recebe lista de exemplo (`div.quadros`) | conteúdo que vinha por ajax |
| A8 | Home: `$("#maincenter div.newsforum ul li div").css("border-bottom","none")` (jQuery inline) → regra CSS `!important` no bloco adicionado de `common.css`. O `style` pontilhado inline que o PHP emite fica intacto | mesmo efeito visual sem jQuery |
| A9 | Home: `<object>` Flash do YouTube → `<iframe>` (mesmo vídeo) | Flash morto |
| A10 | Foto padrão de personagem `modules/uploads/photos/nophoto.jpg` (arquivo real) → `images/photos/nophoto.jpg`. Imagens geradas pelo servidor → SVG `data:` — logo de guild (`logoGuildDecode.php`), thumbs de screenshot (`modules/uploads/screenshots/`), captcha (`?page=captcha`, JPEG 135×25 preto/branco), foto enviada por jogador (`modules/uploads/photos/<arquivo>`) | sem backend |
| A11 | Rankings: o 2º `<select id="type">` (id duplicado no original) → `id="searchType"`. `checkPeriod()` lê o 1º via `getElementById`, então o comportamento é o mesmo | HTML válido |
| A12 | Cadastro: `action='?page=register&write=true'` → `register-resultado.html` (`method='post'` mantido); `document.cadastro.submit()` → `window.location='register-resultado.html'`; iframe `termos.tpl.php` → `termos.html` | fluxo demonstrável |
| A13 | `vips[howtobuy]`: **dados bancários reais do autor original** (banco, agência, conta, nome) → placeholders | privacidade |
| A14 | `contact`: **telefone e nome pessoais do autor original** → placeholder | privacidade |
| A15 | `noticia*`: `<script>loadComments()</script>` (ajax `noticeselectcomments`) → comentários de exemplo dentro de `#commentSelectReturn`, com IP/[Excluir] só em `noticia-admin.html` | conteúdo que vinha por ajax |
| A16 | `screenshot`: form de voto (`POST ?page=loadModule…voteImage`) → `action="#" onsubmit="return false;"` | sem backend |
| A17 | Enquete (todas as páginas): as respostas de `ldAjax::loadPoll` vão em 3 `<template>` logo após `#pollResult` — `pollVote` ("Voto computado."), `pollInvalid` ("Opção inválida.", nenhuma opção marcada) e `pollResults` (uma barra `.gauge` por resposta + "Total de votos:"). Com `$POLL['LOGIN'] = true` as duas primeiras viram "Logue-se para votar.", como no original deslogado | resposta que vinha por ajax |
| A18 | `recovery`: form (`POST ?page=recovery&type=password&Write=true`) → `action="#" onsubmit="return false;"` | sem backend |
| A19 | Login (todas as páginas): as respostas de `ldAjax::loginCheck` vão em 2 `<template>` após `#ResultAjaxLogin` — `loginFill` (`LOGIN_FILL_ALL_FIELDS`, campos vazios) e `loginError` (`LOGIN_ERROR`, preenchidos: não há contas) | resposta que vinha por ajax |
| A20 | Demais `<form action="?page=…">` (painel) → `action="#" onsubmit="return false;"`; se o destino tem tela gerada (passo 1 do Transferir Resets; buscas do admin: Editar conta/personagem, Transformar vip, Alterar notícia, Modificar/Fechar leilão) → `action="<tela>.html" method="get"`. Aplicada no fim, antes da A5 | sem backend |
| A21 | `<select>` de personagem do painel (`onchange="location='?…&character='+valor"`) → `location='paneluser-<opção>-kira.html'` (qualquer personagem abre a tela com Kira) | navegação estática |
| A22 | Botões `javascript:window.location='?page=…'` (também `window.location = '?…'+valor`) → `staticHref()`; ações sem tela (`reset=true`, `Write=…`, lance em leilão) → `#` | navegação estática |
| A23 | Ajax com resposta pré-renderizada (baú virtual): `onclick="Verify('url', 'alvo', 'get')"` → `data-kit-tpl`/`data-kit-target` + `<template>` após o `div` alvo; `main.js` injeta no clique. A6 também remove `onclick` com `jQuery.post(` (botão "Enviar" dos detalhes) | resposta que vinha por ajax |
| A24 | Evento de perguntas: botão "Responder" (`Verify('…QUESTIONS_EVENT&answer='+jQuery('#answerFinal').val(), 'reponseSubmit')`) → `data-kit-qe="<alternativa certa>"`; as 3 respostas (`qe-select`, `qe-correct`, `qe-wrong`) em `<template>` após `#reponseSubmit`; `main.js` injeta conforme a opção marcada e executa os `<script>` da resposta, como o `ajax.js` fazia | resposta que vinha por ajax |
| A25 | Golden Archer: o `<script>` que ligava o submit do form ao ajax (`$("#goldeArcherForm").submit(… Verify(…&serial=…) …)`, jQuery) sai; o form ganha `data-kit-ga` e as respostas por serial vão em `<template>` após `#serialResponse`; `main.js` escolhe pelo serial digitado (sem hífens, maiúsculo) | resposta que vinha por ajax |
| A26 | Notícias do admin (Adicionar, Alterar): o `onclick` do botão que codificava o texto em base64 antes do envio sai (sem o envio, o texto ficaria em base64 na tela); a função `base64Encode()` fica no HTML | sem backend |

Fora do `adapt()`:

- `termos.tpl.php` é um fragmento (`<style>` + `<div>`); vira documento completo em `renderTermos()`.
- `mensagens.html` é montada por `_build/catalog.php` (incluído pelo `render.php`); o `<style>` com as classes
  `kit*` (legendas, amostras da sidebar) só existe nessa página.
- `css/common.css` é gerado no build: `_build/common.header.css` (`@font-face` Alberta e Charlemagne Std, as 6 regras
  equivalentes ao `cufon-replace.js` — gradientes de texto → cor sólida — e a regra da A8) + o original convertido para
  UTF-8 e podado (ver [CSS](#css--_buildcssphp)). `MAIAN.TTF` (Maiandra GD) descartada: nenhum seletor usava.
- `js/main.js` reimplementa só `input.login` (Common.js), `raiseEnterAction` (jsGeneralFunction.js)
  e o toggle da Equipe; os botões da enquete injetam em `#pollResult` o `<template>` correspondente
  (A17), como o `Verify()` fazia; os forms "Carregar" e "Buscar" do ranking (GET `?page=rankings&type=…`)
  abrem a página gerada da combinação escolhida; o botão de login injeta em `#ResultAjaxLogin` o
  `<template>` da A19. Nenhum texto é inventado: tudo que o JS mostra vem de constantes do `.lang.php`.
- Saída sempre em UTF-8 válido (o build para se não for). Os nomes coreanos (EUC-KR) do `item.txt` no select do
  Golden Archer do admin eram bytes crus numa página iso-8859-1; no kit viram os mesmos caracteres Latin-1 que o
  navegador mostrava (ex.: `½ºÇǾî(µð)`).
- `js/jquery.js` e `js/fancybox/`: cópias literais dos originais, carregadas só pelas páginas da exceção da A2.
- `images/` (86 arquivos, 1,7 MB): só `common/`, `main/`, `event/`, `gens/`, `vault/`, `goldenarcher/interface.jpg` (o `interface.psd` de 2,8 MB, fonte em camadas da arte, fica no original), `auctions/` (gerados), `gauge.png` e `photos/nophoto.jpg` (copiada de `modules/uploads/`; o resto: ver [O que não foi copiado](#o-que-não-foi-copiado)).

## Desvios de fidelidade declarados

### Overrides da settings (bloco "1b" do `render.php`)

A `settings.php` original deixa dois recursos do template **desligados**; o kit os liga para
mostrar todos os componentes. A settings não é alterada — o override é só no harness.

| Flag na settings | Valor original | Efeito no original | No kit |
|---|---|---|---|
| `$FORUM_CONFIGS['ENABLE']` | `false` | bloco "Últimas do forum" da home não é renderizado | ligado; 5 tópicos de exemplo (`LAST_TOPICS`), `LINK_FORUM`/`LINKS_TOPICS` (URLs do fórum do servidor original) → `#` / `#topic-%d` |
| `$Config_Find_Rooms_ConnectServer['Enable']` | `false` | `{#GAMESERVERS_ON}` vazio — a caixa SERVIDORES mostra só "Total online" | ligado; 2 gauges de exemplo |

Para ver a home como a settings a deixaria, comente as 4 linhas do bloco 1b e rode o harness.

Além disso:

- **Dados de exemplo** nos `{#TAGS}` que vinham do banco (contadores, nomes de personagens/guilds,
  notícias, chat, enquete, screenshots). Nomes das salas de servidor são "Server 1/2" (os reais na
  settings são a marca do servidor original). `DOWNLOAD_*_LINK` (URLs mortas na settings) → `#`.
- `<title>` é `TITLE_SITE` da settings em todas as páginas, como no original.
- Estados logados: `noticia-usuario/gm/admin.html` e o painel (`paneluser*.html`, usuário comum); o resto do kit é deslogado.
- Painel: em Ver Pedidos, qualquer "Visualizar Ticket" abre o pedido 14 (`paneluser-read-ticket-view.html`).
- Painel: no grupo B, escolher qualquer personagem abre a tela de Kira (A21); o passo 1 do Transferir Resets
  vai para o passo 2 com GET em vez de POST (A20). No Master Reset, Kira não cumpre os requisitos da settings
  (400 resets e 32000 em cada atributo), então a caixa mostra as linhas de erro no lugar do botão — estado real.
- Painel do GM: o `AbreMenu()` do `menuPanelGamemaster.tpl.php` percorre `div1`…`div11`, mas o menu só tem 3 abas —
  a aba abre e o script para com erro no console ao procurar a `div4`. Mantido (bug do original). Banir por
  personagem sempre responde "login inválido" (a conta é validada antes de ser buscada pelo personagem).
- Painel do admin: em Editar conta, o rótulo da resposta secreta é "Telefone" (`LDPA_TEXT_SECRET_ANSWER` no
  `.lang.php`) e a senha aparece em texto (`USE_MD5` = 0). Em Editar personagem, os selects de classe e mapa não marcam
  o valor atual (o primeiro item fica escolhido; ao lado aparece "Atual: Blade Knight") e "Karutan" aparece duas vezes
  (lista de mapas da settings). Na lista de contas liberadas, toda linha tem `id='rowDc'` (`$i` nunca é definido). Os
  [Sincronizar] das 5 linhas de VIP levam todos a `type=1`. Mantidos.
- Painel do admin (grupo C): em Desconectar, o cabeçalho da coluna de conexão sai como
  `LDPA_GAME_DISCONNECT_TEXT_CONNECT_TM` (a constante não existe no `.lang.php`; no PHP 5 aparecia o nome). Em Fechar
  leilão, o status é calculado de `$getAuctions` (variável inexistente) e sai sempre "Aguardando premiação", e o
  `</form>` do formulário do vencedor nunca é escrito (`"</table>"; "</form>";`). O texto de erro do download de
  atualização é "Baixando" (`LDPA_VERIFY_UPDATE_DOWNLOADING_ERROR`). Mantidos.
- Painel do admin (grupo B): "Adcionar" nos títulos. Em Transformar vip, a hora vem de `date("h")` (12 h): o término
  às 20:15 aparece como 8:15; os segundos 00 não existem na lista (1–60) e os anos vão de ano atual −1 a +9, contados na
  data do build; o `selected` sai como `selected=\"selected\"` (barra literal) e funciona assim mesmo. Em Adicionar vips o
  select traz os 5 planos fixos no `.tpl`, ativos ou não. Nos depósitos, "Rejeitado / Removido" grava status 2 e o
  campo Valor não é salvo. Mantidos.
- Painel: `paneluser-screenshot.html` é aberta pelo `ldModules`, não pelo `ldPanelUser` — o `setPermissions()` não roda e
  as tags de permissão do menu (`{#MODIFY_DATA_FREE}`…) ficam **literais** no HTML, como no original: nessa tela o
  quadro "Permissões da Opção" mostra "Não" para todos os planos. Mantido (é a única página com `{#…}` de propósito).
- Painel do admin: o nome sugerido em Gerar backup usa a data fixa 23/09/2026 20:40 (no original, a hora de abertura
  da tela), para o build ser reprodutível.
- Painel: leilões com datas fixas (o "em andamento" termina em 31/12/2030) para o status não depender do dia do build;
  as fotos de `modules/uploads/auctions/` não vieram no repositório — placeholders JPG gerados pelo GD.
- Painel: `paneluser-questions-event.html` força o sorteio (`$GAME_QUESTION['LUCK']` = 9500..9500 só ao gerá-la); no
  original ele acontece em 5% das aberturas de qualquer tela do painel. Prêmio e serial são sorteados com semente fixa.
- Painel: a tabela de personagens escreve "NÃ£o" — o `ldpaneluser.class.php` (ISO-8859-1) tem esse "Não" gravado em
  UTF-8, e a página em iso-8859-1 exibia assim. Mantido literal. O evento de perguntas ("Show do Milhão", popup
  fancybox sorteado em 5% das aberturas do painel) não aparece nas outras telas: `{#questionResponse}` fica vazio, como
  fora do sorteio — o estado sorteado está em `paneluser-questions-event.html`. O `<ul>`
  da lista em `noticias.html` não é fechado — é assim no PHP.
- O que não foi gerado: ver [O que não foi copiado](#o-que-não-foi-copiado).

**Textos são literais** (inclusive erros de ortografia do original — "Bizavó", "atravez",
"Minimo Requirido"…): saem direto do `.tpl`/`.lang.php` pelo harness. Não corrigir à mão.

## Moldura

```
home:      #mainwrapper > .mainvisual + #mainheader + #mainbody (#mainleft + #maincenter + #mainright) + #subbottom + #footer
internas:  #mainwrapper > .mainvisual + #mainheader + #subbody  (#mainleft + #subright[.locationtitle + .sub_wrap]) + #subbottomPanel + #footer
```

`#mainleft` é `menuLeft.tpl.php` + `div[login].tpl.php` (ou `div[logout].tpl.php` nas `noticia-usuario/gm/admin.html`). Padrão de feedback do site
(usado pelo cadastro e por todo o painel): `div.quadrosOut > div.quadros` (resultado),
`div.qdestaques2` (info azul, `icon-info.gif`), `div.qdestaques` (erro vermelho, `icon-error.gif`).

## Limitações herdadas do original

- Largura fixa 990px, não responsivo; layout interno com `<table>` e `style=""` inline.
- `ldajax.lang.php` define `POLL_VOTE_SUCESS` duas vezes (a 2ª, "Erro ao votar.", deveria ser `POLL_VOTE_ERROR`,
  que não existe): no PHP 8 o ramo de erro do voto quebraria com constante indefinida.
- `ldRegister::registerNow` grava `REGISTER_EMPTY_INPUTS` em `$errorTmp` (variável que nunca é exibida):
  campos vazios não geram mensagem própria.
- `screenshots.module.php` monta "Screenshot não encontrada." numa variável local que não é devolvida:
  screenshot inexistente e galeria vazia saem como tabela vazia.
- Painel: o Coletor de pontos faz `return` antes de preencher `{#RESPONSE_WRITE}` quando a conta está online (a
  página exibe a tag literal); "Desconectar conta" diz "Você deve estar offline" quando a conta está **offline**
  (teste invertido); no Log de compras o status "Rejeitado" nunca aparece (o ternário testa `status == 0` duas
  vezes — status 2 vira "Erro de Status"). Tudo registrado em `paneluser-mensagens.html`.
- Painel: `LDPU_RESET_TEXT_QUESTS` vale "Itens" (a linha das quests no Reset/Master Reset aparece como "Itens:");
  Mover Personagem usa `LDPU_MOVE_CHARCTER_COORD_y` (o `.lang.php` define `…_COORD_Y`), impresso como o nome da
  constante — os dois mantidos literais.
- **Charlemagne Std** é fonte comercial da Adobe; o `.otf` veio com o template.
- O visual é derivado do site oficial do SUN Online (Webzen).
# sun
