<?php
/*
 * Painéis do GM e do admin (ldpanelgamemaster.class.php + ldpaneladmin.class.php). Os métodos originais rodam via
 * puRunClass('ldPanelAdmin', …) contra o banco falso de pumock.php.
 * Painel do GM: logado com privilégio 1; as 5 telas usam métodos do ldPanelAdmin.
 * Painel do admin: logado com privilégio 2; feito por grupos (paGroupAPages…).
 */

// optionLoadAdminCenter lê $_ENV['OS'], $_SERVER['SERVER_SOFTWARE'] e $_SERVER['SERVER_ADMIN'] do servidor web: exemplo
function paServerEnv(callable $fn)
{
    $prev = [$_ENV['OS'] ?? null, $_SERVER['SERVER_SOFTWARE'] ?? null, $_SERVER['SERVER_ADMIN'] ?? null];
    [$_ENV['OS'], $_SERVER['SERVER_SOFTWARE'], $_SERVER['SERVER_ADMIN']] = ['Windows_NT', 'Apache/2.2.17 (Win32) PHP/5.2.17', 'admin@example.com'];
    $r = $fn();
    [$_ENV['OS'], $_SERVER['SERVER_SOFTWARE'], $_SERVER['SERVER_ADMIN']] = $prev;
    return $r;
}

function gmPage(string $tpl, array $tags = []): array
{
    return ['tpl' => $tpl, 'tags' => $tags, 'login' => 1];
}

// executa um método do ldPanelAdmin como o painel do GM o chama (page=panelgamemaster nos links que ele monta)
function gmRun(string $method, array $args = [], array $get = [], array $post = []): array
{
    return puRunClass('ldPanelAdmin', $method, $args, ['page' => 'panelgamemaster'] + $get, $post);
}

// placeholder JPG (fotos enviadas que não vieram no repositório): gerado no build com o GD
function kitPlaceholderJpg(string $file, int $w, int $h, string $label): void
{
    @mkdir(dirname($file), 0777, true);
    $im = imagecreatetruecolor($w, $h);
    imagefill($im, 0, 0, imagecolorallocate($im, 0x34, 0x33, 0x36));
    imagestring($im, 5, (int)(($w - imagefontwidth(5) * strlen($label)) / 2), (int)(($h - imagefontheight(5)) / 2), $label, imagecolorallocate($im, 0xC5, 0xC0, 0xAD));
    imagejpeg($im, $file, 85);
    imagedestroy($im);
}

function gmPages(): array
{
    global $OUT;
    puSeedDB();
    foreach (FakeDB::$tables['webcomplaints'] as $c) {   // fotos de denúncia: modules/uploads/complaints/ -> images/complaints/
        kitPlaceholderJpg("$OUT/images/complaints/" . basename($c['image']), 640, 480, 'denuncia ' . $c['id'] . ' - 640x480');
    }
    $tags = fn(array $t) => array_diff_key($t, ['__echo' => 1, '__exit' => 1]);
    return [
        'panelgamemaster.html'                       => gmPage('panelgamemaster', $tags(paServerEnv(fn() => gmRun('optionLoadAdminCenter')))),
        'panelgamemaster-manager-ban-account.html'   => gmPage('panelgamemaster[MANAGER_BAN_ACCOUNT]', $tags(gmRun('optionLoadManagerBanAccounts', [], ['option' => 'MANAGER_BAN_ACCOUNT']))),
        'panelgamemaster-manager-ban-character.html' => gmPage('panelgamemaster[MANAGER_BAN_CHARACTER]', $tags(gmRun('optionLoadManagerBanCharacters', [], ['option' => 'MANAGER_BAN_CHARACTER']))),
        'panelgamemaster-complaints.html'            => gmPage('panelgamemaster[COMPLAINTS]', $tags(gmRun('optionLoadManagerComplaints', [], ['option' => 'COMPLAINTS']))),
        'panelgamemaster-tickets-operation.html'     => gmPage('panelgamemaster[TICKETS_OPERATION]', $tags(gmRun('optionLoadManagerTickets', [0], ['option' => 'TICKETS_OPERATION']))),
        'panelgamemaster-tickets-completing.html'    => gmPage('panelgamemaster[TICKETS_COMPLETING]', $tags(gmRun('optionLoadManagerTickets', [1], ['option' => 'TICKETS_COMPLETING']))),
        'panelgamemaster-tickets-view.html'          => gmPage('panelgamemaster[TICKETS_OPERATION]', $tags(gmRun('optionLoadManagerTickets', [0], ['option' => 'TICKETS_OPERATION', 'Ticket' => '14']))),
        // página do kit: respostas das 5 telas (moldura do AdminCenter)
        'panelgamemaster-mensagens.html'             => gmPage('panelgamemaster', $tags(paServerEnv(fn() => gmRun('optionLoadAdminCenter')))),
    ];
}

function gmCatalog(): string
{
    return panelCatalog('panelgamemaster', [
        ['Banir / Desbanir conta', 'manager-ban-account', 'optionLoadManagerBanAccounts', '<div class="quadrosOut">%s</div>',
         'No <code>div.quadrosOut</code> abaixo do formulário. Banir informando só o personagem sempre cai em <code>LDPA_INVALID_LOGIN</code>: a conta é validada antes de ser buscada pelo personagem (bug do original).'],
        ['Banir / Desbanir personagem', 'manager-ban-character', 'optionLoadManagerBanCharacters', '<div class="quadrosOut">%s</div>', ''],
        ['Denuncias', 'complaints', 'optionLoadManagerComplaints', '<div class="quadrosOut" id="logs_">%s</div>', ''],
        ['Tickets', 'tickets-operation', 'optionLoadManagerTickets', '<div class="quadrosOut" id="logs_">%s</div>',
         'Tickets Abertos e Encerrados usam o mesmo método. Na lista, a borda vermelha marca pedidos cuja última resposta não é do "Suporte". No pedido aberto, o formulário de resposta aparece mesmo com o pedido fechado (o teste usa a variável do laço de respostas).'],
    ], 'painel do GM');
}

// <script> alert('…') </script> (ou alert("…")) -> caixa com o texto do alert(), sem executar; \n vira quebra de linha
function paAlert(string $html): ?string
{
    if (!preg_match('~^<script[^>]*>\s*alert\((["\'])(.*)\1\);\s*</script>$~s', trim($html), $al)) return null;
    return "<div class=\"kitSample\" style=\"background: #fff; color: #000; border: 1px solid #999;\">alert: "
         . str_replace('\n', '<br />', htmlspecialchars(html_entity_decode($al[2]))) . "</div>";
}

// catálogo de respostas de um painel: [título, slug da página, método do ldPanelAdmin, contêiner, introdução]
function panelCatalog(string $prefix, array $sections, string $label, string $extra = ''): string
{
    global $PA_SOURCE;
    $out = '';
    foreach ($sections as [$title, $slug, $method, $wrap, $intro]) {
        $page = "{$prefix}-{$slug}.html";
        $body = '';
        foreach (puMessagesFrom($method, $PA_SOURCE) as [$var, $consts, $cond, $html]) {
            // pedaços de formulário e de lista montados linha a linha (campos, rótulos soltos, "Respostas:") não são mensagens
            if (preg_match('~<(select|input|option|form|textarea)\b~i', $html) || preg_match('~^<(strong|em)>[^<]*</\1>\s*$~', trim($html))
                || str_ends_with(trim(strip_tags($html)), ':')) continue;
            $when = $cond !== '' ? '<code>' . htmlspecialchars(mb_strimwidth($cond, 0, 170, '…')) . '</code>' : '(sem condição na mesma linha)';
            // <script> alert('…') </script>: mostra o texto do alert() em vez de executar
            $sample = paAlert($html) ?? sprintf($wrap, $html);
            $body .= catItem($consts, $when, $sample);
        }
        if ($body === '') $body = catItem('', 'Nenhuma mensagem de uma linha só neste método.', '');
        $out .= catSection("{$title} — <a href=\"{$page}\">{$page}</a>:", ($intro !== '' ? $intro . ' ' : '') . "Extraídas de <code>ldPanelAdmin::{$method}()</code>.", $body);
    }
    return catStyle()
        . "<div class=\"qdestaques2\">Página do kit (não existe no template original): as respostas de cada opção do {$label}, com o texto da constante do <code>ldpaneladmin.lang.php</code> e a marcação de <code>ldpaneladmin.class.php</code>, dentro do contêiner onde aparecem.</div>"
        . $out . $extra;
}

// ---------- painel do admin (privilégio 2) ----------
function paPage(string $tpl, array $tags = []): array
{
    return ['tpl' => $tpl, 'tags' => array_diff_key($tags, ['__echo' => 1, '__exit' => 1]), 'login' => 2];
}

// executa um método do ldPanelAdmin como o painel do admin (page=paneladmin nos links que ele monta)
function paRun(string $method, array $args = [], array $get = [], array $post = [], ?callable $patchDB = null): array
{
    return puRunClass('ldPanelAdmin', $method, $args, ['page' => 'paneladmin'] + $get, $post, [], $patchDB);
}

// grupo A: AdminCenter, Sincronizar, Contas, Personagens, Suporte
function paGroupAPages(): array
{
    $center = fn() => paServerEnv(fn() => paRun('optionLoadAdminCenter'));
    return [
        'paneladmin.html'                                => paPage('paneladmin', $center()),
        // [Sincronizar] do AdminCenter: type=1 VIPs vencidos, type=2 banimentos vencidos
        'paneladmin-sincronize-vips.html'                => paPage('paneladmin[SINCRONIZE]', paRun('optionLoadSincronize', [1], ['option' => 'SINCRONIZE', 'type' => '1'])),
        'paneladmin-sincronize-bans.html'                => paPage('paneladmin[SINCRONIZE]', paRun('optionLoadSincronize', [2], ['option' => 'SINCRONIZE', 'type' => '2'])),
        // Contas (busca -> formulário preenchido com a conta kira01)
        'paneladmin-edit-account.html'                   => paPage('paneladmin[EDIT_ACCOUNT]', paRun('optionLoadEditAccount', [], ['option' => 'EDIT_ACCOUNT'])),
        'paneladmin-edit-account-kira01.html'            => paPage('paneladmin[EDIT_ACCOUNT]', paRun('optionLoadEditAccount', [], ['option' => 'EDIT_ACCOUNT', 'Write' => 'true'], ['account' => 'kira01'])),
        'paneladmin-delete-account.html'                 => paPage('paneladmin[DELETE_ACCOUNT]', paRun('optionLoadDeleteAccount', [], ['option' => 'DELETE_ACCOUNT'])),
        'paneladmin-manager-ban-account.html'            => paPage('paneladmin[MANAGER_BAN_ACCOUNT]', paRun('optionLoadManagerBanAccounts', [], ['option' => 'MANAGER_BAN_ACCOUNT'])),
        'paneladmin-manager-accounts-transfer-cash.html' => paPage('paneladmin[MANAGER_ACCOUNTS_TRANSFER_CASH]', paRun('optionLoadManagerAccountsTransferCash', [], ['option' => 'MANAGER_ACCOUNTS_TRANSFER_CASH'])),
        // Personagens (busca -> formulário preenchido com o Kira)
        'paneladmin-edit-character.html'                 => paPage('paneladmin[EDIT_CHARACTER]', paRun('optionLoadEditCharacter', [], ['option' => 'EDIT_CHARACTER'])),
        'paneladmin-edit-character-kira.html'            => paPage('paneladmin[EDIT_CHARACTER]', paRun('optionLoadEditCharacter', [], ['option' => 'EDIT_CHARACTER', 'Write' => 'true'], ['character' => 'Kira'])),
        'paneladmin-delete-character.html'               => paPage('paneladmin[DELETE_CHARACTER]', paRun('optionLoadDeleteCharacter', [], ['option' => 'DELETE_CHARACTER'])),
        'paneladmin-manager-ban-character.html'          => paPage('paneladmin[MANAGER_BAN_CHARACTER]', paRun('optionLoadManagerBanCharacters', [], ['option' => 'MANAGER_BAN_CHARACTER'])),
        // Suporte (mesmos métodos do painel do GM)
        'paneladmin-complaints.html'                     => paPage('paneladmin[COMPLAINTS]', paRun('optionLoadManagerComplaints', [], ['option' => 'COMPLAINTS'])),
        'paneladmin-tickets-operation.html'              => paPage('paneladmin[TICKETS_OPERATION]', paRun('optionLoadManagerTickets', [0], ['option' => 'TICKETS_OPERATION'])),
        'paneladmin-tickets-completing.html'             => paPage('paneladmin[TICKETS_COMPLETING]', paRun('optionLoadManagerTickets', [1], ['option' => 'TICKETS_COMPLETING'])),
        'paneladmin-tickets-view.html'                   => paPage('paneladmin[TICKETS_OPERATION]', paRun('optionLoadManagerTickets', [0], ['option' => 'TICKETS_OPERATION', 'Ticket' => '14'])),
        // página do kit: respostas das telas do admin (moldura do AdminCenter)
        'paneladmin-mensagens.html'                      => paPage('paneladmin', $center()),
    ];
}

function paCatalog(): string
{
    return panelCatalog('paneladmin', [
        ['Editar conta', 'edit-account', 'optionLoadEditAccount', '<div class="quadrosOut">%s</div>',
         'No <code>div.quadrosOut</code> abaixo do formulário (em todas as telas do admin, salvo indicação). O formulário preenchido (busca com sucesso) está em <a href="paneladmin-edit-account-kira01.html">paneladmin-edit-account-kira01.html</a>.'],
        ['Deletar conta', 'delete-account', 'optionLoadDeleteAccount', '<div class="quadrosOut">%s</div>', ''],
        ['Banir / Desbanir conta', 'manager-ban-account', 'optionLoadManagerBanAccounts', '<div class="quadrosOut">%s</div>',
         'Banir informando só o personagem sempre cai em <code>LDPA_INVALID_LOGIN</code> (bug do original, o mesmo do painel do GM).'],
        ['Liberar / Travar conta para transferir', 'manager-accounts-transfer-cash', 'optionLoadManagerAccountsTransferCash', '<div class="quadrosOut">%s</div>',
         'Liberar e Remover não mostram mensagem de sucesso: a lista é refeita. Login inexistente responde com um <code>alert()</code>.'],
        ['Editar personagem', 'edit-character', 'optionLoadEditCharacter', '<div class="quadrosOut">%s</div>',
         'O formulário preenchido está em <a href="paneladmin-edit-character-kira.html">paneladmin-edit-character-kira.html</a>.'],
        ['Deletar personagem', 'delete-character', 'optionLoadDeleteCharacter', '<div class="quadrosOut">%s</div>', ''],
        ['Banir / Desbanir personagem', 'manager-ban-character', 'optionLoadManagerBanCharacters', '<div class="quadrosOut">%s</div>', ''],
        ['Sincronizador', 'sincronize-vips', 'optionLoadSincronize', '<div class="quadrosOut">%s</div>',
         'Resultado completo em <a href="paneladmin-sincronize-vips.html">paneladmin-sincronize-vips.html</a> (VIPs) e <a href="paneladmin-sincronize-bans.html">paneladmin-sincronize-bans.html</a> (banidos). As linhas abaixo são as partes que o método concatena.'],
        ['Adicionar / Remover / Transformar Vips', 'add-vip', 'optionLoadManageVip', '<div class="quadrosOut">%s</div>',
         'Um método para as 3 telas. O formulário de datas e plano do Transformar está em <a href="paneladmin-transform-vip-kira01.html">paneladmin-transform-vip-kira01.html</a>.'],
        ['Adicionar notícia', 'add-notice', 'optionLoadAddNotice', '<div class="quadrosOut">%s</div>', ''],
        ['Remover notícia', 'remove-notice', 'optionLoadRemoveNotice', '<div class="quadrosOut">%s</div>', ''],
        ['Modificar notícia', 'modify-notice', 'optionLoadModifyNotice', '<div class="quadrosOut" id="logs_">%s</div>',
         'O formulário da notícia escolhida está em <a href="paneladmin-modify-notice-100.html">paneladmin-modify-notice-100.html</a>.'],
        ['Enquetes', 'add-poll', 'optionLoadManagerPoll', '<div class="quadrosOut">%s</div>',
         'Um método para Adicionar, Remover e Modificar. Remover e Modificar respondem acima da lista de enquetes; Modificar não tem mensagem ao incluir ou apagar uma resposta (a página da enquete é refeita).'],
        ['Adicionar / Remover SGolds', 'add-cash', 'optionLoadAddCash', '<div class="quadrosOut">%s</div>', ''],
        ['Enviar mensagem para todos online', 'game-msg-all', 'optionLoadGameMsgAll', '<div class="quadrosOut">%s</div>',
         'As respostas são <code>alert()</code>. Enviar fala com o JoinServer por socket (<code>ldGame</code>).'],
        ['Enviar mensagem para determinados logins', 'game-msg-specific', 'optionLoadGameMsgSpecific', '<div class="quadrosOut">%s</div>', ''],
        ['Ver Chat em tempo real', 'game-chat-server', 'optionLoadGameChatServer', '<div class="quadrosOut">%s</div>', ''],
        ['Adicionar Leilão', 'add-auctions', 'optionLoadAuctionsAdd', '<div class="quadrosOut">%s</div>',
         'Os erros aparecem abaixo do formulário, que volta preenchido com o que foi digitado; o sucesso aparece sozinho.'],
        ['Modificar Leilão', 'edit-auctions', 'optionLoadAuctionsEdit', '<div class="quadrosOut">%s</div>',
         'O formulário do leilão escolhido está em <a href="paneladmin-edit-auctions-4.html">paneladmin-edit-auctions-4.html</a>; erros e sucesso aparecem abaixo dele.'],
        ['Remover Leilão', 'delete-auctions', 'optionLoadAuctionsRemove', '<div class="quadrosOut">%s</div>', ''],
        ['Fechar Leilão', 'close-auctions', 'optionLoadAuctionsClose', '<div class="quadrosOut">%s</div>',
         'Os detalhes do leilão escolhido estão em <a href="paneladmin-close-auctions-2.html">paneladmin-close-auctions-2.html</a>.'],
        ['Denuncias', 'complaints', 'optionLoadManagerComplaints', '<div class="quadrosOut" id="logs_">%s</div>', ''],
        ['Tickets', 'tickets-operation', 'optionLoadManagerTickets', '<div class="quadrosOut" id="logs_">%s</div>',
         'Os mesmos do painel do GM (Tickets Abertos e Encerrados usam o mesmo método).'],
    ], 'painel do admin', paMsgDeposits() . paMsgGroupC());
}

// grupo B: VIPs, notícias, enquetes, SGolds, depósitos
function paGroupBPages(): array
{
    global $OUT;
    puSeedDB();
    foreach (FakeDB::$tables['weblogbuycash'] as $d) {   // comprovantes: modules/uploads/confirmBuys/ -> images/deposits/
        if ($d['anexo'] !== '') kitPlaceholderJpg("$OUT/images/deposits/" . basename($d['anexo']), 640, 480, 'comprovante ' . $d['id'] . ' - 640x480');
    }
    $pages = [
        // VIPs (Transformar: busca -> datas e plano da conta kira01)
        'paneladmin-add-vip.html'              => paPage('paneladmin[ADD_VIP]', paRun('optionLoadManageVip', [1], ['option' => 'ADD_VIP'])),
        'paneladmin-delete-vip.html'           => paPage('paneladmin[DELETE_VIP]', paRun('optionLoadManageVip', [2], ['option' => 'DELETE_VIP'])),
        'paneladmin-transform-vip.html'        => paPage('paneladmin[TRANSFORM_VIP]', paRun('optionLoadManageVip', [3], ['option' => 'TRANSFORM_VIP'])),
        'paneladmin-transform-vip-kira01.html' => paPage('paneladmin[TRANSFORM_VIP]', paRun('optionLoadManageVip', [3], ['option' => 'TRANSFORM_VIP', 'Write' => 'true'], ['account' => 'kira01'])),
        // Notícias (Alterar: escolher -> formulário da notícia 100)
        'paneladmin-add-notice.html'           => paPage('paneladmin[ADD_NOTICE]', paRun('optionLoadAddNotice', [], ['option' => 'ADD_NOTICE'])),
        'paneladmin-remove-notice.html'        => paPage('paneladmin[REMOVE_NOTICE]', paRun('optionLoadRemoveNotice', [], ['option' => 'REMOVE_NOTICE'])),
        'paneladmin-modify-notice.html'        => paPage('paneladmin[MODIFY_NOTICE]', paRun('optionLoadModifyNotice', [], ['option' => 'MODIFY_NOTICE'])),
        'paneladmin-modify-notice-100.html'    => paPage('paneladmin[MODIFY_NOTICE]', paRun('optionLoadModifyNotice', [], ['option' => 'MODIFY_NOTICE', 'Write' => 'true'], ['id' => '100'])),
        // Enquetes
        'paneladmin-add-poll.html'             => paPage('paneladmin[ADD_POLL]', paRun('optionLoadManagerPoll', [0], ['option' => 'ADD_POLL'])),
        'paneladmin-remove-poll.html'          => paPage('paneladmin[REMOVE_POLL]', paRun('optionLoadManagerPoll', [1], ['option' => 'REMOVE_POLL'])),
        'paneladmin-modify-poll.html'          => paPage('paneladmin[MODIFY_POLL]', paRun('optionLoadManagerPoll', [2], ['option' => 'MODIFY_POLL'])),
    ];
    foreach (FakeDB::$tables['webpollquestions'] as $q) {   // Alterar: uma página por enquete
        $pages["paneladmin-modify-poll-{$q['id']}.html"] = paPage('paneladmin[MODIFY_POLL]', paRun('optionLoadManagerPoll', [2], ['option' => 'MODIFY_POLL', 'action' => 'modify', 'idPoll' => (string)$q['id']]));
    }
    return $pages + [
        // SGolds
        'paneladmin-add-cash.html'             => paPage('paneladmin[ADD_CASH]', paRun('optionLoadAddCash', [1], ['option' => 'ADD_CASH'])),
        'paneladmin-remove-cash.html'          => paPage('paneladmin[REMOVE_CASH]', paRun('optionLoadAddCash', [2], ['option' => 'REMOVE_CASH'])),
        // Depósitos (status 0 em andamento, 1 concluídos, 2 falsos)
        'paneladmin-deposits-in-operation.html' => paPage('paneladmin[DEPOSITS_IN_OPERATION]', paRun('optionLoadManagerDeposits', [0], ['option' => 'DEPOSITS_IN_OPERATION'])),
        'paneladmin-deposits-completing.html'  => paPage('paneladmin[DEPOSITS_COMPLETING]', paRun('optionLoadManagerDeposits', [1], ['option' => 'DEPOSITS_COMPLETING'])),
        'paneladmin-deposits-false.html'       => paPage('paneladmin[DEPOSITS_FALSE]', paRun('optionLoadManagerDeposits', [2], ['option' => 'DEPOSITS_FALSE'])),
    ];
}

// Depósitos: a resposta ao salvar ($this->ResultTplDeposits) monta o status numa variável — cada status executado de verdade
function paMsgDeposits(): string
{
    $body = '';
    foreach ([0 => 'LDPA_MDEPOSITS_STAT_IN_PROGRESS', 1 => 'LDPA_MDEPOSITS_STAT_COMPLETED', 2 => 'LDPA_MDEPOSITS_STAT_REJECTED', 3 => 'LDPA_MDEPOSITS_STAT_REJECTED_AND_REMOVE'] as $st => $const) {
        $r = paRun('optionLoadManagerDeposits', [0], ['option' => 'DEPOSITS_IN_OPERATION', 'Write' => 'true', 'Id' => '31'],
                   ['login' => 'kira01', 'cash' => '500', 'status' => (string)$st, 'comentario_adm' => '', 'valor' => '50.00'])['RESULTTPL'];
        preg_match('~^<div class="qdestaques">.*?</div>~s', $r, $m);
        $body .= catItem('LDPA_MDEPOSITS_TEXT_SUCCESS + ' . $const, "<code>status = {$st}</code> (Novo status), depósito 31 da kira01 com 500 SGolds", '<div class="quadrosOut" id="logs_">' . $m[0] . '</div>');
    }
    return catSection('Depósitos — <a href="paneladmin-deposits-in-operation.html">paneladmin-deposits-in-operation.html</a>:',
        'Resposta ao Salvar, acima da lista (a mesma nas 3 telas de depósitos). Gerada executando <code>ldPanelAdmin::optionLoadManagerDepositsModifyStats()</code> com cada status.', $body);
}

// grupo C: backup, ferramentas do jogo, leilões, verificar atualização, Golden Archer
function paGroupCPages(): array
{
    $online = fn(string $method, string $opt) => paPage("paneladmin[{$opt}]", paRun($method, [], ['option' => $opt]));
    return [
        // nome sugerido: date() do build -> data fixa do exemplo (build reprodutível)
        'paneladmin-gerate-backups.html'       => paPage('paneladmin[GERATE_BACKUPS]', ['FILE_NAME_SUGESTION' => '%s ' . date('m-d-Y g-i a', mktime(20, 40, 0, 9, 23, 2026)) . '.bak']
                                                   + paRun('optionLoadGerateBackup', [], ['option' => 'GERATE_BACKUPS'])),
        // ferramentas do jogo: contas online (zeus, elfa, mago); os envios falam com o JoinServer por socket e não rodam no build
        'paneladmin-game-msg-all.html'         => $online('optionLoadGameMsgAll', 'GAME_MSG_ALL'),
        'paneladmin-game-msg-specific.html'    => $online('optionLoadGameMsgSpecific', 'GAME_MSG_SPECIFIC'),
        'paneladmin-game-disconnect.html'      => $online('optionLoadGameDisconnect', 'GAME_DISCONNECT'),
        'paneladmin-game-chat-server.html'     => $online('optionLoadGameChatServer', 'GAME_CHAT_SERVER'),
        // leilões: Modificar -> leilão 4 (em andamento); Fechar -> leilão 2 (encerrado, aguardando prêmio)
        'paneladmin-add-auctions.html'         => paPage('paneladmin[ADD_AUCTIONS]', paRun('optionLoadAuctionsAdd', [], ['option' => 'ADD_AUCTIONS'])),
        'paneladmin-edit-auctions.html'        => paPage('paneladmin[EDIT_AUCTIONS]', paRun('optionLoadAuctionsEdit', [], ['option' => 'EDIT_AUCTIONS'])),
        'paneladmin-edit-auctions-4.html'      => paPage('paneladmin[EDIT_AUCTIONS]', paRun('optionLoadAuctionsEdit', [], ['option' => 'EDIT_AUCTIONS', 'action' => 'load'], ['auction' => '4'])),
        'paneladmin-delete-auctions.html'      => paPage('paneladmin[DELETE_AUCTIONS]', paRun('optionLoadAuctionsRemove', [], ['option' => 'DELETE_AUCTIONS'])),
        'paneladmin-close-auctions.html'       => paPage('paneladmin[CLOSE_AUCTIONS]', paRun('optionLoadAuctionsClose', [], ['option' => 'CLOSE_AUCTIONS'])),
        'paneladmin-close-auctions-2.html'     => paPage('paneladmin[CLOSE_AUCTIONS]', paRun('optionLoadAuctionsClose', [], ['option' => 'CLOSE_AUCTIONS', 'action' => 'close'], ['auction' => '2'])),
        // outros: Verificar atualização (o servidor do autor não existe mais -> "não há atualizações"); Golden Archer
        'paneladmin-verify-update.html'        => paPage('paneladmin[VERIFY_UPDATE]', paRun('optionLoadVerifyUpdate', [], ['option' => 'VERIFY_UPDATE'])),
        'paneladmin-golden-archer.html'        => paPage('paneladmin[GOLDEN_ARCHER]', paRun('optionLoadGoldenArcher', [], ['option' => 'GOLDEN_ARCHER'])),
        'paneladmin-golden-archer-create-serial.html' => paPage('paneladmin[GOLDEN_ARCHER]', paRun('optionLoadGoldenArcher', [], ['option' => 'GOLDEN_ARCHER', 'action' => 'createSerial'])),
    ];
}

// respostas do grupo C que o extrator de uma linha não pega (montadas com variáveis): executadas de verdade
function paMsgGroupC(): string
{
    $sample = fn(string $html) => '<div class="quadrosOut">' . $html . '</div>';
    // Gerar backup: BACKUP DATABASE ok / com erro (o banco falso devolve false para o comando)
    $post = ['database' => 'MuOnline', 'dirname' => 'C:\\Backups\\', 'filename' => '%s 09-23-2026 8-40 pm.bak'];
    $ok  = paRun('optionLoadGerateBackup', [], ['option' => 'GERATE_BACKUPS', 'Write' => 'true'], $post)['RESULTTPL'];
    $err = paRun('optionLoadGerateBackup', [], ['option' => 'GERATE_BACKUPS', 'Write' => 'true'], $post, fn() => FakeDB::$fail = '~^BACKUP~i');
    FakeDB::$fail = null;
    $out = catSection('Gerar Backup — <a href="paneladmin-gerate-backups.html">paneladmin-gerate-backups.html</a>:', 'Executando <code>optionLoadGerateBackup()</code> com o nome sugerido.',
        catItem('LDPA_GENERATE_BACKUP_SUCCESS', 'o <code>BACKUP DATABASE</code> deu certo', $sample($ok)) . catItem('LDPA_GENERATE_BACKUP_ERROR', 'o <code>BACKUP DATABASE</code> falhou', $sample($err['RESULTTPL'])));
    // Desconectar: o texto do alert() vem do ldGame (ldgame.lang.php), que fala com o JoinServer
    $dc = '';
    foreach ([['GAME_SUCCESS_DISCONNECT', 'desconectou (resposta 1 do JoinServer)', GAME_SUCCESS_DISCONNECT . ' zeus'], ['GAME_ERROR_DISCONNECT', 'o JoinServer não desconectou', GAME_ERROR_DISCONNECT],
              ['GAME_INCORRENCT_LOGIN', 'login vazio ou com mais de 10 letras', GAME_INCORRENCT_LOGIN]] as [$c, $when, $txt]) {
        k($c);
        $dc .= catItem($c, $when, paAlert("<script type=\"text/javascript\"> alert(\"{$txt}\"); </script>"));
    }
    $out .= catSection('Desconectar contas onlines — <a href="paneladmin-game-disconnect.html">paneladmin-game-disconnect.html</a>:', 'Resposta ao clicar em Desconectar: um <code>alert()</code> com o retorno de <code>ldGame::disconnectPlayer()</code>.', $dc);
    // Verificar atualização: lista vinda do servidor (stub) com 2 arquivos; depois Baixar / Instalar (1 ok, 1 com erro)
    $GLOBALS['PA_FAKE_UPDATE_LIST'] = "modules/classes/ldpaneluser.class.php{*.*}0f3c9d2e8b7a6150c4d3e2f1a0b9c8d7\ntemplates/sunonline/index.tpl.php{*.*}9a8b7c6d5e4f30211203f4e5d6c7b8a9";
    $avail = paRun('optionLoadVerifyUpdate', [], ['option' => 'VERIFY_UPDATE'])['RESULT'];
    $GLOBALS['PA_FAKE_COPY'] = [true, false];
    $down = paRun('optionLoadVerifyUpdate', [], ['option' => 'VERIFY_UPDATE', 'Download' => 'true'])['RESULT'];
    unset($GLOBALS['PA_FAKE_UPDATE_LIST'], $GLOBALS['PA_FAKE_COPY']);
    $vu = fn(string $html) => '<div class="quadrosOut" id="logs_">' . $html . '</div>';
    $out .= catSection('Verificar atualização — <a href="paneladmin-verify-update.html">paneladmin-verify-update.html</a>:',
        'A lista vinha de <code>daldegamserver.com</code> (fora do ar: a tela do kit mostra "não há atualizações"). Aqui, a lista simulada no build (sem acessar a rede) com 2 arquivos.',
        catItem('LDPA_VERIFY_UPDATE_AVAILABLE + LDPA_VERIFY_UPDATE_DOWNLOAD_AND_INSTALL', 'o md5 de algum arquivo difere do servidor', $vu($avail))
        . catItem('LDPA_VERIFY_UPDATE_DOWNLOADING + _OK / _ERROR', '<code>Download=true</code> (botão Baixar / Instalar): 1º arquivo baixado, 2º com erro (o texto de erro é "Baixando" no <code>.lang.php</code>)', $vu($down)));
    // Golden Archer: criar serial (login vazio / serial criado; o serial é sorteado — semente fixa)
    $ga = fn(array $post) => paRun('optionLoadGoldenArcher', [], ['option' => 'GOLDEN_ARCHER', 'action' => 'createSerialWrite'], $post)['RESPONSE_WRITE'];
    mt_srand(20260923);
    $gaPost = ['item' => '0,8', 'itemLevel' => '9', 'itemOption' => '4', 'itemSkill' => '1', 'itemLuck' => '1', 'harmony' => '0,0', 'socket1' => '255', 'socket2' => '255', 'socket3' => '255', 'socket4' => '255', 'socket5' => '255'];
    $gaWrap = fn(string $html) => '<div class="legend" style="margin-top: 25px;">' . $html . '</div>';
    $out .= catSection('Golden Archer — <a href="paneladmin-golden-archer-create-serial.html">paneladmin-golden-archer-create-serial.html</a>:', 'Resposta do botão Criar serial (texto fixo no PHP).',
        catItem('(texto fixo no PHP)', '<code>empty($_POST[\'memb___id\'])</code>', $gaWrap($ga($gaPost)))
        . catItem('(texto fixo no PHP)', 'serial criado para a conta zeus (Serpent Sword +9 +16 +Skill +Luck); as 3 partes são sorteadas', $gaWrap($ga($gaPost + ['memb___id' => 'zeus']))));
    return $out;
}
