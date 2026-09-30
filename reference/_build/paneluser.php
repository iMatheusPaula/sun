<?php
/*
 * Painel do usuário (ldpaneluser.class.php). Estado: logado como usuário comum (privilégio 0),
 * conta de exemplo "kira01" no plano Vip Gold — com a settings original, Free não acessa 8 das opções
 * (CLEAN_VAULT, DOUBLE_VAULT, RESET_TRANSFER, MOVE_CHARACTER, CHANGE_NICK, CHANGE_CLASS,
 * REDISTRIBUTE_POINTS, CLEAN_INVENTORY), que cairiam em paneluser[ACCESSDANIED].
 */

// a conta do seed (seed.php)
$PU_ACCOUNT = (function () {
    $a = seedAccount('kira01');
    return [
        'login' => $a['login'], 'name' => $a['name'], 'vip' => $a['vip'], 'vipEnd' => seedTs($a['vipEnd']),
        'cash' => $a['cash'], 'cash2' => $a['cash2'], 'points' => $a['points'],
        'connectTM' => $a['connectTM'], 'server' => $a['server'], 'ip' => $a['ip'],
        'tel' => $a['tel'], 'mail' => $a['email'], 'question' => $a['question'],
    ];
})();

// página do painel: moldura com login de usuário comum + tags comuns do painel + tags da tela
function puPage(string $tpl, array $tags = []): array
{
    return ['tpl' => $tpl, 'tags' => $tags + panelUserTags(), 'login' => 0];   // tags da tela vencem as comuns
}

// nome de item do banco de itens do MuSite (modules/classes/ldItemClass/data/item.serialize.txt)
function itemName(int $cat, int $id): string
{
    static $db = null;
    global $ROOT;
    $db ??= unserialize(file_get_contents("$ROOT/modules/classes/ldItemClass/data/item.serialize.txt"));
    return $db[$cat][$id]['Name'];
}

// tags presentes em todas as telas do painel: permissões do menu (setPermissions), evento de perguntas, nome
function panelUserTags(): array
{
    global $PANELUSER_PREMISSIONS, $PU_ACCOUNT;
    $t = [];
    // ldPanelUser::setPermissions: {#X_HABILIT}, {#X_FREE}, {#X_VIP_S}, {#X_VIP_G}, {#X_VIP_3..5} = $PANELUSER_PREMISSIONS[X][0..6]
    foreach ($PANELUSER_PREMISSIONS as $module => $p) {
        foreach (['HABILIT', 'FREE', 'VIP_S', 'VIP_G', 'VIP_3', 'VIP_4', 'VIP_5'] as $i => $suffix) {
            $t["{$module}_{$suffix}"] = $p[$i];
        }
    }
    // questionsEventCheck: sorteio de 5% ($GAME_QUESTION['LUCK']); fora do sorteio a tag fica vazia
    $t['questionResponse'] = '';
    $t['MEMB_NAME'] = $PU_ACCOUNT['name'];
    return $t;
}

// ldPanelUser::loadOptionsDefault (ldpaneluser.class.php:356-406) — "Minha Conta"
function panelUserDefault(): array
{
    global $PANELUSER_MODULE, $CLASS_CHARACTERS, $PU_ACCOUNT;
    $a = $PU_ACCOUNT;
    $t = [];
    $t['BAN_ACCOUNT_DATAILS'] = '';   // conta sem ban e já ativada (os dois avisos estão em mensagens.html)
    $t['ACCOUNT_PLAN_DATAILS'] = $PANELUSER_MODULE['BUY_VIPS']['NAME_FLATS'][$a['vip']]
        . ($a['vip'] > 0 ? ", " . LDPU_OPTIONS_DEFAULT_FLAT_EXPIRE . ": " . date("d/m/Y g:i a", $a['vipEnd']) : null);
    $t['ACCOUNT_CASH'] = (int)$a['cash'];
    $t['ACCOUNT_CASH2'] = (int)$a['cash2'];
    $t['ACCOUNT_POINTS'] = (int)$a['points'];
    $t['LAST_CONNECTION_DETAILS'] = $a['connectTM'] . " " . LDPU_OPTIONS_DEFAULT_LAST_CONNECTION . " " . $a['server'];
    $t['LAST_CONNECTION_DETAILS_IP'] = $a['ip'];
    global $SEED;
    // Name, Class, resets, PkCount, CtlCode (1 = banido), fim do ban (webBanneds type 2)
    $chars = [];
    foreach ($SEED['characters'] as $c) {
        if (!seedSame($c['account'], $a['login'])) continue;
        $ban = current(array_filter($SEED['bans'], fn($b) => $b['type'] == 2 && seedSame($b['name'], $c['name'])));
        $chars[] = [$c['name'], $c['class'], $c['resets'], $c['pkCount'], $c['ctlCode'], $ban ? seedTs($ban['end']) : 0];
    }
    $rows = '';
    $bans = '';
    foreach ($chars as [$name, $class, $resets, $pk, $ctl, $banEnd]) {
        // "NÃ£o": o .class.php (ISO-8859-1) tem esse "Não" gravado em UTF-8 — a página em iso-8859-1 exibia assim
        $rows .= "<tr><td>" . $name . "</td><td>" . className($class) . "</td><td>" . $resets . "</td><td>" . ($pk > 0 ? "Sim" : "NÃ£o") . "</td></tr>";
        if ($ctl == 1) $bans .= sprintf("<div class='qdestaques'>" . LDPU_OPTIONS_DEFAULT_BANNED_CHARACTER . "</div>", $name, date("d/m/Y g:i a", $banEnd));
    }
    $t['CHARACTER_DETAILS'] = $rows;
    $t['BAN_CHARACETERS_DATAILS'] = $bans;
    return $t;
}

// ---------- Grupo A: Gerenciar Minha Conta ----------
// Estado inicial de cada tela (sem Write/action): {#RespostWrite} vazio; as respostas estão em paneluser-mensagens.html

// loadOptionsModifyData (407-459)
function puModifyData(): array
{
    global $PU_ACCOUNT;
    return ['RespostWrite' => '', 'TEL__NUMB' => $PU_ACCOUNT['tel'], 'MAIL_ADDR' => $PU_ACCOUNT['mail'], 'USER_FQUEST' => $PU_ACCOUNT['question']];
}

// loadOptionsTransferCash (1408-1513): sem action, a exceção de código 0 só preenche os saldos
function puTransferCash(): array
{
    global $PU_ACCOUNT;
    return ['RespostWrite' => '', 'MEMB___ID' => $PU_ACCOUNT['login'], 'CASH_AMOUNT' => $PU_ACCOUNT['cash'], 'CASH_AMOUNT2' => $PU_ACCOUNT['cash2']];
}

// loadOptionsCollectorPoints (785-853): item e pontos vêm da settings + banco de itens
function puCollectorPoints(): array
{
    global $PANELUSER_MODULE;
    $r = $PANELUSER_MODULE['COLLECTOR_POINTS']['REQUIRE'];
    return ['POINTS_PER_ITEM' => $r['premiumInPoints'], 'ITEM_NAME' => itemName($r['idCategorie'], $r['idItem']), 'RESPONSE_WRITE' => ''];
}

// loadOptionsLogBuys (1532-1553): Write=1 log de SGolds (webLogBuyCash), Write=2 log de Vips (webLogBuyVips)
function puLogBuys(int $write): array
{
    global $PANELUSER_MODULE;
    $o = '';
    global $SEED, $PU_ACCOUNT;
    $mine = fn(string $table) => array_filter($SEED[$table], fn($r) => seedSame($r['account'], $PU_ACCOUNT['login']));   // WHERE username = … (ordem da tabela)
    if ($write == 1) {
        // id, cash, data, hora, comentario, comentario_adm, status
        $rows = array_map(fn($d) => [$d['id'], $d['cash'], $d['date'], $d['hour'], $d['comment'], $d['adminComment'], $d['status']], $mine('deposits'));
        foreach ($rows as [$id, $cash, $data, $hora, $com, $comAdm, $status]) {
            // o ternário do original testa status == 0 duas vezes: "Rejeitado" nunca aparece, status 2 vira "Erro de Status"
            $o .= "<div class=\"quadros\"><em>" . LDPU_LOG_BUY_TEXT_ID . ":</em> <strong>" . $id . "</strong><br /><em>" . LDPU_LOG_BUY_TEXT_AMOUNT . ":</em> <strong>" . $cash . "</strong><br /><em>" . LDPU_LOG_BUY_TEXT_DATE . ":</em> <strong>" . $data . "</strong><br /><em>" . LDPU_LOG_BUY_TEXT_HOUR . ":</em> <strong>" . $hora . "</strong><br /><em>" . LDPU_LOG_BUY_TEXT_COMMENT . ":</em> <strong>" . nl2br($com) . "</strong><br /><em>" . LDPU_LOG_BUY_TEXT_COMMENT_ADM . ":</em> <strong>" . nl2br($comAdm) . "</strong><br /><em>" . LDPU_LOG_BUY_TEXT_STATUS . ":</em> <strong>" . ($status == 0 ? LDPU_LOG_BUY_TEXT_IN_PROGRESS : ($status == 1 ? LDPU_LOG_BUY_TEXT_COMPLETED : ($status == 0 ? LDPU_LOG_BUY_TEXT_REJECTED : LDPU_LOG_BUY_TEXT_STATUS_ERROR))) . "</strong><br /></div>";
        }
    } else {
        foreach (array_map(fn($v) => [$v['id'], $v['type'], $v['days'], $v['cashAmount'], seedTs($v['date'])], $mine('vipPurchases')) as [$id, $type, $days, $amount, $date]) {
            $o .= "<div class=\"quadros\"><em>" . LDPU_LOG_BUY_TEXT_ID . ":</em> <strong>" . $id . "</strong><br /><em>" . LDPU_LOG_BUY_TEXT_TYPE . ":</em> <strong>" . ($PANELUSER_MODULE['BUY_VIPS']['NAME_FLATS'][$type]) . "</strong><br /><em>" . LDPU_LOG_BUY_TEXT_DAYS . ":</em> <strong>" . $days . "</strong><br /><em>" . LDPU_LOG_BUY_TEXT_VALUE_BY . ":</em> <strong>" . $amount . "</strong><br /><em>" . LDPU_LOG_BUY_TEXT_DATE . ":</em> <strong>" . date("d/m/Y g:i a", $date) . "</strong><br /></div>";
        }
    }
    return ['RespostWrite' => $o];
}

// ---------- paneluser-mensagens.html (página do kit): respostas de cada tela, no contêiner real ----------
function puMsgA(): string
{
    global $PU_ACCOUNT, $PANELUSER_MODULE;
    $etc  = fn(string $m) => "<div id=\"etc\" style=\"margin-bottom: 26px;\">{$m}</div>";                                   // MODIFY_DATA, MODIFY_PERSONALID, TRANSFER_CASH
    $cc   = fn(string $m) => "<div style=\"margin-top: 16px; margin-bottom: 16px; padding-left: 5px;\" id=\"classe_change\">{$m}</div>"; // DOUBLE_VAULT, GAME_DISCONNECT
    $q2   = fn(string $c) => "<div class='qdestaques2'>" . k($c) . "</div>";
    $q    = fn(string $c) => "<div class='qdestaques'>" . k($c) . "</div>";
    $list = function (array $items, callable $wrap) {
        $o = '';
        foreach ($items as [$c, $when, $html]) $o .= catItem($c, $when, $wrap($html));
        return $o;
    };
    $out = '';

    // Alterar Dados
    $body = $list([
        ['LDPU_MODIFY_DATA_FILL_NAME', 'Dados: nome vazio', $q2('LDPU_MODIFY_DATA_FILL_NAME')],
        ['LDPU_MODIFY_DATA_FILL_PHONE', 'Dados: telefone vazio', $q2('LDPU_MODIFY_DATA_FILL_PHONE')],
        ['LDPU_MODIFY_DATA_INVALID_SIZE_NAME', 'Dados: nome com mais de 10 caracteres', $q2('LDPU_MODIFY_DATA_INVALID_SIZE_NAME')],
        ['LDPU_MODIFY_DATA_INVALID_SIZE_PHONE', 'Dados: telefone com mais de 15 caracteres', $q2('LDPU_MODIFY_DATA_INVALID_SIZE_PHONE')],
        ['LDPU_MODIFY_DATA_FILL_PASSWORD', 'Senha: senha atual vazia', $q2('LDPU_MODIFY_DATA_FILL_PASSWORD')],
        ['LDPU_MODIFY_DATA_FILL_NEW_PASSWORD', 'Senha: nova senha vazia', $q2('LDPU_MODIFY_DATA_FILL_NEW_PASSWORD')],
        ['LDPU_MODIFY_DATA_FILL_SECRET_ANSWER', 'Senha: resposta secreta vazia', $q2('LDPU_MODIFY_DATA_FILL_SECRET_ANSWER')],
        ['LDPU_MODIFY_DATA_INVALID_SIZE_PASSWORD', 'Senha: nova senha com mais de 10 caracteres', $q2('LDPU_MODIFY_DATA_INVALID_SIZE_PASSWORD')],
        ['LDPU_MODIFY_DATA_PASSWORDS_MUST_BE_IDENTICAL', 'Senha: as duas novas senhas diferem', $q2('LDPU_MODIFY_DATA_PASSWORDS_MUST_BE_IDENTICAL')],
        ['LDPU_MODIFY_DATA_INVALID_SECRET_ANSWER', 'Senha: resposta secreta errada', $q2('LDPU_MODIFY_DATA_INVALID_SECRET_ANSWER')],
        ['LDPU_MODIFY_DATA_INVALID_PASSWORD', 'Senha: senha atual errada', $q2('LDPU_MODIFY_DATA_INVALID_PASSWORD')],
        ['LDPU_MODIFY_DATA_SUCCESS_ALTER', 'Dados ou senha alterados', $q2('LDPU_MODIFY_DATA_SUCCESS_ALTER')],
        ['LDPU_MODIFY_DATA_ERROR_ALTER', 'Falha no UPDATE (na senha, com um "." a mais no fim)', $q('LDPU_MODIFY_DATA_ERROR_ALTER')],
    ], $etc);
    $out .= catSection('Alterar Dados — <a href="paneluser-modify-data.html">paneluser-modify-data.html</a>:', 'Todas no <code>div#etc</code> acima dos formulários. Note que os erros usam o quadro azul (<code>qdestaques2</code>).', $body);

    // Limpar Baú
    $body = $list([
        ['LDPU_CLEAN_VAULT_NOT_HAVE_VAULT', 'Conta sem baú', "<div class='quadros'><strong>" . k('LDPU_CLEAN_VAULT_NOT_HAVE_VAULT') . "</strong></div>"],
        ['LDPU_YOU_MUST_BE_OFFLINE', 'Conta online no jogo', $q2('LDPU_YOU_MUST_BE_OFFLINE')],
        ['LDPU_CLEAN_VAULT_FILL_PASSWORD', 'Senha vazia', $q2('LDPU_CLEAN_VAULT_FILL_PASSWORD')],
        ['LDPU_CLEAN_VAULT_CHOOSE_TASK', 'Nada marcado como "Sim"', $q2('LDPU_CLEAN_VAULT_CHOOSE_TASK')],
        ['LDPU_CLEAN_VAULT_INVALID_PASSWORD', 'Senha errada', $q2('LDPU_CLEAN_VAULT_INVALID_PASSWORD')],
        ['LDPU_CLEAN_VAULT_1_TEXT_SUCCESS + LDPU_CLEAN_VAULT_2_TEXT_SUCCESS + LDPU_CLEAN_VAULT_ZEN_TEXT_SUCCESS', 'Sucesso: uma linha por item marcado (aqui, os três)', "<div class='qdestaques2'>" . k('LDPU_CLEAN_VAULT_1_TEXT_SUCCESS') . "<br/>" . k('LDPU_CLEAN_VAULT_2_TEXT_SUCCESS') . "<br/>" . k('LDPU_CLEAN_VAULT_ZEN_TEXT_SUCCESS') . "</div>"],
    ], fn($m) => "<div style=\"margin-bottom: 8px;\">{$m}</div>");
    $out .= catSection('Limpar Baú — <a href="paneluser-clean-vault.html">paneluser-clean-vault.html</a>:', 'No <code>div</code> acima do aviso vermelho.', $body);

    // Baú Duplo
    $body = $list([
        ['LDPU_DOUBLE_VAULT_NOT_HAVE_VAULT', 'Conta sem baú', "<div class='quadros'><strong>" . k('LDPU_DOUBLE_VAULT_NOT_HAVE_VAULT') . "</strong></div>"],
        ['LDPU_YOU_MUST_BE_OFFLINE', 'Conta online no jogo', $q2('LDPU_YOU_MUST_BE_OFFLINE')],
        ['LDPU_DOUBLE_VAULT_FILL_PASSWORD', 'Senha vazia', $q2('LDPU_DOUBLE_VAULT_FILL_PASSWORD')],
        ['LDPU_DOUBLE_VAULT_INVALID_SIZE_PASSWORD', 'Senha com mais de 10 caracteres', $q2('LDPU_DOUBLE_VAULT_INVALID_SIZE_PASSWORD')],
        ['LDPU_DOUBLE_VAULT_INVALID_PASSWORD', 'Senha errada', $q2('LDPU_DOUBLE_VAULT_INVALID_PASSWORD')],
        ['LDPU_DOUBLE_VAULT_SUCCESS_ALTER', 'Baús trocados', $q2('LDPU_DOUBLE_VAULT_SUCCESS_ALTER')],
        ['LDPU_DOUBLE_VAULT_ERROR_ALTER', 'Falha no UPDATE', $q('LDPU_DOUBLE_VAULT_ERROR_ALTER')],
    ], $cc);
    $out .= catSection('Baú Duplo — <a href="paneluser-double-vault.html">paneluser-double-vault.html</a>:', 'No <code>div#classe_change</code> acima do aviso azul.', $body);

    // Log de Compras
    $out .= catSection('Log de Compras — <a href="paneluser-log-buys.html">paneluser-log-buys.html</a>:', '', catItem('', 'Os dois logs são telas próprias: <a href="paneluser-log-buys-cash.html">paneluser-log-buys-cash.html</a> e <a href="paneluser-log-buys-vips.html">paneluser-log-buys-vips.html</a> (um <code>div.quadros</code> por compra, dentro de <code>div#logs_</code>). Log vazio: nada é exibido.', ''));

    // Alterar Personal ID
    $pid = fn(string $c) => "<div class='qdestaques'><strong style='color:red;'>" . k($c) . "</strong></div>";
    $body = $list([
        ['LDPU_MODIFY_PID_FILL_ONLY_NUMBERS', 'Personal ID com letras', $pid('LDPU_MODIFY_PID_FILL_ONLY_NUMBERS')],
        ['LDPU_MODIFY_PID_INVALID_SIZE', 'Personal ID sem 7 dígitos', $pid('LDPU_MODIFY_PID_INVALID_SIZE')],
        ['LDPU_MODIFY_PID_INVALID_PASSWORD', 'Senha errada', $pid('LDPU_MODIFY_PID_INVALID_PASSWORD')],
        ['LDPU_MODIFY_PID_SUCCESS', 'Alterado', "<div class='quadros'><strong style='color:blue;'>" . k('LDPU_MODIFY_PID_SUCCESS') . "</strong></div>"],
    ], $etc);
    $out .= catSection('Alterar Personal ID — <a href="paneluser-modify-personalid.html">paneluser-modify-personalid.html</a>:', 'No <code>div#etc</code> acima do formulário; erros com <code>&lt;strong style="color:red;"&gt;</code>, sucesso com <code>color:blue</code>.', $body);

    // Desconectar conta
    $body = $list([
        ['LDPU_YOU_MUST_BE_OFFLINE', 'Aparece quando a conta está <strong>offline</strong>: o teste do original é <code>checkOnlineAccount(...) == 0</code> com o texto de "deve estar offline"', $q2('LDPU_YOU_MUST_BE_OFFLINE')],
        ['LDPU_GAME_DISCONNECT_INVALID_PASSWORD', 'Senha errada', $q2('LDPU_GAME_DISCONNECT_INVALID_PASSWORD')],
        ['GAME_SUCCESS_DISCONNECT', 'Desconectada (texto de <code>ldGame::disconnectPlayer</code>; o <code>\n</code> vira <code>&lt;br /&gt;</code>)', "<div class='qdestaques2'>" . str_replace("\\n", "<br />", k('GAME_SUCCESS_DISCONNECT') . " " . $PU_ACCOUNT['login']) . "</div>"],
        ['GAME_ERROR_DISCONNECT', 'JoinServer não desconectou', "<div class='qdestaques2'>" . str_replace("\\n", "<br />", k('GAME_ERROR_DISCONNECT')) . "</div>"],
    ], $cc);
    $out .= catSection('Desconectar conta — <a href="paneluser-game-disconnect.html">paneluser-game-disconnect.html</a>:', 'No <code>div#classe_change</code> abaixo do formulário.', $body);

    // Transferir SGolds/Golds
    $tq = fn(string $t) => "<div class='qdestaques'>{$t}</div>";
    $body = $list([
        ['LDPU_TRANSFER_CASH_FILL_LOGIN_DESTINY', 'Login de destino vazio', $tq(k('LDPU_TRANSFER_CASH_FILL_LOGIN_DESTINY'))],
        ['LDPU_TRANSFER_CASH_INVALID_AMOUNT', 'Quantidade não numérica ou menor que 1', $tq(k('LDPU_TRANSFER_CASH_INVALID_AMOUNT'))],
        ['LDPU_TRANSFER_CASH_INVALID_TYPE', 'Tipo de moeda inválido', $tq(k('LDPU_TRANSFER_CASH_INVALID_TYPE'))],
        ['LDPU_TRANSFER_CASH_LOGIN_NOT_FOUND', 'Login de destino inexistente (%s = login)', $tq(sprintf(k('LDPU_TRANSFER_CASH_LOGIN_NOT_FOUND'), 'fulano'))],
        ['LDPU_TRANSFER_CASH_CANT_TRANSF_TO_YOU_LOGIN', 'Destino = a própria conta', $tq(k('LDPU_TRANSFER_CASH_CANT_TRANSF_TO_YOU_LOGIN'))],
        ['LDPU_TRANSFER_CASH_NOT_HAVE_PREVILEGY', 'Conta sem permissão de revenda (<code>MEMB_INFO.resale = 0</code>)', $tq(k('LDPU_TRANSFER_CASH_NOT_HAVE_PREVILEGY'))],
        ['LDPU_TRANSFER_CASH_NOT_HAVE_THIS_AMOUNT', 'Saldo insuficiente (%s = moeda)', $tq(sprintf(k('LDPU_TRANSFER_CASH_NOT_HAVE_THIS_AMOUNT'), CASH_NAME))],
        ['LDPU_TRANSFER_CASH_ERROR', 'Falha ao creditar no destino (%s = moeda)', $tq(sprintf(k('LDPU_TRANSFER_CASH_ERROR'), CASH_NAME))],
        ['LDPU_TRANSFER_CASH_TEXT_SUCCESS', 'Transferido (%s = quantidade, moeda, destino)', "<div class='qdestaques2'>" . sprintf(k('LDPU_TRANSFER_CASH_TEXT_SUCCESS'), 100, CASH_NAME, 'zeus') . "</div>"],
    ], $etc);
    $out .= catSection('Transferir ' . CASH_NAME . '/' . CASH_NAME2 . ' — <a href="paneluser-transfer-cash.html">paneluser-transfer-cash.html</a>:', 'No <code>div#etc</code> acima do formulário: erros em vermelho, sucesso em azul.', $body);

    // Coletor de pontos
    $r = $PANELUSER_MODULE['COLLECTOR_POINTS']['REQUIRE'];
    $item = itemName($r['idCategorie'], $r['idItem']);
    $body  = catItem('LDPU_COLLECTOR_POINTS_SUCCESS', 'Itens coletados (%d unidades, %s item, %d pontos)', sprintf("<div class=\"qdestaques2\">" . k('LDPU_COLLECTOR_POINTS_SUCCESS') . "</div>", 3, $item, 3 * $r['premiumInPoints']));
    $body .= catItem('LDPU_COLLECTOR_POINTS_ERROR', 'Nenhuma unidade no baú (%s = item)', sprintf("<div class=\"qdestaques\">" . k('LDPU_COLLECTOR_POINTS_ERROR') . "</div>", $item));
    $body .= catItem('LDPU_YOU_MUST_BE_OFFLINE', 'Conta online: o método faz <code>return</code> antes de preencher a tag, e a página exibe o texto literal abaixo no lugar da mensagem', "{&#35;RESPONSE_WRITE}");
    $out .= catSection('Coletor de pontos — <a href="paneluser-collector-points.html">paneluser-collector-points.html</a>:', 'Abaixo do botão, dentro do quadro de instruções.', $body);

    return $out;
}

// ---------- Grupo B: Gerenciar Personagens ----------
// Cada opção tem 2 telas: sem personagem (só o <select>) e com "Kira" escolhido (&character=Kira), que é onde
// o método monta a caixa da opção. As duas saem da execução do método original (pumock.php).
const PU_GROUP_B = [
    'RESET' => 'loadOptionsReset', 'MASTER_RESET' => 'loadOptionsMasterReset', 'RESET_TRANSFER' => 'loadOptionsResetTransfer',
    'CLEAN_PK' => 'loadOptionsCleanPk', 'DISTRIBUTE_POINTS' => 'loadOptionsDistributePoints', 'MOVE_CHARACTER' => 'loadOptionsMoveCharacter',
    'CHANGE_NICK' => 'loadOptionsChangeNick', 'CHANGE_CLASS' => 'loadOptionsChangeClass', 'REDISTRIBUTE_POINTS' => 'loadOptionsRedistributePoints',
    'CLEAN_INVENTORY' => 'loadOptionsCleanInventory', 'MANAGER_PHOTO' => 'loadOptionsManagerPhoto',
];
const PU_TITLES_B = [
    'RESET' => 'Resetar Personagem', 'MASTER_RESET' => 'Master Reset', 'RESET_TRANSFER' => 'Transferir Resets', 'CLEAN_PK' => 'Limpar PK',
    'DISTRIBUTE_POINTS' => 'Distribuir Pontos', 'MOVE_CHARACTER' => 'Mover Personagem', 'CHANGE_NICK' => 'Mudar Nick', 'CHANGE_CLASS' => 'Mudar Classe',
    'REDISTRIBUTE_POINTS' => 'Redistribuir Pontos', 'CLEAN_INVENTORY' => 'Limpar Inventário', 'MANAGER_PHOTO' => 'Gerenciar foto',
];
function puSlug(string $opt): string { return strtolower(str_replace('_', '-', $opt)); }

function puGroupBPages(): array
{
    $pages = [];
    foreach (PU_GROUP_B as $opt => $method) {
        $slug = puSlug($opt);
        $pages["paneluser-{$slug}.html"] = puPage("paneluser[{$opt}]", puRun($method));
        // Transferir Resets não usa &character=: o passo 2 é o POST do form com os dois personagens
        $pages["paneluser-{$slug}-kira.html"] = $opt === 'RESET_TRANSFER'
            ? puPage("paneluser[{$opt}]", puRun($method, ['Write' => '1'], ['character1' => 'Kira', 'character2' => 'Kirinha']))
            : puPage("paneluser[{$opt}]", puRun($method, ['character' => 'Kira']));
    }
    return $pages;
}

// Extrai de um método da classe original toda mensagem montada em uma linha só
// ($tempRepost .= / $errorReset .= "…".CONSTANTE."…";), com a condição que a dispara.
// Linhas com variáveis (as caixas com dados do personagem) ficam de fora: elas estão nas telas -kira.
function puMessagesFrom(string $method, ?string $source = null): array
{
    global $PU_SOURCE;
    $src = $source ?? $PU_SOURCE;
    $start = strpos($src, "function {$method}(");
    $open = strpos($src, '{', $start);
    for ($i = $open, $depth = 0; $i < strlen($src); $i++) {
        if ($src[$i] === '{') $depth++;
        if ($src[$i] === '}' && --$depth === 0) break;
    }
    $lines = explode("\n", substr($src, $open, $i - $open));
    $out = [];
    foreach ($lines as $n => $line) {
        if (preg_match('~^(.*?)\$(?:this->)?(tempRepost|tempRespost|tempResult|tempResponse|tempTpl|ResponseTpl|writeRespost|errorReset|tplResult)\s*\.?=\s*(.+);\s*$~', $line, $m)) {
            $expr = trim($m[3]);
        } elseif (preg_match('~^(.*?)\becho\s+(.+);\s*$~', $line, $m)) {
            $m = [$m[0], $m[1], 'tempRepost', $m[2]];
            $expr = trim($m[3]);
        } elseif (preg_match('~^(.*?)\bprintf\((.+)\);\s*$~', $line, $m)) {
            $m = [$m[0], $m[1], 'tempRepost', 'sprintf(' . $m[2] . ')'];
            $expr = trim($m[3]);
        } elseif (preg_match('~^(.*?)throw new Exception\((.+?)(?:,\s*\d+)?\);\s*$~', $line, $m)) {
            $m = [$m[0], $m[1], 'tempRepost', $m[2]];
            $expr = trim($m[3]);
        } elseif (preg_match('~^(.*?)(?:return\s+)?\$ldTpl->set\("\w+",\s*(.+)\);\s*$~', $line, $m)) {
            $m = [$m[0], $m[1], 'tempRepost', $m[2]];
            $expr = trim($m[3]);
        } else continue;
        // interpolação {$var} dentro de "..." -> %s (como os placeholders de sprintf)
        $expr = preg_replace('~\{\$[^}]*\}~', '%s', $expr);
        if (str_starts_with($expr, 'sprintf(')) {                  // sprintf("fmt", $args…) -> só o formato
            $depth = 0;
            for ($j = 8; $j < strlen($expr); $j++) {
                if ($expr[$j] === '(') $depth++;
                if ($expr[$j] === ')') $depth--;
                if ($expr[$j] === ',' && $depth === 0) break;
            }
            $expr = substr($expr, 8, $j - 8);
        }
        $consts = [];
        $safe = true;
        $tokens = token_get_all("<?php {$expr};");
        foreach ($tokens as $k => $t) {
            if (!is_array($t)) { if (!in_array($t, ['.', ';'], true)) $safe = false; continue; }
            if (in_array($t[0], [T_OPEN_TAG, T_WHITESPACE, T_CONSTANT_ENCAPSED_STRING], true)) continue;
            if ($t[0] === T_STRING && !in_array(strtolower($t[1]), ['null', 'true', 'false'], true) && defined($t[1])) { $consts[] = $t[1]; continue; }
            $safe = false;
        }
        if (!$safe) continue;
        if (!$consts) {   // texto fixo no PHP: só se for um quadro de mensagem com texto
            $lit = eval("return {$expr};");
            if (!preg_match('~class=[\'"](?:qdestaques2?|quadros)[\'"]~', $lit) || trim(strip_tags($lit)) === '') continue;
        }
        foreach ($consts as $c) k($c);                              // registra constantes inexistentes para a legenda
        $html = eval("return {$expr};");
        $cond = trim($m[1]);
        if ($cond === '') {                                         // condição na linha anterior (if(...) / else)
            $prev = '';
            for ($b = $n - 1; $b >= 0; $b--) {
                $prev = trim($lines[$b]);
                if ($prev !== '' && $prev !== '{') break;
            }
            $cond = preg_match('~^(else|(?:else)?if\s*\()~', $prev) ? $prev : '';
        }
        $cond = rtrim($cond, " {");
        $key = $cond . '|' . $html;
        $out[$key] = [$m[2], implode(' + ', array_unique($consts)) ?: '(texto fixo no PHP)', $cond, $html];
    }
    return array_values($out);
}

function puMsgB(): string
{
    $out = '';
    foreach (PU_GROUP_B as $opt => $method) {
        $slug = puSlug($opt);
        $body = '';
        foreach (puMessagesFrom($method) as [$var, $consts, $cond, $html]) {
            $when = ($cond !== '' ? '<code>' . htmlspecialchars(mb_strimwidth($cond, 0, 170, '…')) . '</code>' : '(sem condição na mesma linha)')
                  . ($var === 'errorReset' ? ' — linha de <code>$errorReset</code>' : '');
            $body .= catItem($consts, $when, "<div class=\"quadrosOut\">{$html}</div>");
        }
        $out .= catSection(PU_TITLES_B[$opt] . " — <a href=\"paneluser-{$slug}.html\">paneluser-{$slug}.html</a> / <a href=\"paneluser-{$slug}-kira.html\">-kira</a>:",
            "Extraídas de <code>{$method}()</code>. Entram em <code>{&#35;RespostWrite}</code>, no <code>div.quadrosOut</code> abaixo do select.", $body);
    }
    return $out;
}

// ---------- Grupo C: Shop + Suporte ----------
// Uma tela cada (estado inicial); Ver Pedidos tem também o pedido aberto (&Ticket=14).
const PU_GROUP_C = [
    'BUY_VIPS' => 'loadOptionsBuyVips', 'CONFIRM_PAYMENT' => 'loadOptionsConfirmPayment', 'OPEN_COMPLAINTS' => 'loadOptionsOpenComplaints',
    'OPEN_TICKET' => 'loadOptionsOpenTicket', 'READ_TICKET' => 'loadOptionsReadTicket', 'SEND_SMS' => 'loadOptionsSendSMS',
];
const PU_TITLES_C = [
    'BUY_VIPS' => 'Comprar Vips', 'CONFIRM_PAYMENT' => 'Confirmar Pagamento', 'OPEN_COMPLAINTS' => 'Abrir Denuncias',
    'OPEN_TICKET' => 'Abrir Pedidos', 'READ_TICKET' => 'Ver Pedidos', 'SEND_SMS' => 'Enviar SMS para os ADMs',
];

function puGroupCPages(): array
{
    global $Config_SMS;
    // privacidade: o destinatário de SMS da settings é o nome do autor original (o número já vem zerado)
    foreach ($Config_SMS as $i => $cfg) $Config_SMS[$i]['Name'] = 'Administrador';
    $pages = [];
    foreach (PU_GROUP_C as $opt => $method) {
        $pages['paneluser-' . puSlug($opt) . '.html'] = puPage("paneluser[{$opt}]", puRun($method));
    }
    $pages['paneluser-read-ticket-view.html'] = puPage('paneluser[READ_TICKET]', puRun('loadOptionsReadTicket', ['Ticket' => '14']));
    return $pages;
}

function puMsgC(): string
{
    $out = '';
    foreach (PU_GROUP_C as $opt => $method) {
        $slug = puSlug($opt);
        $body = '';
        foreach (puMessagesFrom($method) as [$var, $consts, $cond, $html]) {
            $when = $cond !== '' ? '<code>' . htmlspecialchars(mb_strimwidth($cond, 0, 170, '…')) . '</code>' : '(sem condição na mesma linha)';
            $body .= catItem($consts, $when, "<div class=\"quadrosOut\">{$html}</div>");
        }
        if ($body === '') $body = catItem('', 'Nenhuma mensagem de uma linha só neste método.', '');
        $extra = $opt === 'READ_TICKET' ? " / <a href=\"paneluser-read-ticket-view.html\">-view</a>" : '';
        $out .= catSection(PU_TITLES_C[$opt] . " — <a href=\"paneluser-{$slug}.html\">paneluser-{$slug}.html</a>{$extra}:", "Extraídas de <code>{$method}()</code>.", $body);
    }
    return $out;
}

// ---------- Grupo D: Baú virtual ----------
// Respostas de ajax pré-renderizadas por página: [página => [url do Verify => html da resposta]] (A23)
$PU_AJAX_TEMPLATES = [];

// loadOptionsVirtualVault: as duas listas + o painel de detalhes de cada item (action=details, que responde via exit)
function puVirtualVaultPages(): array
{
    global $PU_AJAX_TEMPLATES;
    $t = puRun('loadOptionsVirtualVault');
    preg_match_all("~Verify\\('(\\?page=paneluser&option=VIRTUAL_VAULT&action=details&vault=(game|virtual)&key=(\\d+))'~", $t['liItemsVaultGame'] . $t['liItemsVaultVirtual'], $m, PREG_SET_ORDER);
    foreach ($m as [, $url, $vault, $key]) {
        $PU_AJAX_TEMPLATES['paneluser-virtual-vault.html'][$url] = puRun('loadOptionsVirtualVault', ['action' => 'details', 'vault' => $vault, 'key' => $key])['__exit'] ?? '';
    }
    return ['paneluser-virtual-vault.html' => puPage('paneluser[VIRTUAL_VAULT]', $t)];
}

// Evento de perguntas ("Show do Milhão"): questionsEventCheck() roda depois de toda tela do painel e, em 5% das
// vezes ($GAME_QUESTION['LUCK'] = 0..10000, mínimo 9500), abre a pergunta num fancybox com contador de 60 s.
// Aqui: "Minha Conta" com o sorteio forçado (LUCK = 9500..9500) só para gerar esta página.
function puQuestionsEventPages(): array
{
    global $GAME_QUESTION, $PU_AJAX_TEMPLATES;
    $luck = $GAME_QUESTION['LUCK'];
    $GAME_QUESTION['LUCK'] = [9500, 9500, 9500];
    $event = puRun('questionsEventCheck');
    $GAME_QUESTION['LUCK'] = $luck;
    // respostas do ajax (?page=paneluser&option=QUESTIONS_EVENT&answer=N), por opção escolhida
    $s = ['GAME_QUESTION_ENABLE' => true, 'GAME_QUESTION_TIME' => time() + 60, 'GAME_QUESTION_NUMBER' => 1];
    mt_srand(2026);   // prêmio (shuffle) e serial (rand) determinísticos
    $PU_AJAX_TEMPLATES['paneluser-questions-event.html'] = [
        'qe-select'  => puRun('loadOptionsQuestionsEvent', ['answer' => '0'], [], $s)['__echo'],
        'qe-correct' => puRun('loadOptionsQuestionsEvent', ['answer' => '2'], [], $s)['__echo'],
        'qe-wrong'   => puRun('loadOptionsQuestionsEvent', ['answer' => '1'], [], $s)['__echo'],
    ];
    return ['paneluser-questions-event.html' => puPage('paneluser', ['questionResponse' => $event['questionResponse']] + panelUserDefault())];
}

function puMsgEvent(): string
{
    $wrap = fn(string $m) => "<div id=\"questionsEvent\" style=\"display: block; height: auto; background: #fff;\"><div id=\"optionsQuestion\"><div id=\"reponseSubmit\">{$m}</div></div></div>";
    $body = '';
    foreach (puMessagesFrom('loadOptionsQuestionsEvent') as [$var, $consts, $cond, $html]) {
        $when = $cond !== '' ? '<code>' . htmlspecialchars(mb_strimwidth($cond, 0, 170, '…')) . '</code>' : '(sem condição na mesma linha)';
        $body .= catItem($consts, $when, $wrap($html));
    }
    $body .= catItem('', 'Resposta certa completa, como sai no popup (prêmio sorteado da lista da settings e serial de 3 partes); depois de certa ou errada o PHP esconde o contador (<code>#timerString</code>)', $wrap(preg_replace('~<script.*?</script>~s', '', puRun('loadOptionsQuestionsEvent', ['answer' => '2'], [], ['GAME_QUESTION_ENABLE' => true, 'GAME_QUESTION_TIME' => time() + 60, 'GAME_QUESTION_NUMBER' => 1])['__echo'])));
    return catSection('Evento de perguntas — <a href="paneluser-questions-event.html">paneluser-questions-event.html</a>:', 'Extraídas de <code>loadOptionsQuestionsEvent()</code>. Entram em <code>#reponseSubmit</code>, dentro do popup (réplica sem a altura fixa de 360px).', $body);
}

// Screenshots do painel (módulo screenshots, panelManager): aberto pelo ldModules, não pelo ldPanelUser — por isso o
// setPermissions() e o questionsEventCheck() não rodam, e as tags de permissão do menu ficam literais (bug do original)
function puScreenshotsPanelPages(): array
{
    global $PANELUSER_PREMISSIONS;
    $t = puRunScreenshotsPanel();
    $tags = panelUserTags();
    foreach (array_keys($PANELUSER_PREMISSIONS) as $module) {
        foreach (['HABILIT', 'FREE', 'VIP_S', 'VIP_G', 'VIP_3', 'VIP_4', 'VIP_5'] as $suffix) unset($tags["{$module}_{$suffix}"]);
    }
    unset($tags['questionResponse']);
    return ['paneluser-screenshot.html' => ['tpl' => 'paneluser[SCREENSHOT]', 'tags' => array_diff_key($t, ['__echo' => 1, '__exit' => 1, '__opened' => 1]) + $tags, 'login' => 0]];
}

function puMsgScreenshots(): string
{
    global $PU_MODULE_SOURCE;
    $body = '';
    foreach (puMessagesFrom('panelManager', $PU_MODULE_SOURCE) as [$var, $consts, $cond, $html]) {
        $when = $cond !== '' ? '<code>' . htmlspecialchars(mb_strimwidth($cond, 0, 170, '…')) . '</code>' : '(sem condição na mesma linha)';
        $body .= catItem($consts, $when, "<div id=\"etc\" style=\"margin-bottom: 26px;\">{$html}</div>");
    }
    return catSection('Screenshots — <a href="paneluser-screenshot.html">paneluser-screenshot.html</a>:', 'Extraídas de <code>screenshots.module.php</code> → <code>panelManager()</code> (textos fixos no PHP, sem <code>.lang</code>). Entram no <code>div#etc</code> acima dos formulários.', $body);
}

// Leilões: lista + um leilão aberto por status. A foto (miniatura que abre a grande num fancybox) é placeholder JPG
// gerado no build em images/auctions/ — as fotos reais ficavam em modules/uploads/auctions/, que veio vazio.
const PU_AUCTION_IDS = [4, 3, 2, 1];

function puAuctionsPages(): array
{
    global $OUT;
    @mkdir("$OUT/images/auctions", 0777, true);
    foreach (PU_AUCTION_IDS as $id) {
        foreach (['small' => [100, 75, 3], 'large' => [640, 480, 5]] as $size => [$w, $h, $font]) {
            $im = imagecreatetruecolor($w, $h);
            imagefill($im, 0, 0, imagecolorallocate($im, 0x34, 0x33, 0x36));
            $txt = "leilao {$id}" . ($size === 'large' ? " - {$w}x{$h}" : '');
            imagestring($im, $font, (int)(($w - imagefontwidth($font) * strlen($txt)) / 2), (int)(($h - imagefontheight($font)) / 2), $txt, imagecolorallocate($im, 0xC5, 0xC0, 0xAD));
            imagejpeg($im, "$OUT/images/auctions/{$id}.{$size}.jpg", 85);
            imagedestroy($im);
        }
    }
    $pages = ['paneluser-auctions.html' => puPage('paneluser[AUCTIONS]', puRun('loadOptionsAuctions'))];
    foreach (PU_AUCTION_IDS as $id) {
        $pages["paneluser-auctions-{$id}.html"] = puPage('paneluser[AUCTIONS]', puRun('loadOptionsAuctions', ['action' => 'showDetailsAuction', 'id' => (string)$id]));
    }
    return $pages;
}

function puMsgAuctions(): string
{
    $body = catItem('', 'Respostas ao lance (<code>action=bid</code>): o PHP devolve só um <code>&lt;script&gt;alert(\'…\')</code> — código 1 (erro) volta com <code>history.go(-1)</code>, código 0 (sucesso) recarrega o leilão. Textos dos <code>alert()</code>:', '');
    foreach (puMessagesFrom('loadOptionsAuctions') as [$var, $consts, $cond, $html]) {
        $when = $cond !== '' ? '<code>' . htmlspecialchars(mb_strimwidth($cond, 0, 170, '…')) . '</code>' : '(sem condição na mesma linha)';
        $body .= catItem($consts, $when, "<div class=\"kitSample\" style=\"background: #fff; color: #000; border: 1px solid #999;\">alert: " . htmlspecialchars(html_entity_decode(strip_tags($html))) . "</div>");
    }
    $empty = puRun('loadOptionsAuctions', [], [], [], function () { FakeDB::$tables['webauctions'] = []; });
    $body .= catItem('LDPU_AUCTIONS_NOT_HAVE_AUCTIONS', 'Nenhum leilão ativo (lista)', $empty['RespostWrite']);
    $none = puRun('loadOptionsAuctions', ['action' => 'showDetailsAuction', 'id' => '99']);
    $body .= catItem('LDPU_AUCTIONS_NOT_FOUND', 'Leilão inexistente ou inativo (detalhes)', $none['RespostWrite']);
    return catSection('Leilões — <a href="paneluser-auctions.html">paneluser-auctions.html</a>:', 'Lista e detalhes estão nas páginas (um leilão por status: <a href="paneluser-auctions-4.html">4</a>, <a href="paneluser-auctions-3.html">3</a>, <a href="paneluser-auctions-2.html">2</a>, <a href="paneluser-auctions-1.html">1</a>).', $body);
}

// Golden Archer: registro de serial (ajax action=check -> echo em #serialResponse). Respostas pré-renderizadas por serial
// (A25): o da conta ainda não usado, um já registrado, um de outra conta; vazio e qualquer outro -> as mensagens do PHP.
const PU_GA_SERIALS = ['4E6C-9713-01F7', '7F21-B0C4-9E3A', '55D0-1A2B-C3E9'];

function puGoldenArcherPages(): array
{
    global $PU_AJAX_TEMPLATES;
    $check = fn(string $serial) => puRun('loadOptionsGoldenArcher', ['action' => 'check', 'serial' => $serial])['__echo'];
    $tpl = ['ga-empty' => $check(''), 'ga-invalid' => $check('0000-0000-0000')];
    foreach (PU_GA_SERIALS as $serial) $tpl['ga-' . str_replace('-', '', $serial)] = $check($serial);
    $PU_AJAX_TEMPLATES['paneluser-golden-archer.html'] = $tpl;
    return ['paneluser-golden-archer.html' => puPage('paneluser[GOLDEN_ARCHER]')];
}

function puMsgGoldenArcher(): string
{
    $wrap = fn(string $m) => "<div class=\"goldenArcherBox\" style=\"width: 300px; height: auto; background: #1d1d1f; padding: 8px 0;\"><div id=\"serialResponse\" style=\"position: static;\">{$m}</div></div>";
    $body = '';
    foreach (puMessagesFrom('loadOptionsGoldenArcher') as [$var, $consts, $cond, $html]) {
        $when = $cond !== '' ? '<code>' . htmlspecialchars(mb_strimwidth($cond, 0, 170, '…')) . '</code>' : '(sem condição na mesma linha)';
        $body .= catItem($consts, $when, $wrap("<p class=\"white\">{$html}</p>"));
    }
    $ok = puRun('loadOptionsGoldenArcher', ['action' => 'check', 'serial' => PU_GA_SERIALS[0]])['__echo'];
    $body .= catItem('LDPU_GOLDEN_ARCHER_REGISTER_SUCCESS + LDPU_GOLDEN_ARCHER_ITEM_RECEIVER', 'Serial registrado (código 0): duas linhas amarelas com o nome do item', $wrap($ok));
    return catSection('Golden Archer — <a href="paneluser-golden-archer.html">paneluser-golden-archer.html</a>:', 'Extraídas de <code>loadOptionsGoldenArcher()</code>: erros (código 1) saem em <code>&lt;p class="white"&gt;</code>, dentro de <code>#serialResponse</code>, sobre a arte. Seriais de exemplo na página: <code>' . implode('</code>, <code>', PU_GA_SERIALS) . '</code>.', $body);
}

function puMsgD(): string
{
    $body = '';
    foreach (puMessagesFrom('loadOptionsVirtualVault') as [$var, $consts, $cond, $html]) {
        $when = $cond !== '' ? '<code>' . htmlspecialchars(mb_strimwidth($cond, 0, 170, '…')) . '</code>' : '(sem condição na mesma linha)';
        $body .= catItem($consts, $when, "<div>{$html}</div>");
    }
    $body .= catItem('', 'Enviar item (botão do painel de detalhes, <code>jQuery.post(…action=send…)</code>): com sucesso a resposta é só um <code>&lt;script&gt;</code> jQuery que tira o item de uma lista (<code>fadeOut</code>) e o põe no topo da outra (<code>prepend</code>) — sem mensagem visível', '');
    return catSection('Baú virtual — <a href="paneluser-virtual-vault.html">paneluser-virtual-vault.html</a>:', 'Extraídas de <code>loadOptionsVirtualVault()</code>. Detalhes e respostas de envio entram em <code>div#detailsVault</code>, abaixo dos dois baús.', $body);
}

function puCatalog(): string
{
    return catStyle()
        . "<div class=\"qdestaques2\">Página do kit (não existe no template original): as respostas de cada opção do painel do usuário, com o texto da constante do <code>ldpaneluser.lang.php</code> e a marcação de <code>ldpaneluser.class.php</code>, dentro do contêiner onde aparecem. As telas no estado inicial estão nos links de cada seção.</div>"
        . puMsgA()
        . "<p class=\"kitNote\">Grupo B (Personagens): mensagens extraídas automaticamente do código de cada método, com a condição PHP que as dispara. As linhas de <code>\$errorReset</code> aparecem, antes do clique, no lugar do botão dentro da caixa do personagem e, depois, dentro de um <code>div.qdestaques</code>.</p>"
        . puMsgB()
        . "<p class=\"kitNote\">Grupo C (Shop + Suporte): idem, extraídas do código.</p>"
        . puMsgC()
        . "<p class=\"kitNote\">Grupo D (em andamento).</p>"
        . puMsgD()
        . puMsgEvent()
        . puMsgScreenshots()
        . puMsgAuctions()
        . puMsgGoldenArcher();
}
