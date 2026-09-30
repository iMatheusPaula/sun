<?php
/**
 * sunonline-static — harness de renderização.
 *
 * Renderiza os .tpl.php do template "sunonline" (MuSite 2.5.4) SEM o backend:
 *   1. carrega modules/settings.php e languages/pt-br/*.lang.php (convertidos p/ UTF-8);
 *   2. resolve {#INCLUDE:x} e {#DIV[LOGIN_LOGOUT]} como o ldTpl faz;
 *   3. substitui {#TAGS} pelos valores que as classes ld* gerariam (dados de exemplo,
 *      montados com a MESMA estrutura HTML das classes; textos vêm das constantes .lang.php);
 *   4. avalia o PHP inline dos .tpl (<?php if(...) ?>) com a settings real;
 *   5. aplica as ADAPTAÇÕES ESTÁTICAS (lista explícita abaixo) e grava o .html.
 *
 * Uso:  php _build/render.php            (gera todas as páginas)
 *       php _build/render.php index.html (gera uma)
 */

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
$_SERVER['REQUEST_URI'] = '/';

$ROOT = dirname(__DIR__, 3);            // MuSite-template (o kit fica em sunonline-static/reference)
$TPL  = "$ROOT/templates/sunonline";
$OUT  = dirname(__DIR__);               // sunonline-static

function latin1(string $file): string
{
    return mb_convert_encoding(file_get_contents($file), 'UTF-8', 'ISO-8859-1');
}

// ---------- 1. settings + idiomas (escopo global, saída descartada) ----------
ob_start();
eval('?>' . latin1("$ROOT/modules/settings.php"));
foreach (glob("$ROOT/languages/pt-br/*.lang.php") as $f) {
    eval('?>' . latin1($f));
}
ob_end_clean();

// ---------- 1b. overrides do kit (a settings.php original fica intocada) ----------
// Recursos que a settings deixa DESLIGADOS mas que fazem parte do template. Ligados aqui para que o
// kit mostre todos os componentes; sem isso o bloco "Últimas do forum" não é renderizado e
// {#GAMESERVERS_ON} sai vazio (a caixa SERVIDORES só teria "Total online"). Documentado no README.
$FORUM_CONFIGS['ENABLE'] = true;
$FORUM_CONFIGS['LINK_FORUM']   = '#';           // era a URL do fórum do servidor original
$FORUM_CONFIGS['LINKS_TOPICS'] = '#topic-%d';   // idem; mantém o %d que ldHome::loadLastForum usa no sprintf
$Config_Find_Rooms_ConnectServer['Enable'] = true;

// dados de exemplo: os mesmos do app (src/data/seed.ts -> seed.json)
require __DIR__ . '/seed.php';

// Stubs do que o index.tpl.php instancia inline (módulo de screenshots, precisa de DB no original)
class ldModules { public function __construct($m) {} }
class screenshots
{
    // screenshots.module.php:38-56 — TOP_HOME pela nota; o autor é o GameIDC de quem enviou
    public static function getScreenshots(): void
    {
        global $SCREENSHOTS;
        $rows = array_slice(seedScreenshotsRanked(), 0, (int)$SCREENSHOTS['TOP_HOME']);
        if (!$rows) { echo "<td>Nenhuma Screenshot foi enviada.</td>"; return; }
        foreach ($rows as $s) {
            echo "<td><a href=\"?page=loadModule&module=screenshots&action=viewImage&id={$s['id']}\">" . seedScreenshotAuthor($s) . "<br />"
               . "<img src=\"modules/uploads/screenshots/{$s['image']}.small.jpg\" style=\"width: {$s['sw']}px; height: {$s['sy']}px;\" /><br />"
               . "Voto" . ($s['rate'] > 0 ? "s" : "") . ": {$s['rate']}</a></td>";
        }
    }
}

// ---------- utilitários ----------
function tpl(string $name): string
{
    global $TPL;
    return latin1("$TPL/$name.tpl.php");
}

function yesno(bool $ok): string   // ldVips: permissão -> Sim verde / Não vermelho
{
    return $ok ? '<span style="color:#40B60E">' . VIPS_YES . '</span>' : '<span style="color:#FF0000">' . VIPS_NOT . '</span>';
}
function cleanflag(bool $cleans): string   // ldVips: "Apaga X ao resetar" -> Sim vermelho / Não verde
{
    return $cleans ? '<span style="color:#FF0000">' . VIPS_YES . '</span>' : '<span style="color:#40B60E">' . VIPS_NOT . '</span>';
}
function className(int $code): string      // ldPanelUser::classNameDefine
{
    global $CLASS_CHARACTERS;
    foreach ($CLASS_CHARACTERS['CLASSCODES'] as $c) {
        if ((int)$c[0] === $code) return $c[1];
    }
    return (string)$code;
}
function svg(string $body, int $w, int $h): string
{
    return "data:image/svg+xml;utf8," . rawurlencode("<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 $w $h' width='$w' height='$h'>$body</svg>");
}
$SVG_GUILD = [
    svg("<rect width='8' height='8' fill='#e8e2cf'/><path fill='#b4322e' d='M1 1h6v1H1zM1 6h6v1H1z'/><path fill='#333' d='M3 2h2v4H3z'/>", 8, 8),
    svg("<rect width='8' height='8' fill='#222'/><path fill='#fcda96' d='M2 2h4v4H2z'/><path fill='#222' d='M3 3h2v2H3z'/>", 8, 8),
    svg("<rect width='8' height='8' fill='#4682b4'/><path fill='#fff' d='M0 0h4v4H0zM4 4h4v4H4z'/>", 8, 8),
    svg("<rect width='8' height='8' fill='#76f6c5'/><path fill='#291f12' d='M1 1h2v2H1zM5 1h2v2H5zM1 5h2v2H1zM5 5h2v2H5z'/>", 8, 8),
];
$SVG_SHOT    = svg("<rect width='90' height='68' fill='#343336'/><text x='45' y='38' font-family='Arial' font-size='9' fill='#c5c0ad' text-anchor='middle'>screenshot</text>", 90, 68);
$SVG_SHOT_BIG = svg("<rect width='640' height='480' fill='#343336'/><text x='320' y='245' font-family='Arial' font-size='22' fill='#c5c0ad' text-anchor='middle'>screenshot 640x480</text>", 640, 480);
$SVG_PHOTO   = svg("<rect width='100' height='100' fill='#343336'/><circle cx='50' cy='38' r='18' fill='#c5c0ad'/><path d='M18 92c4-22 18-32 32-32s28 10 32 32z' fill='#c5c0ad'/>", 100, 100);
$SVG_CAPTCHA = svg("<rect width='135' height='25' fill='#000'/><text x='30' y='18' font-family='monospace' font-size='14' fill='#fff'>7f3a9c1e</text>", 135, 25);

// ---------- 3. valores dos {#TAGS} ----------

// enquete ativa do seed: $POLL_ACTIVE (webPollQuestions) e idAnswer => [resposta, votos]
[$POLL_ACTIVE, $POLL_SAMPLE] = seedPoll();

// ldAjax::loadPoll (ldajax.class.php:198-273): as 3 respostas possíveis no estado do kit (deslogado),
// pré-renderizadas em <template> (A17); js/main.js injeta em #pollResult como o Verify() fazia.
function pollResponses(): string
{
    global $POLL, $POLL_SAMPLE;
    // action=vote: com $POLL['LOGIN'] = true e sem sessão, toda tentativa devolve POLL_LOGIN_TO_VOTE;
    // com HANG_WITH type 0 não há trava; answerNumber "0" (nenhuma opção marcada) -> empty() -> opção inválida
    $login   = $POLL['LOGIN'] == true;
    $vote    = "<li><strong>" . ($login ? POLL_LOGIN_TO_VOTE : POLL_VOTE_SUCESS) . "</strong></li>";
    $invalid = "<li><strong>" . ($login ? POLL_LOGIN_TO_VOTE : POLL_INVALID_OPTION) . "</strong></li>";
    // action=result (utf8_encode omitido: as respostas de exemplo já são UTF-8)
    $total = array_sum(array_column($POLL_SAMPLE, 1));
    $result = '';
    foreach ($POLL_SAMPLE as $id => [$answer, $votes]) {
        $result .= "<li><strong>" . $answer . "</strong> ({$votes})<br />
                                <div class=\"gauge\" id=\"gauge\" style=\"width:150px; height:10px;\">
                                <div class=\"top\"></div>
                                 <div class=\"content\" style=\"height:8px;\"><img id=\"{$id}\" class=\"filling\" src=\"templates/sunonline/images/gauge.png\" style=\"width:" . (int)(($votes / $total) * 100) . "%; height:13px; border:none;\" alt=\"\" title=\"\"/></div>
                                <div class=\"bottom\"></div>
                               </div></li>";
    }
    $result .= "<li>" . POLL_TOTAL_VOTES . " {$total}</li>";
    return "\n                            <template id=\"pollVote\">{$vote}</template>"
         . "\n                            <template id=\"pollInvalid\">{$invalid}</template>"
         . "\n                            <template id=\"pollResults\">{$result}</template>";
}

// ldGeneral::__construct / loadServersOn / loadPoll  (comuns a todas as páginas)
function commonTags(): array
{
    global $PANELUSER_MODULE;
    $t = [
        'TITLE_SITE' => TITLE_SITE, 'SERVER_NAME' => SERVER_NAME, 'SERVER_SLOGAN' => SERVER_SLOGAN,
        'SERVER_VERSION' => SERVER_VERSION, 'SERVER_DROP' => SERVER_DROP, 'SERVER_XP' => SERVER_XP,
        'SERVER_BUGBLESS' => SERVER_BUGBLESS, 'CASH_NAME' => CASH_NAME, 'CASH_NAME2' => CASH_NAME2,
        'POINTS_NAME' => POINTS_NAME, 'SHOPPING_LINK' => SHOPPING_LINK,
        'ResultTime' => '0.0123',
    ];
    // contadores (ldGeneral::__construct): count(1) sobre o seed
    $n = seedCounters();
    $t += [
        'TOTAL_ACCOUNTS' => (string)$n['accounts'], 'TOTAL_CHARATERS' => (string)$n['characters'], 'RECORD_ONLINE' => (string)$n['record'],
        'TOTAL_ACCOUNTS_VIP_SILVER' => (string)$n['vips'][1], 'TOTAL_ACCOUNTS_VIP_GOLD' => (string)$n['vips'][2], 'TOTAL_ACCOUNTS_VIP_3' => (string)$n['vips'][3],
        'TOTAL_ACCOUNTS_VIP_4' => (string)$n['vips'][4], 'TOTAL_ACCOUNTS_VIP_5' => (string)$n['vips'][5],
        'TOTAL_CHARACTERS_BANNEDS' => (string)$n['bannedCharacters'], 'TOTAL_ACCOUNTS_BANNEDS' => (string)$n['bannedAccounts'],
        'TOTAL_ACCOUNTS_ONLINES' => (string)$n['online'],
    ];
    foreach ($PANELUSER_MODULE['BUY_VIPS']['NAME_FLATS'] as $i => $n) $t["VIP_NAME_$i"] = $n;

    // ldGeneral::loadServersOn (ldgeneral.class.php:91-105). Nomes das salas: exemplo (os reais são a marca do servidor original)
    global $Config_Find_Rooms_ConnectServer;
    $gs = '';
    if ($Config_Find_Rooms_ConnectServer['Enable'] == true)
    foreach ([['Server 1', 63], ['Server 2', 21]] as [$name, $users]) {
        $gs .= "<li>{$name}: <a href=\"?page=onlines&amp;room={$name}\">{$users}</a>% <br />
                            <div class=\"gauge\" id=\"gauge\" style=\"width:150px; height:10px;\">
                            <div class=\"top\"></div>
                             <div class=\"content\" style=\"height:8px;\"><img id=\"{$name}\" class=\"filling\" src=\"templates/sunonline/images/gauge.png\" style=\"width:{$users}%; height:13px; border:none;\" alt=\"\" title=\"\"/></div>
                            <div class=\"bottom\"></div>
                           </div></li>";
    }
    $t['GAMESERVERS_ON'] = $gs;

    // ldGeneral::loadPoll (ldgeneral.class.php:141-153)
    global $POLL_SAMPLE, $POLL_ACTIVE;
    $pollId = $POLL_ACTIVE['id'];
    $p = "<li><strong>{$POLL_ACTIVE['question']}</strong></li>";
    $p .= "<input id=\"answerNumber\" name=\"answerNumber\" value=\"0\" type=\"hidden\" />";
    foreach ($POLL_SAMPLE as $id => [$answer, $votes]) {
        $p .= "<li><input id=\"answer\" name=\"answer\" value=\"{$id}\" type=\"radio\" onclick=\"javascript: document.getElementById('answerNumber').value = this.value\" /> {$answer}</li>";
    }
    $p .= "<li><input type=\"button\" class=\"button\" value=\"" . POLL_BUTTON_VOTE . "\" onclick=\"javascript: Verify('?page=ajax&amp;require=poll&action=vote&amp;pollId={$pollId}&amp;answerNumber='+document.getElementById('answerNumber').value,'pollResult','get');\" /> <input type=\"button\" class=\"button\" value=\"" . POLL_BUTTON_RESULTS . "\" onclick=\"javascript: Verify('?page=ajax&amp;require=poll&action=result&amp;pollId={$pollId}','pollResult','get');\" /></li>";
    $t['POLL_OPTIONS'] = $p;
    return $t;
}

// ldHome (index)
function homeTags(): array
{
    global $CASTLE_SIEGE;
    $t = [];
    global $NOTICES, $RANKING_HOME_CONFIGS;
    // loadLastNotices (ldhome.class.php:37): TOP $NOTICES['LAST'] ORDER BY date DESC
    $n = '';
    foreach (array_slice(seedNotices(), 0, (int)$NOTICES['LAST']) as $notice) {
        $n .= "<li><div style=\"border-bottom-color:#C2CAD3; border-bottom-width:thin; border-bottom-style:dotted;\"><a href=\"?page=readNotice&amp;id={$notice['id']}\">{$notice['subject']}</a> - [" . date("d/m/Y g:i a", seedTs($notice['date'])) . "]</div></li>";
    }
    $t['LAST_NOTICES_HOME'] = $n;

    // loadTop* (ldhome.class.php:107-172): TOP 4 da tabela do ranking pela coluna do tipo = os 4 primeiros do ranking
    $amount = (int)$RANKING_HOME_CONFIGS['TOPAMOUNT'];
    $top = fn(int $type, int $period, string $col) => implode('', array_map(fn($c) => "<li><a href=\"?page=rankings&type=6&name={$c['name']}\">{$c['name']}</a> - {$c[$col]}</li>", seedRanking($type, $period, $amount)));
    $t['RANK_TOP_RESETS_HOME']        = $top(1, 0, 'resets');
    $t['RANK_TOP_RESETS_WEEK_HOME']   = $top(1, 1, 'resetsWeek');
    $t['RANK_TOP_RESETS_MONTH_HOME']  = $top(1, 2, 'resetsMonth');
    $t['RANK_TOP_MASTER_RESETS_HOME'] = $top(5, 0, 'masterResets');
    $t['RANK_TOP_PK_HOME']            = $top(4, 0, 'pkCountWeb');
    $t['RANK_TOP_LEVEL_HOME']         = $top(2, 0, 'level');

    // loadLastForum (ldhome.class.php:42-106): só se $FORUM_CONFIGS['ENABLE']; LAST_TOPICS tópicos, IPB (TYPE=1)
    global $FORUM_CONFIGS;
    if ($FORUM_CONFIGS['ENABLE'] == TRUE) {
        $f = '';
        $topics = [[912, 'Guia de reset para iniciantes'], [910, 'Recrutamento guild Legends'], [905, 'Bug no Chaos Castle? [resolvido]'],
                   [899, 'Sugestão: evento de drop no sábado'], [897, 'Vendo set Dark Phoenix +13']];
        foreach (array_slice($topics, 0, (int)$FORUM_CONFIGS['LAST_TOPICS']) as [$topicId, $title]) {
            $f .= sprintf("<li><div style=\"border-bottom-color:#C2CAD3; border-bottom-width:thin; border-bottom-style:dotted;\"><a href=\"{$FORUM_CONFIGS['LINKS_TOPICS']}\" target=\"_blank\">%s</a></div></li>", $topicId, $title);
        }
        $t['LAST_FORUM_HOME'] = $f;
    }

    // loadTopGuilds (ldhome.class.php:173-184): G_Mark -> decode=guild<logo>
    $g = '';
    foreach (seedGuildRanking($amount) as $guild) {
        $g .= sprintf("<td align=\"center\"><a href=\"?page=rankings&type=7&name=%s\"><strong>%s</strong><br /><img src=\"modules/classes/logoGuildDecode.php?decode=%s\" width=\"100\" height=\"100\" /><br />Pontos: %d</a></td>", $guild['name'], $guild['name'], "guild{$guild['mark']}", $guild['score']);
    }
    $t['RANK_TOP_GUILDS_HOME'] = $g;

    // loadCastleSiege (ldhome.class.php:23-30): MuCastle_DATA
    global $SEED;
    $t['CASTLE_SIEGE_OCCUPY_GUILD'] = $SEED['castle']['occupied'] ? $SEED['castle']['owner'] : CASTLE_SIEGE_OCCUPY_GUILD;
    $t['CASTLE_SIEGE_END_DATE']     = $CASTLE_SIEGE['CONFRONTATION'];
    return $t;
}

// ldAjax: chat em tempo real (ldajax.class.php:42-69) e equipe (:188-197)
function chatItems(): string
{
    $types = [[CHAT_MSG_CHAT, 'chat'], [CHAT_MSG_PARTY, 'party'], [CHAT_MSG_GUILD, 'guild'], [CHAT_MSG_GLOBAL, 'msgglobal'], [CHAT_MSG_ANUNGUILD, 'anunguild'],
              [CHAT_MSG_WHISPER, 'whisper'], [CHAT_MSG_ALlIANCE, 'aliance'], [CHAT_MSG_GM, 'gamemaster'], [CHAT_MSG_POST, 'post']];
    $o = '';
    foreach (seedChat(50) as $m) {
        [$type, $cls] = $types[$m['type']];
        [$hour, $name, $msg] = [$m['hour'], $m['name'], $m['message']];
        $o .= "<li>
                                <div class=\"chatList {$cls}\">
                                  <strong>[{$type}][{$hour}] {$name}:</strong> {$msg}
                                </div>
                            </li>";
    }
    return $o;
}
function staffBox(): string
{
    // ldAjax::showMemberStaff (ldajax.class.php:188-197)
    $o = "<div class='quadros'>";
    foreach (seedStaff() as [$name, $on]) {
        $o .= "&nbsp; - <strong>{$name}</strong> - " . ($on == 0 ? "<strong class='staffOffline'>" . STAFF_OFFLINE . "</strong>" : "<strong class='staffOnline'>" . STAFF_ONLINE . "</strong>") . "<br />";
    }
    return $o . "</div>";
}

// ldRankings::gerateRankingResets (type=1, period 0/1/2), gerateRankingLevel (2), gerateRankingPks (4),
// gerateRankingMasterResets (5) — top=10. As 4 funções só mudam a coluna destacada (#CDC89E), a coluna de
// valor, o ORDER BY e espaços em branco; cada detalhe abaixo segue a função correspondente.
function rankingList(int $type = 1, int $period = 0): string
{
    global $PANELUSER_MODULE;
    // coluna de valor: resets (ou resets da semana/mês) e master resets; ORDER BY em seedRanking()
    $valueCol = match ($type) { 5 => 'masterResets', 1 => ['resets', 'resetsWeek', 'resetsMonth'][$period], default => 'resets' };
    $rows = seedRanking($type, $period, 10);
    $hl = fn(int $col) => ($col === match ($type) { 2 => 3, 4 => 4, default => 5 }) ? '#CDC89E' : '#E4E1CB';
    $o = "<div class='quadros'>
                    <table border='0' width='100%'>
                     <tr>" . ($type == 5 ? "   " : "") . "
                      <td bgcolor='" . $hl(1) . "' align='center'><strong>" . RANKING_PLACE . "</strong></td>
                      <td bgcolor='" . $hl(2) . "' align='center'><strong>" . RANKING_CHARACTER_SHORT . "</strong></td>
                      <td bgcolor='" . $hl(3) . "' align='center'><strong>" . RANKING_LEVEL . "</strong></td>
                      <td bgcolor='" . $hl(4) . "' align='center'><strong>" . RANKING_KILLS . "</strong></td>
                      <td bgcolor='" . $hl(5) . "' align='center'><strong>" . ($type == 5 ? RANKING_MASTERRESETS : RANKING_RESETS) . "</strong></td>
                      <td bgcolor='" . $hl(6) . "' align='center'><strong>" . RANKING_CLASS . "</strong></td>
                      <td bgcolor='" . $hl(7) . "' align='center'><strong>" . RANKING_STATUS_ACCOUNT . "</strong></td>
                     </tr>";
    $trail = match ($type) { 1 => "", 4 => "  ", default => " " };
    $pos = 1;
    foreach ($rows as $d) {
        [$char, $vip, $class, $lvl, $pk] = [$d['name'], seedAccountVip($d['account']), $d['class'], $d['level'], $d['pkCountWeb']];
        $pkText = $pk < 0 ? RANKING_HERO : $pk;
        $o .= "<tr>
                      <td bgcolor='#F1F0E4' align='center'><strong>" . $pos++ . "</strong></td>{$trail}
                      <td align='center'><a href=\"?page=rankings&type=6&name={$char}\">{$char}</a></td>
                      <td align='center'>{$lvl}</td>
                      <td align='center'>{$pkText}</td>
                      <td align='center'>{$d[$valueCol]}</td>
                      <td align='center'>" . className($class) . "</td>
                      <td align='center'>" . $PANELUSER_MODULE['BUY_VIPS']['NAME_FLATS'][$vip] . "</td>
                     </tr>";
    }
    return $o . "</table></div>";
}

// ldRankings::gerateRankingGuilds (type=3, top=10) — ORDER BY G_Score desc; assistente = G_Status 64, BM = 32
function rankingGuilds(): string
{
    // G_Name, G_Master, assistente, battle master, G_Score
    $guilds = array_map(fn($g) => [$g['name'], $g['master'], seedMemberWithStatus($g['name'], 64), seedMemberWithStatus($g['name'], 32), $g['score']], seedGuildRanking(10));
    $o = "<div class='quadros'>
					<table border='0' width='100%'>
					 <tr>
					  <td bgcolor='#E4E1CB' align='center'><strong>" . RANKING_PLACE_X . "</strong></td>
					  <td bgcolor='#E4E1CB' align='center'><strong>" . RANKING_GUILD_NAME . "</strong></td>
					  <td bgcolor='#E4E1CB' align='center'><strong>" . RANKING_GUILD_GM . "</strong></td>
					  <td bgcolor='#E4E1CB' align='center'><strong>" . RANKING_GUILD_ASSISTANT . "</strong></td>
					  <td bgcolor='#E4E1CB' align='center'><strong>" . RANKING_GUILD_BM . "</strong></td>
					  <td bgcolor='#E4E1CB' align='center'><strong>" . RANKING_GUILD_SCORE_SHORT . "</strong></td>
					 </tr>";
    $pos = 1;
    foreach ($guilds as [$name, $master, $assistant, $bm, $score]) {
        $o .= "<tr>
					  <td bgcolor='#F1F0E4' align='center'><strong>" . $pos++ . "</strong></td>             
					  <td bgcolor='#F1F0E4' align='center'><a href=\"?page=rankings&type=7&name={$name}\">{$name}</a></td>
					  <td bgcolor='#F1F0E4' align='center'>{$master}</td>
					  <td bgcolor='#F1F0E4' align='center'>{$assistant}</td>
					  <td bgcolor='#F1F0E4' align='center'>{$bm}</td>
					  <td bgcolor='#F1F0E4' align='center'>{$score}</td>
					 </tr>";
    }
    return $o . "</table></div>";
}

// ldRankings::geratePerfilCharacter (type=6&name=DarkLord), $RANKING_CONFIGS['STATS'] da settings
function rankingProfile(string $name = 'DarkLord'): string
{
    global $PANELUSER_MODULE, $RANKING_CONFIGS;
    $c = seedCharacter($name);
    $stats = $RANKING_CONFIGS['STATS'] == TRUE;
    $s = fn(string $label, $val) => $stats ? "<strong>{$label}:</strong> {$val} <br />" : '';
    $photo = empty($c['image']) ? 'modules/uploads/photos/nophoto.jpg' : "modules/uploads/photos/{$c['image']}";
    $mapName = '';
    foreach ($PANELUSER_MODULE['MOVE_CHARACTER']['MAPS'] as $map) if ($map[0] == $c['map']) $mapName = $map[1];
    $account = seedAccount($c['account']);
    $guild = seedGuildOf($name);
    return "<h1>" . RANKING_RESULT_OF . " {$name}</h1>
                                        <div class='quadros'>
                                        <table width='100%' border='0'>
                                        <tr>
                                            <td width='25%' align='center' valign='middle' style='padding-top: 2px;'>
                                            <p><img src='{$photo}' alt='' width='100' height='100' style='border: 2px solid #222222;' /></p>
                                            </td>
                                            <td width='37%'>
                                                <strong>" . RANKING_CLASS . ":</strong> " . className($c['class']) . " <br />
                                                <strong>" . RANKING_RESETS . ":</strong> {$c['resets']} <br />
                                                <strong>" . RANKING_LEVEL . ":</strong> {$c['level']} <br />
                                                <strong>" . RANKING_POINTS . ":</strong> {$c['points']} <br />
                                                " . $s(RANKING_MAP, $mapName) . "
                                                " . $s(RANKING_MAP_POS_X, $c['x']) . "
                                                " . $s(RANKING_MAP_POS_Y, $c['y']) . "
                                                <strong>" . RANKING_ZEN . ":</strong> {$c['money']}
                                            </td>
                                            <td width='38%' valign='top'>
                                                " . $s(RANKING_STRENGHT, $c['strength']) . "
                                                " . $s(RANKING_DEXTERITY, $c['dexterity']) . "
                                                " . $s(RANKING_VITALITY, $c['vitality']) . "
                                                " . $s(RANKING_ENERGY, $c['energy']) . "
                                                " . $s(RANKING_LEADERSHIP, (int)$c['leadership']) . "
                                                <strong>" . RANKING_STATUS . ":</strong> " . ($account['online'] ? RANKING_STATUS_ONLINE : RANKING_STATUS_OFFLINE) . " <br />
                                                <strong>" . RANKING_GUILD . ":</strong> " . ($guild === null ? RANKING_GUILD_EMPTY : "<a href=\"?page=rankings&type=7&name={$guild}\">{$guild}</a>") . " <br />
                                                <strong>" . RANKING_TYPE_ACCOUNT . ":</strong> " . $PANELUSER_MODULE['BUY_VIPS']['NAME_FLATS'][(int)$account['vip']] . "
                                            </td>
                                        </tr>
                                        </table>
                                        </div>";
}

// ldRankings::geratePerfilGuild (type=7&name=Legends)
function rankingGuildProfile(string $name = 'Legends'): string
{
    $guild = seedFind('guilds', 'name', $name);
    // Name, G_Level, G_Status + Class e resets do personagem
    $members = array_map(fn($m) => [$m['name'], $m['level'], $m['status'], seedCharacter($m['name'])['class'] ?? 0, seedCharacter($m['name'])['resets'] ?? ''], seedGuildMembers($name));
    $list = '';
    foreach ($members as [$n, $lvl, $status, $class, $resets]) {
        switch ($status) {
            case 32:  $pos = "<strong>" . RANKING_GUILD_BM . "</strong>"; break;
            case 64:  $pos = "<strong>" . RANKING_GUILD_ASSISTANT . "</strong>"; break;
            case 128: $pos = "<strong>" . RANKING_GUILD_GM . "</strong>"; break;
            default:  $pos = $lvl == 1 ? "<strong>" . RANKING_GUILD_GM . "</strong>" : RANKING_GUILD_MEMBBER; break;
        }
        $list .= "<tr><td><a href=\"?page=rankings&type=6&name={$n}\">{$n}</a></td><td>" . className($class) . "</td><td>{$resets}</td><td>" . $pos . "</td></tr>";
    }
    return "<h1>" . RANKING_RESULT_OF . " {$name}</h1>
                                        <div class='quadros'>
                                        <table width='100%' border='0'>
                                        <tr>
                                            <td width='25%' align='center' valign='middle' style='padding-top: 2px;'>
                                            <p><img src='modules/classes/logoGuildDecode.php?decode=guild{$guild['mark']}' alt='' width='100' height='100' style='border: 2px solid #222222;' /></p>
                                            </td>
                                            <td width='75%'> 
                                                <strong>" . RANKING_GUILD_SCORE . "</strong>: {$guild['score']} <br /> 
                                                <strong>" . RANKING_GUILD_GM . "</strong>: <a href=\"?page=rankings&type=6&name={$guild['master']}\">{$guild['master']}</a> <br />    
                                                <strong>" . RANKING_TOTAL_MEMBERS . "</strong>: " . count($members) . " <br />
                                                <table width='100%' border='0' style='text-align: center;'>
                                                    <tr>
                                                        <th width='26%' align='center' style='background: #E4E1CB; padding: 1px 5px;'><strong>" . RANKING_CHARACTER . "</strong></th>
                                                        <th width='25%' align='center' style='background: #E4E1CB; padding: 1px 5px;'><strong>" . RANKING_CLASS . "</strong></th>     
                                                        <th width='23%' align='center' style='background: #E4E1CB; padding: 1px 5px;'><strong>" . RANKING_RESETS . "</strong></th>
                                                        <th width='26%' align='center' style='background: #E4E1CB; padding: 1px 5px;'><strong>" . RANKING_POSITION . "</strong></th>
                                                    </tr>
                                                    {$list}
                                                </table>
                                            </td> 
                                        </tr>
                                        </table>
                                        </div>";
}

// ldRankings::gerateRankingGens (type=8 Duprian / type=9 Vanert, top 10) com GENS_MANUFACTURER da settings
function rankingGens(int $type = 8): string
{
    global $RANKING_CONFIGS;
    $familyPath = $type == 9 ? 'vanert' : 'duprian';
    $names = [1 => RANKING_GENS_GRADUATION_GRAND_DUKE, RANKING_GENS_GRADUATION_DUKE, RANKING_GENS_GRADUATION_MARQUIS, RANKING_GENS_GRADUATION_COUNT,
              RANKING_GENS_GRADUATION_VISCOUNT, RANKING_GENS_GRADUATION_BARON, RANKING_GENS_GRADUATION_KNIGHT_COMMANDER, RANKING_GENS_GRADUATION_SUPERIOR_KNIGHT,
              RANKING_GENS_GRADUATION_KNIGHT, RANKING_GENS_GRADUATION_GUARD_PREFECT, RANKING_GENS_GRADUATION_OFFICER, RANKING_GENS_GRADUATION_LIEUTENANT,
              RANKING_GENS_GRADUATION_SERGEANT, RANKING_GENS_GRADUATION_PRIVATE];
    $rows = array_map(fn($g) => [$g['name'], $g['contribution']], seedGens($type == 9 ? 2 : 1, 10));
    $o = "<div class='quadros'>
                    <table border='0' width='100%'>
                     <tr>
                      <td bgcolor='#E4E1CB' align='center'><strong>" . RANKING_PLACE . "</strong></td>
                      <td bgcolor='#E4E1CB' align='center'><strong>" . RANKING_CHARACTER_SHORT . "</strong></td>
                      <td bgcolor='#E4E1CB' align='center'><strong>" . RANKING_GENS_CONTRIBUITION . "</strong></td>
                      <td bgcolor='#E4E1CB' align='center'><strong>" . RANKING_GENS_FAMILY . "</strong></td> 
                      <td bgcolor='#E4E1CB' align='center'><strong>" . RANKING_GENS_PATENT_LOGO . "</strong></td>
                      <td bgcolor='#E4E1CB' align='center'><strong>" . RANKING_GENS_PATENT_NAME . "</strong></td>
                     </tr>";
    $posicao = 1;
    foreach ($rows as [$char, $contrib]) {
        // graduação: mesma tabela de faixas de ldrankings.class.php (GENS_MANUFACTURER == 1, X-Team)
        if ($RANKING_CONFIGS['GENS_MANUFACTURER'] == 1) {
            if ($contrib <= 999) $grad = 14; elseif ($contrib <= 4999) $grad = 13; elseif ($contrib <= 14999) $grad = 12;
            elseif ($contrib <= 49999) $grad = 11; elseif ($contrib <= 99999) $grad = 10; else $grad = 9;
            if ($contrib > 100000) {
                if ($posicao == 1) $grad = 1; elseif ($posicao <= 5) $grad = 2; elseif ($posicao <= 10) $grad = 3; elseif ($posicao <= 30) $grad = 4;
                elseif ($posicao <= 50) $grad = 5; elseif ($posicao <= 100) $grad = 6; elseif ($posicao <= 200) $grad = 7; elseif ($posicao <= 300) $grad = 8; else $grad = 9;
            }
        } else {
            $grad = 14;   // GENS_MANUFACTURER == 0: GRADUATION vem da tabela T_GensSystem
        }
        $o .= "<tr>
                      <td bgcolor='#F1F0E4' align='center'><strong>" . $posicao++ . "</strong></td>
                      <td align='center'><a href=\"?page=rankings&type=6&name={$char}\">{$char}</a></td>
                      <td align='center'>{$contrib}</td>
                      <td align='center'><img src=\"templates/sunonline/images/gens/{$familyPath}/14.jpg\" /></td> 
                      <td align='center'><img src=\"templates/sunonline/images/gens/{$familyPath}/{$grad}.jpg\" /></td>
                      <td align='center'>{$names[$grad]}</td>
                     </tr>";
    }
    return $o . "</table></div>";
}

// ldNotice::listAllNotices (?page=readNotice sem id)
function noticeList(): string
{
    $o = "<ul>";
    foreach (seedNotices() as $n) {
        $o .= "<li><a href=\"?page=readNotice&amp;id={$n['id']}\">{$n['subject']}</a> - " . date("[d/m/Y g:i a]", seedTs($n['date'])) . "</li>";
    }
    return $o;   // o original não fecha o <ul> (ldnotice.class.php:45-49)
}
// ldNotice::openNoticeId (?page=readNotice&id=100)
function noticeRead(int $id = 100): string
{
    $n = seedFind('notices', 'id', (string)$id);
    return "<h1>{$n['subject']}</h1><br />{$n['content']}<br /><br /><strong><em>" . NOTICE_ADD_DATE . "</em></strong> " . date("d/m/Y g:i a", seedTs($n['date']));
}
// ldAjax::loadNoticeSelectComments — $admin: ldPanelAdmin::checkPermission() (mostra IP e [Excluir])
function noticeComments(bool $admin, int $noticeId = 100): string
{
    global $SEED;
    // do mais novo para o mais antigo (ORDER BY id DESC); quem assina: GameIDC da conta (personagem com ou sem foto)
    // ou, sem personagem, o login
    $rows = array_values(array_filter($SEED['noticeComments'], fn($c) => $c['noticeId'] == $noticeId));
    usort($rows, fn($a, $b) => $b['id'] <=> $a['id']);
    $o = ''; $count = 0;
    foreach ($rows as $r) {
        [$account, $comment, $ts, $ip] = [$r['account'], $r['comment'], seedTs($r['date']), $r['ip']];
        $lastCharacter = seedAccount($account)['lastCharacter'] ?? null;
        $c = $lastCharacter !== null ? seedCharacter($lastCharacter) : null;
        $postBy = $c === null
            ? "<strong>{$account}</strong><br /><img src='modules/uploads/photos/nophoto.jpg' style='border: none; width:100px; height:100px;' />"
            : "<a href='?page=rankings&type=6&name={$c['name']}'><strong>{$c['name']}</strong><br /><img src='modules/uploads/photos/" . (empty($c['image']) ? "nophoto.jpg" : $c['image']) . "' style='border: none; width:100px; height:100px;' /><br />{$c['resets']} resets</a>";
        $count++;
        $o .= "<div class=\"quadros\" id=\"divComent_{$count}\">";
        $o .= "<table style='width: 100%;'>";
        $o .= "<tr>";
        $o .= "<td align='center' style='padding-right: 20px; width: 120px'>{$postBy}</td>";
        $o .= "<td valign='top'><strong><em>" . NOTICES_COMMENTS_COMMENT . "</em></strong> <br />";
        $o .= nl2br(htmlentities($comment));
        $o .= "</td>";
        $o .= "</tr>";
        $o .= "<tr>";
        $o .= "<td colspan='2' align='right'>";
        $o .= NOTICES_COMMENTS_DATE . " " . date("d/m/Y G:i:s", $ts);
        if ($admin) {
            $o .= ", IP: {$ip}";
            $o .= ", <a href=\"javascript: void(0);\" onclick=\"javascript: Verify('?page=ajax&require=noticedeletecomment&commentId={$count}', 'divComent_{$count}', 'get');\">" . NOTICES_COMMENTS_ERASE . "</a>";
        }
        $o .= "</td>";
        $o .= "</tr>";
        $o .= "</table>";
        $o .= "</div>";
    }
    return $o;
}
// screenshots::viewAll (?page=loadModule&module=screenshots&action=viewAll) — ORDER BY rate DESC, 5 por linha
function screenshotsAll(): string
{
    $o = "<tr>"; $i = 0;
    foreach (seedScreenshotsRanked() as $sh) {
        [$id, $rate] = [$sh['id'], $sh['rate']];
        $o .= "<td align=\"center\">
                                        <a href=\"?page=loadModule&module=screenshots&action=viewImage&id={$id}\">
                                        <img src=\"modules/uploads/screenshots/{$sh['image']}.small.jpg\" style=\"width: {$sh['sw']}px; height: {$sh['sy']}px;\" /><br />
                                        <strong>Quantidade de voto" . ($rate > 0 ? "s" : "") . ":</strong> {$rate}
                                        </a>
                                        </td>";
        if ((++$i % 5) == 0) $o .= "</tr><tr>";
    }
    return $o . "</tr>";
}
// screenshots::viewScreenshot (?page=loadModule&module=screenshots&action=viewImage&id=1); cw×cy limitado por $SCREENSHOTS['MAX_WIDTH']
function screenshotView(int $id = 1): string
{
    $sh = seedFind('screenshots', 'id', (string)$id);
    [$rate, $char] = [$sh['rate'], seedScreenshotAuthor($sh)];
    return "<tr>
                                          <td align=\"center\">
                                            <img src=\"modules/uploads/screenshots/{$sh['image']}.common.jpg\" style=\"width: {$sh['cw']}px; height: {$sh['cy']}px;\" />
                                          </td>
                                      </tr>
                                      <tr>
                                          <td>
                                            <strong>Legenda:</strong> {$sh['legend']}<br />
                                            <strong>Foto enviada por:</strong> {$char}<br />
                                            <strong>Enviada em:</strong> " . date("d/m/y \\a\\s G:i:s", seedTs($sh['date'])) . "<br />
                                            <strong>Quantidade de voto" . ($rate > 0 ? "s" : "") . ":</strong> {$rate}<br />
                                            <form action=\"?page=loadModule&module=screenshots&action=voteImage&id={$id}\" method=\"post\">
                                                <strong>Gostou da imagem:</strong> 
                                                <input type=\"submit\" value=\"Votar\" class=\"button\" />
                                            </form>
                                          </td>
                                      </tr>";
}
// ldGeneral::loadDivLoginLogout — {#MENU_LOGIN} por privilégio (0 user, 1 GM, 2 admin)
function menuLogin(int $previlegy): string
{
    switch ($previlegy) {
        case 1:  return "<li><a href=\"?page=paneluser\">" . PANEL_USER . "</a></li><li><a href=\"?page=panelgamemaster\">" . PANEL_GAME_MASTER . "</a></li>";
        case 2:  return "<li><a href=\"?page=paneluser\">" . PANEL_USER . "</a></li><li><a href=\"?page=panelgamemaster\">" . PANEL_GAME_MASTER . "</a></li><li><a href=\"?page=paneladmin\">" . PANEL_ADMIN . "</a></li>";
        default: return "<li><a href=\"?page=paneluser\">" . PANEL_USER . "</a></li>";
    }
}

// ldRegister: {#REGISTER_OPTIONS_BONUS} (ldregister.class.php:45-60) com kits e nomes de itens reais
function registerBonusOptions(): string
{
    global $ROOT, $REGISTER_SETTINGS;
    $db = unserialize(file_get_contents("$ROOT/modules/classes/ldItemClass/data/item.serialize.txt"));
    $o = '';
    foreach ($REGISTER_SETTINGS['BONUS_ITEM']['ITEMS'] as $key => $kit) {
        $o .= "<option value=\"{$key}\" style=\"font-weight: bold;\">&#62; {$kit["Name"]}</option>";
        $o .= "<option value=\"\" disabled=\"disabled\">--- " . REGISTER_BONUS_PACKET_DETAILS . ":</option>";
        foreach ($kit['Items'] as $item) {
            $o .= "<option value=\"\" disabled=\"disabled\">----- " . $db[$item['idCategorie']][$item['idItem']]['Name'] . "</option>";
        }
    }
    return $o;
}
// ldRegister: {#RESULT_REGISTER} após cadastro OK (ldregister.class.php:155,189,242,269) com a settings real
function registerResult(): string
{
    global $REGISTER_SETTINGS, $PANELUSER_MODULE;
    $resp = '';
    if ($REGISTER_SETTINGS['BONUS_VIP']['ACTIVE'] == true) {
        $resp .= sprintf("<div class='qdestaques2'>" . REGISTER_SUCCESS_REGISTER_BONUS_VIP . "</div>",
            $REGISTER_SETTINGS['BONUS_VIP']['DAYS'],
            $PANELUSER_MODULE['BUY_VIPS']['NAME_FLATS'][$REGISTER_SETTINGS['BONUS_VIP']['TYPE']],
            date("d-m-Y g:i a", mktime(20, 15, 0, 9, 10 + (int)$REGISTER_SETTINGS['BONUS_VIP']['DAYS'], 2026)));
    }
    if ($REGISTER_SETTINGS['BONUS_ITEM']['ACTIVE'] == true) {
        $resp .= sprintf("<div class='qdestaques2'>" . REGISTER_SUCCESS_REGISTER_BONUS_ITEMS . "</div>", $REGISTER_SETTINGS['BONUS_ITEM']['ITEMS'][0]["Name"]);
    }
    $email = $REGISTER_SETTINGS['EMAIL_ACTIVE'] == true
        ? "<div class='qdestaques2'><strong>" . CREATE_ACCOUNT_EMAIL_SEND_INFO_ACTIVE_SUCCESS . "</strong></div>"
        : "<div class='qdestaques2'><strong>" . CREATE_ACCOUNT_EMAIL_SEND_INFO_SUCCESS . "</strong></div>";
    return sprintf("<div class=\"quadros\">" . REGISTER_SUCCESS_REGISTER . "</div>" . $resp . $email,
        'novoheroi', '••••••', 'Nome da sua primeira escola?', 'colégio central');
}

// ldVips::loadAdvantages + loadHowToBuyVips
function vipsTags(): array
{
    global $PANELUSER_PREMISSIONS, $PANELUSER_MODULE;
    $suf = ['FREE', 'VIPS', 'VIPG', 'VIP3', 'VIP4', 'VIP5'];
    $t = [];
    foreach ($PANELUSER_PREMISSIONS as $key => $perm) {           // [Ligar, Free, Vip1..Vip5]
        foreach ($suf as $i => $s) $t["{$key}_{$s}"] = yesno((int)$perm[$i + 1] != 0);
    }
    foreach ($PANELUSER_MODULE['RESET'] as $tier => $cfg) {
        foreach (['LevelReset', 'LevelAfter', 'ZenRequire', 'Points'] as $f) {
            foreach ($suf as $i => $s) $t["{$f}[{$tier}]_{$s}"] = (string)$cfg[$f][$i];
        }
        foreach (['CleanItens', 'CleanMagics', 'CleanQuests'] as $f) {
            foreach ($suf as $i => $s) $t["{$f}[{$tier}]_{$s}"] = cleanflag((bool)$cfg[$f][$i]);
        }
    }
    $price = $PANELUSER_MODULE['BUY_VIPS']['PRICE'];
    foreach (['VIPSILVER' => 'VIP_1', 'VIPGOLD' => 'VIP_2', 'VIP3' => 'VIP_3', 'VIP4' => 'VIP_4', 'VIP5' => 'VIP_5'] as $tag => $k) {
        foreach ([30, 90, 180, 365] as $j => $days) $t["CASH_AMOUNT_{$tag}_{$days}_DAYS"] = (string)$price[$k][$j];
    }
    return $t;
}

// ---------- páginas ----------
// ldOnlines::__construct (ldonlines.class.php:12-41), room=all
function onlinesRows(): string
{
    global $PANELUSER_MODULE;
    $t = "<table border='0' width='100%'>
						  <tr>
						   <td align='center' bgcolor='#E2DEC5'><strong>" . ONLINES_CHARACTER . "</strong></td>
						   <td align='center' bgcolor='#E2DEC5'><strong>" . ONLINES_CONNECT_IN . "</strong></td>
						   <td align='center' bgcolor='#E2DEC5'><strong>" . ONLINES_RESETS . "</strong></td>
						   <td align='center' bgcolor='#E2DEC5'><strong>" . ONLINES_ACCOUNT_TYPE . "</strong></td>
						   <td align='center' bgcolor='#E2DEC5'><strong>" . ONLINES_SERVER . "</strong></td>
						  </tr>";
    $i = 0;
    // ConnectTM: datetime do MSSQL como a extensão mssql do PHP devolvia; tipo de conta = NAME_FLATS[webVips.type]
    foreach (seedOnlines() as ['character' => $char, 'connectTM' => $tm, 'resets' => $resets, 'vip' => $vip, 'server' => $room]) {
        ++$i;
        $t .= "<tr>
								  <td align='center' bgcolor='#EDEBDC'>" . $char . "</td>
								  <td align='center' bgcolor='#EDEBDC'>" . $tm . "</td>
								  <td align='center' bgcolor='#EDEBDC'>" . $resets . "</td>
								  <td align='center' bgcolor='#EDEBDC'>" . $PANELUSER_MODULE['BUY_VIPS']['NAME_FLATS'][$vip] . "</td>
								  <td align='center' bgcolor='#EDEBDC'>" . $room . "</td>
								</tr>";
    }
    $t .= "<tr><td colspan='5'><em><strong>" . ONLINES_TOTAL_ONLINE . " " . (int)$i . " </strong></em></td></tr></table>";
    return $t;
}

// ldBanned (ldbanned.class.php:10-40): type=characters -> loadBanned(2), type=accounts -> loadBanned(1)
function bannedTags(string $type): array
{
    // SELECT * FROM webBanneds WHERE type = X ORDER BY name DESC (os vencidos também, até o Sincronizar)
    $rows = array_map(fn($b) => [$b['name'], seedTs($b['end']), $b['by'], $b['description']], seedBans($type === 'accounts' ? 1 : 2));
    $t = '';
    foreach ($rows as [$name, $dateend, $by, $description]) {
        $t .= "<tr>
                                  <td align='center' bgcolor='#EDEBDC'>" . $name . "</td>
                                  <td align='center' bgcolor='#EDEBDC'>" . date("d/m/Y G:i", $dateend) . "</td>
                                  <td align='center' bgcolor='#EDEBDC'>" . $by . "</td>
                                  <td align='center' bgcolor='#EDEBDC'>" . $description . "</td>                           
                                </tr>";
    }
    return ['BannedTitle' => $type === 'accounts' ? BANNED_ACCOUNTS : BANNED_CHARACTERS, 'BANNED_RESULT' => $t];
}

require __DIR__ . '/catalog.php';   // mensagens.html (página do kit)
require __DIR__ . '/paneluser.php'; // painel do usuário
require __DIR__ . '/pumock.php';    // execução dos métodos originais do ldPanelUser com banco falso
require __DIR__ . '/paneladmin.php'; // painéis do GM e do admin

$PAGES = [
    'index.html'                 => ['tpl' => 'index',              'tags' => homeTags()],
    'contact.html'               => ['tpl' => 'contact',            'tags' => []],
    'downloads.html'             => ['tpl' => 'downloads',          'tags' => ['DOWNLOAD_GAMEFULL_LINK' => '#', 'DOWNLOAD_GAMEFULL_MB' => DOWNLOAD_GAMEFULL_MB, 'DOWNLOAD_PATCH_LINK' => '#', 'DOWNLOAD_PATCH_MB' => DOWNLOAD_PATCH_MB]],
    'rankings.html'              => ['tpl' => 'rankings',           'tags' => ['ResultRankings' => rankingList()]],
    'rankings-personagem.html'   => ['tpl' => 'rankings',           'tags' => ['ResultRankings' => rankingProfile()]],
    'rankings-guild.html'        => ['tpl' => 'rankings',           'tags' => ['ResultRankings' => rankingGuildProfile()]],
    'rankings-gens.html'         => ['tpl' => 'rankings',           'tags' => ['ResultRankings' => rankingGens(8)]],
    'rankings-gens-vanert.html'  => ['tpl' => 'rankings',           'tags' => ['ResultRankings' => rankingGens(9)]],
    'rankings-semanal.html'      => ['tpl' => 'rankings',           'tags' => ['ResultRankings' => rankingList(1, 1)]],
    'rankings-mensal.html'       => ['tpl' => 'rankings',           'tags' => ['ResultRankings' => rankingList(1, 2)]],
    'rankings-level.html'        => ['tpl' => 'rankings',           'tags' => ['ResultRankings' => rankingList(2)]],
    'rankings-pk.html'           => ['tpl' => 'rankings',           'tags' => ['ResultRankings' => rankingList(4)]],
    'rankings-master-resets.html'=> ['tpl' => 'rankings',           'tags' => ['ResultRankings' => rankingList(5)]],
    'rankings-guilds.html'       => ['tpl' => 'rankings',           'tags' => ['ResultRankings' => rankingGuilds()]],
    'register.html'              => ['tpl' => 'register',           'tags' => ['RESULT_REGISTER' => '', 'REGISTER_OPTIONS_BONUS' => registerBonusOptions()]],
    'register-resultado.html'    => ['tpl' => 'register',           'tags' => ['RESULT_REGISTER' => registerResult(), 'REGISTER_OPTIONS_BONUS' => registerBonusOptions()]],
    'noticias.html'              => ['tpl' => 'readNotice',         'tags' => ['ResultNotices' => noticeList()]],
    'noticia.html'               => ['tpl' => 'readNotice',         'tags' => ['ResultNotices' => noticeRead()], 'get' => ['id' => 100]],
    // estados logados (sidebar div[logout] + MENU_LOGIN por privilégio; IP/[Excluir] nos comentários só para admin)
    'noticia-usuario.html'       => ['tpl' => 'readNotice',         'tags' => ['ResultNotices' => noticeRead()], 'get' => ['id' => 100], 'login' => 0],
    'noticia-gm.html'            => ['tpl' => 'readNotice',         'tags' => ['ResultNotices' => noticeRead()], 'get' => ['id' => 100], 'login' => 1],
    'noticia-admin.html'         => ['tpl' => 'readNotice',         'tags' => ['ResultNotices' => noticeRead()], 'get' => ['id' => 100], 'login' => 2],
    'screenshots.html'           => ['tpl' => 'screenshots',        'tags' => ['SCREENSHOT_VIEW' => screenshotsAll()]],
    'screenshot.html'            => ['tpl' => 'screenshots',        'tags' => ['SCREENSHOT_VIEW' => screenshotView()]],
    'vips.html'                  => ['tpl' => 'vips',               'tags' => vipsTags()],
    'vips-vantagens.html'        => ['tpl' => 'vips[advantages]',   'tags' => vipsTags()],
    'vips-como-comprar.html'     => ['tpl' => 'vips[howtobuy]',     'tags' => vipsTags()],
    'vips-como-comprar-vip.html' => ['tpl' => 'vips[howToBuyVips]', 'tags' => vipsTags()],
    'onlines.html'               => ['tpl' => 'onlines',            'tags' => ['ONLINES_ROWS' => onlinesRows()]],
    'banned-personagens.html'    => ['tpl' => 'banned',             'tags' => bannedTags('characters')],
    'banned-contas.html'         => ['tpl' => 'banned',             'tags' => bannedTags('accounts')],
    'recovery.html'              => ['tpl' => 'recovery[password]', 'tags' => ['ResultTpl' => '']],
    'loginerror.html'            => ['tpl' => 'loginerror',         'tags' => []],
    'loginaccessdanied.html'     => ['tpl' => 'loginaccessdanied',  'tags' => []],
    // painel do usuário (logado, privilégio 0) — ver paneluser.php
    'paneluser.html'             => ['tpl' => 'paneluser',               'tags' => panelUserTags() + panelUserDefault(), 'login' => 0],
    'paneluser-desactive.html'   => ['tpl' => 'paneluser[DESACTIVE]',    'tags' => panelUserTags(), 'login' => 0],
    'paneluser-accessdanied.html'=> ['tpl' => 'paneluser[ACCESSDANIED]', 'tags' => panelUserTags(), 'login' => 0],
    // grupo A — Gerenciar Minha Conta (estado inicial; respostas em paneluser-mensagens.html)
    'paneluser-modify-data.html'       => puPage('paneluser[MODIFY_DATA]', puModifyData()),
    'paneluser-clean-vault.html'       => puPage('paneluser[CLEAN_VAULT]', ['RespostWrite' => '']),
    'paneluser-double-vault.html'      => puPage('paneluser[DOUBLE_VAULT]', ['RespostWrite' => '']),
    'paneluser-log-buys.html'          => puPage('paneluser[LOG_BUYS]', ['RespostWrite' => '']),
    'paneluser-log-buys-cash.html'     => puPage('paneluser[LOG_BUYS]', puLogBuys(1)),
    'paneluser-log-buys-vips.html'     => puPage('paneluser[LOG_BUYS]', puLogBuys(2)),
    'paneluser-modify-personalid.html' => puPage('paneluser[MODIFY_PERSONALID]', ['RespostWrite' => '']),
    'paneluser-game-disconnect.html'   => puPage('paneluser[GAME_DISCONNECT]', ['RespostWrite' => '']),
    'paneluser-transfer-cash.html'     => puPage('paneluser[TRANSFER_CASH]', puTransferCash()),
    'paneluser-collector-points.html'  => puPage('paneluser[COLLECTOR_POINTS]', puCollectorPoints()),
    // página do kit: respostas de todas as telas do painel (moldura de paneluser[DESACTIVE])
    'paneluser-mensagens.html'         => puPage('paneluser[DESACTIVE]'),
    // página do kit: moldura de loginerror.tpl com o catálogo de mensagens no lugar do conteúdo (ver catalog.php)
    'mensagens.html'             => ['tpl' => 'loginerror',         'tags' => []],
] + puGroupBPages()    // grupo B — Gerenciar Personagens (tela sem personagem + tela com Kira escolhido)
  + puGroupCPages()     // grupo C — Shop + Suporte
  + puVirtualVaultPages()   // grupo D — Baú virtual
  + puQuestionsEventPages() // grupo D — evento de perguntas (popup fancybox)
  + puScreenshotsPanelPages() // grupo D — screenshots do painel (módulo screenshots)
  + puAuctionsPages()          // grupo D — leilões (lista + um leilão por status)
  + puGoldenArcherPages()      // grupo D — Golden Archer (registro de serial)
  + gmPages()                  // painel do GM (privilégio 1)
  + paGroupAPages()            // painel do admin (privilégio 2) — grupo A: AdminCenter, contas, personagens, suporte
  + paGroupBPages()            // painel do admin — grupo B: VIPs, notícias, enquetes, SGolds, depósitos
  + paGroupCPages();           // painel do admin — grupo C: backup, ferramentas do jogo, leilões, outros

// ---------- 5. adaptações estáticas ----------
function staticHref(string $href, bool $logged): string
{
    global $PAGES;
    $h = html_entity_decode($href);
    if ($h === '?' || $h === '?page=home') return 'index.html';
    parse_str(ltrim($h, '?'), $q);
    switch ($q['page'] ?? '') {
        case 'downloads': return 'downloads.html';
        case 'contact':   return 'contact.html';
        case 'readNotice': return isset($q['id']) ? 'noticia.html' : 'noticias.html';
        case 'loadModule':
            if (($q['module'] ?? '') === 'screenshots') return match ($q['action'] ?? '') {
                'viewImage' => 'screenshot.html',
                'panelManager' => isset($q['subAction']) ? '#' : (isset($PAGES['paneluser-screenshot.html']) ? 'paneluser-screenshot.html' : '#'),
                default => 'screenshots.html' };
            return '#';
        case 'register':  return 'register.html';
        case 'rankings':
            return match ((int)($q['type'] ?? 0)) {
                1 => [1 => 'rankings-semanal.html', 2 => 'rankings-mensal.html'][(int)($q['period'] ?? 0)] ?? 'rankings.html',
                2 => 'rankings-level.html', 3 => 'rankings-guilds.html', 4 => 'rankings-pk.html', 5 => 'rankings-master-resets.html',
                6 => 'rankings-personagem.html', 7 => 'rankings-guild.html', 8 => 'rankings-gens.html', 9 => 'rankings-gens-vanert.html',
                default => 'rankings.html' };   // top=50/100/200 -> mesma página (só muda a quantidade de linhas)
        case 'vips':
            return ['advantages' => 'vips-vantagens.html', 'howToBuy' => 'vips-como-comprar.html', 'howToBuyVips' => 'vips-como-comprar-vip.html'][$q['option'] ?? ''] ?? 'vips.html';
        case 'onlines':   return 'onlines.html';   // room=all e room=<sala> -> mesma página (o filtro só reduz as linhas)
        case 'banned':    return ($q['type'] ?? '') === 'accounts' ? 'banned-contas.html' : 'banned-personagens.html';
        case 'recovery':  return 'recovery.html';
        case 'logout':    return 'index.html';     // session_destroy + header("Location: ?")
        case 'paneluser':
            // deslogado: ldPanelUser abre loginerror.tpl; logado: paneluser[-<option>].html quando já gerada
            if (!$logged) return 'loginerror.html';
            if (empty($q['option'])) return 'paneluser.html';
            $slug  = strtolower(str_replace('_', '-', $q['option']));
            $extra = array_values(array_diff(array_keys($q), ['page', 'option', 'character']));
            if ($slug === 'log-buys' && isset($q['Write'])) $file = ['1' => 'paneluser-log-buys-cash.html', '2' => 'paneluser-log-buys-vips.html'][$q['Write']] ?? '#';
            elseif ($slug === 'reset-transfer' && $extra === ['Write']) $file = 'paneluser-reset-transfer-kira.html';   // passo 2
            elseif ($slug === 'read-ticket' && $extra === ['Ticket']) $file = 'paneluser-read-ticket-view.html';       // qualquer pedido
            elseif ($slug === 'auctions' && ($q['action'] ?? '') === 'showDetailsAuction' && array_values(array_diff($extra, ['action', 'id'])) === [])
                $file = 'paneluser-auctions-' . (int)($q['id'] ?? 0) . '.html';                                           // um leilão por status
            elseif ($extra) return '#';                                            // ações (reset=true, Write=…, delete…): sem tela própria
            elseif (isset($q['character'])) $file = "paneluser-{$slug}-kira.html";  // qualquer personagem -> tela com Kira escolhido
            else $file = "paneluser-{$slug}.html";
            return isset($PAGES[$file]) ? $file : '#';
        case 'paneladmin': case 'panelgamemaster':
            // deslogado: checkPermission() falha antes do teste de sessão -> loginaccessdanied.tpl
            if (!$logged) return 'loginaccessdanied.html';
            if (empty($q['option'])) $file = "{$q['page']}.html";
            else {
                $slug  = strtolower(str_replace('_', '-', $q['option']));
                $extra = array_values(array_diff(array_keys($q), ['page', 'option']));
                if ($extra === ['Ticket']) $file = "{$q['page']}-tickets-view.html";   // qualquer pedido
                elseif ($slug === 'sincronize' && $extra === ['type']) $file = "{$q['page']}-sincronize-" . (['1' => 'vips', '2' => 'bans'][$q['type']] ?? '') . '.html';
                elseif ($extra === ['Write'] && isset(PA_SEARCH_RESULT[$slug])) $file = "{$q['page']}-{$slug}-" . PA_SEARCH_RESULT[$slug] . '.html';   // busca -> formulário
                elseif ($slug === 'modify-poll' && ($q['action'] ?? '') === 'modify' && $extra === ['action', 'idPoll']) $file = "{$q['page']}-modify-poll-" . (int)$q['idPoll'] . '.html';
                elseif ($extra === ['action'] && isset(PA_ACTION_RESULT[$slug][$q['action']])) $file = "{$q['page']}-{$slug}-" . PA_ACTION_RESULT[$slug][$q['action']] . '.html';
                elseif ($slug === 'game-chat-server' && $extra === ['loadLasts']) $file = "{$q['page']}-game-chat-server.html";   // Atualizar / Carregar N: mesma página
                elseif ($extra) return '#';                                          // ações (Write=…, SubWrite=…, action=…)
                else $file = "{$q['page']}-{$slug}.html";
            }
            return isset($PAGES[$file]) ? $file : '#';
        default:          return '#';   // painéis (logado), captcha
    }
}

// painel do admin: buscas (Write=true) que abrem um formulário preenchido -> página do exemplo (qualquer valor buscado)
const PA_SEARCH_RESULT = ['edit-account' => 'kira01', 'edit-character' => 'kira', 'transform-vip' => 'kira01', 'modify-notice' => '100'];
// idem para action=… (escolher leilão, abrir o formulário do Golden Archer)
const PA_ACTION_RESULT = ['edit-auctions' => ['load' => '4'], 'close-auctions' => ['close' => '2'], 'golden-archer' => ['createSerial' => 'create-serial']];

const KIT_JQUERY_PAGES = ['paneladmin-close-auctions-2.html', 'paneluser-questions-event.html', 'paneluser-auctions-1.html', 'paneluser-auctions-2.html', 'paneluser-auctions-3.html', 'paneluser-auctions-4.html'];

function adapt(string $html, string $page): string
{
    global $SVG_GUILD, $SVG_SHOT, $SVG_SHOT_BIG, $SVG_CAPTCHA, $SVG_PHOTO, $PAGES;

    // A1. doctype/charset: XHTML 1.0 Strict iso-8859-1 -> HTML5 utf-8
    $html = preg_replace('~<!DOCTYPE[^>]*>\s*<html[^>]*>~', "<!DOCTYPE html>\n<html lang=\"pt-BR\">", $html, 1);
    $html = preg_replace('~<meta http-equiv="Content-Type"[^>]*/>~', '<meta charset="utf-8" />', $html, 1);

    // A2. assets: remove global_gnb.css (só aviso IE6), fancybox css e TODOS os scripts de _Script (Cufon, jQuery, fancybox, Webzen)
    //     Exceção: páginas com fancybox (KIT_JQUERY_PAGES) mantêm jquery.js e os 3 arquivos do fancybox originais, em js/
    $keepJq = in_array($page, KIT_JQUERY_PAGES, true);
    $html = preg_replace($keepJq ? '~[ \t]*<link[^>]+global_gnb20101104\.css[^>]*/>\s*~' : '~[ \t]*<link[^>]+(global_gnb20101104\.css|fancybox)[^>]*/>\s*~', '', $html);
    $html = preg_replace($keepJq ? '~[ \t]*<script[^>]+templates/sunonline/_Script/(?!jquery\.js|fancybox/)[^>]*></script>\s*~' : '~[ \t]*<script[^>]+templates/sunonline/_Script/[^>]*></script>\s*~', '', $html);
    if ($keepJq) $html = str_replace('templates/sunonline/_Script/', 'js/', $html);
    $html = str_replace('templates/sunonline/_Css/common.css', 'css/common.css', $html);
    $html = str_replace('templates/sunonline/images/', 'images/', $html);   // (TEMPLATE_DIR na settings aponta p/ darkstyle; aqui é sempre sunonline)

    // A3. bloco condicional IE6 (com sua marcação) e blocos de markup comentados herdados da Webzen
    $html = preg_replace('~[ \t]*<!-- Internet explorer 6 checker -->.*?<!-- //Internet explorer 6 checker -->\s*~s', '', $html);
    $html = preg_replace('~[ \t]*<!--(?!\[if)(?:(?!-->).)*?<[a-zA-Z/](?:(?!-->).)*?-->[ \t]*\n?~s', '', $html);

    // A4. footer: Cufon.now() -> main.js
    $html = str_replace('<script type="text/javascript">Cufon.now();</script>', '<script src="js/main.js"></script>', $html);


    if ($page === 'index.html') {
        // A7. chat carregado via ajax -> itens de exemplo; equipe (ajax) -> lista de exemplo
        $html = preg_replace('~<script type="text/javascript">Verify\(\'\?page=ajax&require=chatrealtime\'[^<]*</script>~', chatItems(), $html);
        $html = str_replace('<div id="equipe" style="display: none;"></div>', '<div id="equipe" style="display: none;">' . staffBox() . '</div>', $html);
        // A8. jQuery inline que apagava a borda pontilhada das notícias -> regra CSS em common.css (bloco adicionado)
        $html = preg_replace('~[ \t]*<script type="text/javascript">\s*\$\("#maincenter div\.newsforum ul li div"\)[^<]*</script>\s*~', '', $html);
        // A9. Flash do YouTube -> iframe
        $html = preg_replace('~<object width="206" height="167">.*?</object>~s',
            '<iframe width="206" height="167" src="https://www.youtube-nocookie.com/embed/-Ct9UxNDHGQ" title="Trailer" frameborder="0" allowfullscreen style="display:block;"></iframe>', $html);
        // A10. imagens geradas pelo servidor -> placeholders SVG (logos de guild: decode=guild<n> -> logo de exemplo n)
        $html = preg_replace_callback('~src="modules/classes/logoGuildDecode\.php\?decode=guild(\d+)"~', fn($m) => 'src="' . $SVG_GUILD[$m[1] % 4] . '"', $html);
    }
    // A19. login: respostas do ajax require=login -> <template>s após #ResultAjaxLogin; main.js injeta no clique
    $html = str_replace('<div id="ResultAjaxLogin" style="margin-left: 16px; color: #CFB988;"></div>',
        '<div id="ResultAjaxLogin" style="margin-left: 16px; color: #CFB988;"></div>'
        . '<template id="loginFill"><strong>' . LOGIN_FILL_ALL_FIELDS . '</strong></template>'
        . '<template id="loginError"><strong>' . LOGIN_ERROR . '</strong></template>', $html);
    // A17. enquete: respostas do ajax (vote/result) -> <template>s logo após #pollResult; main.js injeta no clique
    $html = str_replace('<div id="pollResult"></div>', '<div id="pollResult"></div>' . str_replace('templates/sunonline/images/', 'images/', pollResponses()), $html);
    // A10 (cont.): thumbs e imagem grande de screenshot em qualquer página
    $html = preg_replace('~src="modules/uploads/screenshots/[^"]*\.small\.jpg"~', 'src="' . $SVG_SHOT . '"', $html);
    $html = preg_replace('~src="modules/uploads/screenshots/[^"]*\.common\.jpg"~', 'src="' . $SVG_SHOT_BIG . '"', $html);
    if (str_starts_with($page, 'noticia')) {
        // A15. comentários carregados via ajax (loadComments -> noticeselectcomments) -> lista de exemplo;
        //      sem id (lista de notícias) o ajax devolvia "Noticia inválida." — reproduzido
        //      IP e [Excluir] só com ldPanelAdmin::checkPermission() (privilégio >= 2)
        $comments = $page === 'noticias.html'
            ? "<div class='qdestaques'>" . NOTICES_COMMENTS_INVALID_NOTICE . "</div>"
            : noticeComments(($PAGES[$page]['login'] ?? -1) >= 2);
        $html = str_replace('<div id="commentSelectReturn"></div>', '<div id="commentSelectReturn">' . $comments . '</div>', $html);
        $html = preg_replace('~[ \t]*<script>\s*function loadComments\(\).*?</script>\s*~s', '', $html);
    }
    if ($page === 'screenshot.html') {
        // A16. form de voto (POST para ?page=loadModule&…voteImage) -> não submete
        $html = preg_replace('~<form action="\?page=loadModule[^"]*" method="post">~', '<form action="#" method="post" onsubmit="return false;">', $html);
    }
    if ($page === 'mensagens.html') {
        // página do kit: título próprio, catálogo no lugar da mensagem de loginerror, sidebar com login e enquete respondidos
        $html = str_replace('<strong>LOGIN</strong>', '<strong>MENSAGENS</strong>', $html);
        $html = str_replace('<h3 class="subtitle">Efetue o Login</h3>', '<h3 class="subtitle">Mensagens e estados</h3>', $html);
        $html = preg_replace('~<div class="qdestaques">Você precisa estar logado[^<]*</div>~', catalogContent(), $html);
        $html = str_replace('<div id="ResultAjaxLogin" style="margin-left: 16px; color: #CFB988;"></div>',
            '<div id="ResultAjaxLogin" style="margin-left: 16px; color: #CFB988;"><strong>' . LOGIN_ERROR . '</strong></div>', $html);
        preg_match('~<template id="pollResults">(.*?)</template>~s', $html, $m);
        $html = str_replace('<div id="pollResult"></div>', '<div id="pollResult">' . $m[1] . '</div>', $html);
    }
    // A25. Golden Archer: o submit do form fazia $(…).submit(… Verify('…&action=check&serial='+serial …)) com jQuery ->
    //      data-kit-ga no form + respostas por serial em <template> após #serialResponse; main.js escolhe pelo serial digitado
    if ($page === 'paneluser-golden-archer.html') {
        global $PU_AJAX_TEMPLATES;
        $html = preg_replace('~[ \t]*<script type="text/javascript">\s*\$\("#goldeArcherForm"\)\.submit\(.*?</script>\s*~s', '', $html);
        $html = str_replace('<form id="goldeArcherForm">', '<form id="goldeArcherForm" data-kit-ga="1">', $html);
        $t = '';
        foreach ($PU_AJAX_TEMPLATES[$page] as $id => $resp) $t .= "<template id=\"{$id}\">{$resp}</template>";
        $html = preg_replace('~(<div id="serialResponse">\s*</div>)~', '$1' . $t, $html, 1);
        unset($PU_AJAX_TEMPLATES[$page]);
    }
    // A24. evento de perguntas: botão "Responder" (Verify(…&answer='+jQuery('#answerFinal').val(), 'reponseSubmit')) ->
    //      data-kit-qe com a alternativa certa; as 3 respostas possíveis em <template> após #reponseSubmit
    if ($page === 'paneluser-questions-event.html') {
        global $PU_AJAX_TEMPLATES;
        $html = preg_replace("~onclick=\"javascript: Verify \('\?page=paneluser&option=QUESTIONS_EVENT&answer='[^\"]*\"~", 'data-kit-qe="2"', $html);
        $t = '';
        foreach ($PU_AJAX_TEMPLATES[$page] as $id => $resp) $t .= "<template id=\"{$id}\">{$resp}</template>";
        $html = preg_replace('~(<div id="reponseSubmit">\s*</div>)~', '$1' . $t, $html, 1);
        unset($PU_AJAX_TEMPLATES[$page]);   // não passa pela A23
    }
    // A23. ajax cuja resposta foi pré-renderizada (baú virtual): onclick="Verify('url', 'alvo', 'get')" ->
    //      data-kit-tpl/data-kit-target + <template> logo após o div alvo; main.js injeta no clique
    global $PU_AJAX_TEMPLATES;
    if (!empty($PU_AJAX_TEMPLATES[$page])) {
        $tpls = [];
        $n = 0;
        $html = preg_replace_callback("~onclick=\"Verify\('([^']*)', '(\w+)', 'get'\);\"~", function ($m) use ($page, &$tpls, &$n) {
            global $PU_AJAX_TEMPLATES;
            if (!isset($PU_AJAX_TEMPLATES[$page][$m[1]])) return $m[0];
            $id = 'kit-ajax-' . $n++;
            $tpls[$m[2]][] = "<template id=\"{$id}\">" . $PU_AJAX_TEMPLATES[$page][$m[1]] . "</template>";
            return "data-kit-tpl=\"{$id}\" data-kit-target=\"{$m[2]}\"";
        }, $html);
        foreach ($tpls as $target => $list) {
            $html = preg_replace('~(<div id="' . $target . '"></div>)~', '$1' . implode('', $list), $html, 1);
        }
    }
    if ($page === 'panelgamemaster-mensagens.html') {
        $html = preg_replace('~<h1 style="margin-top: 20px;">AdminCenter</h1>.*?(?=\s*</div>\s*</div>\s*<!-- //Content -->)~s',
            '<h1 style="margin-top: 20px;">Mensagens do painel</h1>' . gmCatalog(), $html);
    }
    if ($page === 'paneladmin-mensagens.html') {
        $html = preg_replace('~<h1 style="margin-top: 20px;">AdminCenter</h1>.*?(?=\s*</div>\s*</div>\s*<!-- //Content -->)~s',
            '<h1 style="margin-top: 20px;">Mensagens do painel</h1>' . paCatalog(), $html);
    }
    if ($page === 'paneluser-mensagens.html') {
        $html = str_replace('<h1 style="margin-top: 20px;">Opção desativada</h1>', '<h1 style="margin-top: 20px;">Mensagens do painel</h1>', $html);
        $html = str_replace('<div class="qdestaques" style="margin-bottom: 10px; margin-top: 10px;">Desculpe, mais essa opção está desativada.</div>', puCatalog(), $html);
    }
    if ($page === 'recovery.html') {
        // A18. form de recuperação (POST para ?page=recovery&type=password&Write=true) -> não submete
        $html = str_replace('<form action="?page=recovery&amp;type=password&amp;Write=true" method="post">', '<form action="#" method="post" onsubmit="return false;">', $html);
    }
    if (str_starts_with($page, 'rankings')) {
        $html = preg_replace_callback('~src=\'modules/classes/logoGuildDecode\.php\?decode=guild(\d+)\'~', fn($m) => "src='" . $SVG_GUILD[$m[1] % 4] . "'", $html);
        // A11. dois <select id="type"> na mesma página (bug do original); checkPeriod() lê o 1º via getElementById -> o 2º vira searchType
        $html = preg_replace('~<select name="type" id="type">(\s*<option value="6">)~', '<select name="type" id="searchType">$1', $html);
    }
    if (str_starts_with($page, 'register')) {
        // A12. captcha (JPEG 135x25 gerado por ldcaptcha) -> SVG; termos no iframe -> termos.html; submit POST -> navegação
        $html = str_replace('src="?page=captcha"', 'src="' . $SVG_CAPTCHA . '" width="135" height="25" alt="captcha"', $html);
        $html = str_replace("src='templates/sunonline/termos.tpl.php'", "src='termos.html'", $html);
        $html = str_replace("action='?page=register&write=true'", "action='register-resultado.html'", $html);
        $html = str_replace('document.cadastro.submit();', "window.location = 'register-resultado.html';", $html);
    }
    if ($page === 'contact.html') {
        // A14. telefone e nome pessoais do autor original -> placeholder
        $html = preg_replace('~<strong>Minas Gerais</strong>: \([0-9]{2}\) [0-9-]+ &ndash; [^<]+<br />~', '<strong>Estado</strong>: (00) 0000-0000 &ndash; Nome do Administrador<br />', $html);
    }
    if ($page === 'vips-como-comprar.html') {
        // A13. dados bancários reais do autor original -> placeholders
        $html = preg_replace_callback('~(<div class="code">).*?(</div>)~s', fn($m) => $m[1] . "\n                          Banco: Banco Exemplo <br />\n                          Ag&ecirc;ncia: 0000-0 <br />\n                          Conta:  00000-0 <br />\n                          Tipo de conta: Conta corrente <br />\n                          Favorecido: Nome do Favorecido\n                      " . $m[2], $html);
    }
    // A10 (cont., por último p/ pegar os comentários da A15): foto padrão de personagem — arquivo real,
    //   fora da pasta do template — modules/uploads/photos/ -> images/photos/
    $html = str_replace('modules/uploads/photos/nophoto.jpg', 'images/photos/nophoto.jpg', $html);
    //   fotos de denúncia -> placeholders JPG gerados em images/complaints/ (gmPages)
    $html = str_replace('modules/uploads/complaints/', 'images/complaints/', $html);
    //   comprovantes de depósito -> placeholders JPG gerados em images/deposits/ (paGroupBPages)
    $html = str_replace('modules/uploads/confirmBuys/', 'images/deposits/', $html);
    //   fotos de leilão -> placeholders JPG gerados em images/auctions/ (puAuctionsPages)
    $html = str_replace('modules/uploads/auctions/', 'images/auctions/', $html);
    //   fotos enviadas pelos jogadores (uploads) -> SVG
    $html = preg_replace("~src='modules/uploads/photos/[^']+'~", "src='" . $SVG_PHOTO . "'", $html);
    $logged = isset($PAGES[$page]['login']);
    // A20 (idem). formulários restantes que postavam para ?page=… (painel): se o destino tem tela gerada (passo 2 do
    // Transferir Resets), navegam para ela com GET; senão não submetem
    $html = preg_replace_callback('~<form\b([^>]*?)\saction=(["\'])(\?page=[^"\']*)\2([^>]*)>~', function ($m) use ($logged) {
        $to = staticHref($m[3], $logged);
        return $to !== '#' ? '<form action="' . $to . '" method="get">' : '<form' . $m[1] . ' action="#" onsubmit="return false;"' . $m[4] . '>';
    }, $html);
    // A26 (idem). notícias do admin: o botão do formulário (que não submete mais) codificava o texto em base64 no clique,
    // antes do envio — sem o envio, o texto ficaria em base64 na tela. O onclick sai; a função base64Encode() fica.
    $html = preg_replace('~\s*onclick="noticeFrom\.content\.value = base64Encode\(noticeFrom\.content\.value\);"~', '', $html);
    // A21 (idem). <select> de personagem do painel: location='?…&character='+valor -> tela com Kira escolhido
    $html = preg_replace_callback("~location='(\\?page=[^']*?)&amp;character='\\+this\\.options\\[this\\.selectedIndex\\]\\.value~",
        fn($m) => "location='" . staticHref($m[1] . '&amp;character=Kira', $logged) . "'", $html);
    // A22 (idem). botões javascript:window.location='?page=…' -> página estática (ações sem tela -> '#')
    $html = preg_replace_callback("~window\\.location\\s*=\\s*'(\\?page=[^']*)'(?:\\+[^;\"]*)?~", fn($m) => "window.location='" . staticHref($m[1], $logged) . "'", $html);
    // A5 (por último, depois das inserções por página). links ?page=... -> páginas estáticas (ou # para o que não existe no kit)
    $html = preg_replace_callback('~href=(["\'])(\?[^"\']*)\1~', fn($m) => 'href="' . staticHref($m[2], isset($PAGES[$page]['login'])) . '"', $html);
    // menu do admin: ldManager/ é outra aplicação (pasta à parte, não vem no repositório) -> '#'
    $html = str_replace('<a href="ldManager/">', '<a href="#">', $html);
    // A6 (idem). ajax removido: a caixa Equipe perde só a chamada Verify (mantém o toggle); os demais on*="…Verify(…)" caem inteiros
    $html = str_replace(" Verify('?page=ajax&require=membersstaff', 'equipe', 'get');", '', $html);
    $html = preg_replace('~\s+on(?:click|blur)="[^"]*(?:Verify|jQuery\.post)[^"]*"~', '', $html);
    return $html;
}

// ---------- render ----------
// prepara o conteúdo (includes + tags); o eval acontece no escopo GLOBAL, no loop principal,
// porque o PHP inline dos .tpl usa $PANELUSER_MODULE etc. sem declarar "global"
function prepare(array $def): string
{
    $content = tpl($def['tpl']);
    while (preg_match('/\{#INCLUDE:([^}]+)\}/', $content)) {
        $content = preg_replace_callback('/\{#INCLUDE:([^}]+)\}/', fn($m) => tpl($m[1]), $content);
    }
    // ldGeneral::loadDivLoginLogout: div[login] (deslogado) ou div[logout] + {#MENU_LOGIN} ('login' => privilégio)
    $login = $def['login'] ?? null;
    $content = str_replace('{#DIV[LOGIN_LOGOUT]}', tpl($login === null ? 'div[login]' : 'div[logout]'), $content);
    if ($login !== null) $def['tags']['MENU_LOGIN'] = menuLogin((int)$login);
    foreach (commonTags() + $def['tags'] as $k => $v) {
        $content = str_replace('{#' . $k . '}', $v, $content);
    }
    return $content;
}
function finish(string $page, string $html): void
{
    global $OUT;
    $html = adapt($html, $page);
    // bytes fora do UTF-8 (nomes coreanos em EUC-KR do item.txt, no select do Golden Archer do admin): a página original
    // era iso-8859-1 e os mostrava como Latin-1 — convertidos assim, byte a byte; o UTF-8 válido fica como está
    if (!mb_check_encoding($html, 'UTF-8')) {
        $html = preg_replace_callback('/([\xC2-\xDF][\x80-\xBF]|\xE0[\xA0-\xBF][\x80-\xBF]|[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}|\xED[\x80-\x9F][\x80-\xBF]|\xF0[\x90-\xBF][\x80-\xBF]{2}|[\xF1-\xF3][\x80-\xBF]{3}|\xF4[\x80-\x8F][\x80-\xBF]{2})|([\x80-\xFF])/',
            fn($m) => $m[1] !== '' ? $m[1] : mb_convert_encoding($m[2], 'UTF-8', 'ISO-8859-1'), $html);
        fwrite(STDERR, "nota {$page}: bytes fora do UTF-8 convertidos como Latin-1\n");
    }
    if (!mb_check_encoding($html, 'UTF-8')) exit("ERRO {$page}: saída não é UTF-8 válido\n");
    // paneluser-screenshot.html: tags de permissão do menu literais de propósito (o original não as define nessa tela)
    if ($page !== 'paneluser-screenshot.html' && preg_match_all('/\{#[^}]+\}/', $html, $m)) {
        fwrite(STDERR, "AVISO {$page}: tags sem valor: " . implode(' ', array_unique($m[0])) . "\n");
    }
    file_put_contents("$OUT/$page", $html);
    echo str_pad($page, 30) . count(explode("\n", $html)) . " linhas\n";
}

// termos.tpl.php é um fragmento (<style> + <div>) carregado em <iframe>; vira documento completo
function renderTermos(): void
{
    global $OUT;
    $src = tpl('termos');
    preg_match('~^\s*(<style>.*?</style>)\s*(.*)$~s', $src, $m);
    $doc = "<!DOCTYPE html>\n<html lang=\"pt-BR\">\n<head>\n<meta charset=\"utf-8\" />\n<title>Termos de Uso</title>\n{$m[1]}\n</head>\n<body>\n{$m[2]}\n</body>\n</html>\n";
    file_put_contents("$OUT/termos.html", $doc);
    echo str_pad('termos.html', 30) . count(explode("\n", $doc)) . " linhas\n";
}

$only = $argv[1] ?? null;
foreach ($PAGES as $__page => $__def) {
    if ($only !== null && $only !== $__page) continue;
    $__tpl = prepare($__def);
    $_GET = $__def['get'] ?? [];
    ob_start();
    eval('?>' . $__tpl);          // escopo global: $PANELUSER_MODULE, $REGISTER_SETTINGS, $SCREENSHOTS... visíveis
    finish($__page, ob_get_clean());
}
if ($only === null || $only === 'termos.html') renderTermos();
// css/common.css podado pelas páginas geradas (só no build completo: a poda depende de todas)
if ($only === null) { require __DIR__ . '/css.php'; buildCss($ROOT, $OUT); }
