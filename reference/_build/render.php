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

// Stubs do que o index.tpl.php instancia inline (módulo de screenshots, precisa de DB no original)
class ldModules { public function __construct($m) {} }
class screenshots
{
    public static function getScreenshots(): void
    {
        // screenshots.module.php:52 — "Voto".($rate > 0 ? "s" : "").": {$rate}"
        foreach ([['DarkLord', 4.5], ['Kira', 4.2], ['Zeus', 4.0], ['Elfa', 3.8], ['Mago', 3.5]] as $i => [$who, $rate]) {
            $id = $i + 1;
            echo "<td><a href=\"?page=loadModule&module=screenshots&action=viewImage&id={$id}\">{$who}<br />"
               . "<img src=\"modules/uploads/screenshots/{$id}.small.jpg\" style=\"width: 90px; height: 68px;\" /><br />"
               . "Voto" . ($rate > 0 ? "s" : "") . ": {$rate}</a></td>";
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

// enquete de exemplo: idAnswer => [resposta, votos]
$POLL_SAMPLE = [1 => ['Castle Siege', 58], 2 => ['Blood Castle 2x', 31], 3 => ['Drop de Jewels', 17]];

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
        // contadores: count(1) no banco -> números sem formatação (dados de exemplo)
        'TOTAL_ACCOUNTS' => '3412', 'TOTAL_CHARATERS' => '7905', 'RECORD_ONLINE' => '412',
        'TOTAL_ACCOUNTS_VIP_SILVER' => '87', 'TOTAL_ACCOUNTS_VIP_GOLD' => '34', 'TOTAL_ACCOUNTS_VIP_3' => '12',
        'TOTAL_ACCOUNTS_VIP_4' => '9', 'TOTAL_ACCOUNTS_VIP_5' => '4',
        'TOTAL_CHARACTERS_BANNEDS' => '12', 'TOTAL_ACCOUNTS_BANNEDS' => '5', 'TOTAL_ACCOUNTS_ONLINES' => '184',
        'ResultTime' => '0.0123',
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
    global $POLL_SAMPLE;
    $pollId = 1;
    $p = "<li><strong>Qual evento você quer no fim de semana?</strong></li>";
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
    // loadLastNotices (ldhome.class.php:37)
    $n = '';
    $notices = [
        ['Servidor atualizado para a versão 2.5.4', mktime(20, 15, 0, 9, 10, 2026)],
        ['Castle Siege neste domingo às 20h',       mktime(18, 40, 0, 9, 8, 2026)],
        ['Novo sistema de VIP disponível',          mktime(11, 2, 0, 9, 5, 2026)],
        ['Manutenção programada: sexta 03h',        mktime(21, 30, 0, 9, 2, 2026)],
        ['Evento de drop de Jewels ativado',        mktime(16, 12, 0, 8, 30, 2026)],
    ];
    foreach ($notices as $i => [$subject, $ts]) {
        $id = 100 - $i;
        $n .= "<li><div style=\"border-bottom-color:#C2CAD3; border-bottom-width:thin; border-bottom-style:dotted;\"><a href=\"?page=readNotice&amp;id={$id}\">{$subject}</a> - [" . date("d/m/Y g:i a", $ts) . "]</div></li>";
    }
    $t['LAST_NOTICES_HOME'] = $n;

    // loadTop* (ldhome.class.php:113 etc.)
    $top = fn(array $rows) => implode('', array_map(fn($r) => "<li><a href=\"?page=rankings&type=6&name={$r[0]}\">{$r[0]}</a> - {$r[1]}</li>", $rows));
    $t['RANK_TOP_RESETS_HOME']        = $top([['DarkLord', 412], ['Kira', 398], ['Zeus', 371], ['Elfa', 350]]);
    $t['RANK_TOP_RESETS_WEEK_HOME']   = $top([['Mago', 28], ['DarkLord', 25], ['Summoner', 22], ['Kira', 19]]);
    $t['RANK_TOP_RESETS_MONTH_HOME']  = $top([['DarkLord', 96], ['Mago', 90], ['Zeus', 84], ['Elfa', 77]]);
    $t['RANK_TOP_MASTER_RESETS_HOME'] = $top([['Zeus', 7], ['DarkLord', 6], ['Kira', 5], ['Mago', 4]]);
    $t['RANK_TOP_PK_HOME']            = $top([['Assassin', 1204], ['DarkLord', 987], ['Zeus', 812], ['Kira', 640]]);
    $t['RANK_TOP_LEVEL_HOME']         = $top([['Summoner', 400], ['Elfa', 400], ['Mago', 398], ['Kira', 395]]);

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

    // loadTopGuilds (ldhome.class.php:180)
    $g = '';
    foreach ([['Legends', 5320], ['Immortals', 4875], ['Phoenix', 4110], ['Valhalla', 3902]] as $i => [$name, $score]) {
        $g .= sprintf("<td align=\"center\"><a href=\"?page=rankings&type=7&name=%s\"><strong>%s</strong><br /><img src=\"modules/classes/logoGuildDecode.php?decode=%s\" width=\"100\" height=\"100\" /><br />Pontos: %d</a></td>", $name, $name, "guild$i", $score);
    }
    $t['RANK_TOP_GUILDS_HOME'] = $g;

    // loadCastleSiege (ldhome.class.php:28-29)
    $t['CASTLE_SIEGE_OCCUPY_GUILD'] = 'Legends';
    $t['CASTLE_SIEGE_END_DATE']     = $CASTLE_SIEGE['CONFRONTATION'];
    return $t;
}

// ldAjax: chat em tempo real (ldajax.class.php:61-65) e equipe (:190-197)
function chatItems(): string
{
    $rows = [
        ['chat',  CHAT_MSG_CHAT,  '20:41', 'DarkLord', 'alguém pra BC 7?'],
        ['guild', CHAT_MSG_GUILD, '20:42', 'Kira',     'reunião da guild às 21h'],
        ['post',  CHAT_MSG_POST,  '20:43', 'Zeus',     'vendo Bone Blade +13 @ Lorencia'],
        ['party', CHAT_MSG_PARTY, '20:44', 'Elfa',     'bora Kalima 6'],
        ['chat',  CHAT_MSG_CHAT,  '20:45', 'Mago',     'gg'],
    ];
    $o = '';
    foreach ($rows as [$cls, $type, $hour, $name, $msg]) {
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
    foreach ([['Admin', 1], ['GM_Kira', 0], ['GM_Zeus', 1]] as [$name, $on]) {
        $o .= "&nbsp; - <strong>{$name}</strong> - " . ($on == 0 ? "<strong class='staffOffline'>" . STAFF_OFFLINE . "</strong>" : "<strong class='staffOnline'>" . STAFF_ONLINE . "</strong>") . "<br />";
    }
    return $o . "</div>";
}

// Personagens de exemplo, base de todos os rankings (top-4 de cada tipo = tops da home)
// vip type, class code, level, pk, resets, resetsweek, resetsmonth, mresets
$RANK_CHARS = [
    'DarkLord' => [2, 48, 390,  987, 412, 25, 96, 6], 'Kira'     => [1, 17, 395,  640, 398, 19, 70, 5],
    'Zeus'     => [0,  2, 388,  812, 371, 17, 84, 7], 'Elfa'     => [2, 34, 400,   -1, 350, 15, 77, 3],
    'Mago'     => [0,  2, 398,  120, 342, 28, 90, 4], 'Summoner' => [1, 82, 400,   55, 318, 22, 64, 3],
    'Assassin' => [0, 17, 380, 1204, 305, 12, 51, 2], 'Gladius'  => [0, 66, 377,  210, 290,  9, 40, 1],
    'Lunar'    => [2, 34, 386,   -1, 276,  6, 33, 1], 'Thorn'    => [0, 48, 365,   33, 251,  3, 21, 0],
];

// ldRankings::gerateRankingResets (type=1, period 0/1/2), gerateRankingLevel (2), gerateRankingPks (4),
// gerateRankingMasterResets (5) — top=10. As 4 funções só mudam a coluna destacada (#CDC89E), a coluna de
// valor, o ORDER BY e espaços em branco; cada detalhe abaixo segue a função correspondente.
function rankingList(int $type = 1, int $period = 0): string
{
    global $PANELUSER_MODULE, $RANK_CHARS;
    // índices em $RANK_CHARS: 2 level, 3 pk, 4 resets, 5 week, 6 month, 7 mresets
    $valueCol = match ($type) { 5 => 7, 1 => [0 => 4, 1 => 5, 2 => 6][$period] ?? 4, default => 4 };
    $sortKeys = match ($type) {
        1 => $period == 0 ? [4, 2] : [$valueCol, 4, 2],   // resets desc | resetsweek/month desc, resets desc (+ cLevel desc)
        2 => [2, 4], 4 => [3, 4], 5 => [7, 4],            // clevel | pkcount | mresets desc, resets desc
    };
    $rows = $RANK_CHARS;
    uasort($rows, function ($x, $y) use ($sortKeys) {
        foreach ($sortKeys as $k) if ($x[$k] != $y[$k]) return $y[$k] <=> $x[$k];
        return 0;
    });
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
    foreach ($rows as $char => $d) {
        [$vip, $class, $lvl, $pk] = $d;
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
    $guilds = [ // G_Name, G_Master, assistente, battle master, G_Score (as 4 primeiras = tops da home e perfil da Legends)
        ['Legends', 'DarkLord', 'Kira', 'Zeus', 5320], ['Immortals', 'Assassin', 'Gladius', '', 4875],
        ['Phoenix', 'Lunar', 'Thorn', '', 4110], ['Valhalla', 'Raven', '', '', 3902],
        ['Tempest', 'Wolf', 'Nyx', '', 2750], ['Nomads', 'Drako', '', '', 1180],
    ];
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
function rankingProfile(): string
{
    global $PANELUSER_MODULE, $RANKING_CONFIGS;
    $name = 'DarkLord';
    $stats = $RANKING_CONFIGS['STATS'] == TRUE;
    $s = fn(string $label, $val) => $stats ? "<strong>{$label}:</strong> {$val} <br />" : '';
    return "<h1>" . RANKING_RESULT_OF . " {$name}</h1>
                                        <div class='quadros'>
                                        <table width='100%' border='0'>
                                        <tr>
                                            <td width='25%' align='center' valign='middle' style='padding-top: 2px;'>
                                            <p><img src='modules/uploads/photos/nophoto.jpg' alt='' width='100' height='100' style='border: 2px solid #222222;' /></p>
                                            </td>
                                            <td width='37%'>
                                                <strong>" . RANKING_CLASS . ":</strong> " . className(48) . " <br />
                                                <strong>" . RANKING_RESETS . ":</strong> 412 <br />
                                                <strong>" . RANKING_LEVEL . ":</strong> 390 <br />
                                                <strong>" . RANKING_POINTS . ":</strong> 1250 <br />
                                                " . $s(RANKING_MAP, 'Lorencia') . "
                                                " . $s(RANKING_MAP_POS_X, 135) . "
                                                " . $s(RANKING_MAP_POS_Y, 128) . "
                                                <strong>" . RANKING_ZEN . ":</strong> 1850000000
                                            </td>
                                            <td width='38%' valign='top'>
                                                " . $s(RANKING_STRENGHT, 32767) . "
                                                " . $s(RANKING_DEXTERITY, 8500) . "
                                                " . $s(RANKING_VITALITY, 12000) . "
                                                " . $s(RANKING_ENERGY, 4200) . "
                                                " . $s(RANKING_LEADERSHIP, 6100) . "
                                                <strong>" . RANKING_STATUS . ":</strong> " . RANKING_STATUS_ONLINE . " <br />
                                                <strong>" . RANKING_GUILD . ":</strong> <a href=\"?page=rankings&type=7&name=Legends\">Legends</a> <br />
                                                <strong>" . RANKING_TYPE_ACCOUNT . ":</strong> " . $PANELUSER_MODULE['BUY_VIPS']['NAME_FLATS'][2] . "
                                            </td>
                                        </tr>
                                        </table>
                                        </div>";
}

// ldRankings::geratePerfilGuild (type=7&name=Legends)
function rankingGuildProfile(): string
{
    $name = 'Legends';
    $members = [ // Name, G_Level, G_Status, class code, resets
        ['DarkLord', 1, 128, 48, 412], ['Kira', 0, 64, 17, 398], ['Zeus', 0, 32, 2, 371],
        ['Elfa', 0, 0, 34, 350], ['Mago', 0, 0, 2, 342], ['Summoner', 0, 0, 82, 318],
    ];
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
                                            <p><img src='modules/classes/logoGuildDecode.php?decode=guild0' alt='' width='100' height='100' style='border: 2px solid #222222;' /></p>
                                            </td>
                                            <td width='75%'> 
                                                <strong>" . RANKING_GUILD_SCORE . "</strong>: 5320 <br /> 
                                                <strong>" . RANKING_GUILD_GM . "</strong>: <a href=\"?page=rankings&type=6&name=DarkLord\">DarkLord</a> <br />    
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
    $rows = $type == 9
        ? [['Raven', 212400], ['Wolf', 160900], ['Nyx', 131000], ['Drako', 99800], ['Lyra', 64200],
           ['Orion', 38500], ['Vex', 16100], ['Iris', 4999], ['Brom', 1500], ['Kael', 320]]
        : [['DarkLord', 245800], ['Kira', 180400], ['Zeus', 152000], ['Elfa', 121500], ['Mago', 104300],
           ['Summoner', 88200], ['Assassin', 41000], ['Gladius', 12750], ['Lunar', 3200], ['Thorn', 640]];
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
    foreach (sampleNotices() as $i => [$subject, $ts]) {
        $id = 100 - $i;
        $o .= "<li><a href=\"?page=readNotice&amp;id={$id}\">{$subject}</a> - " . date("[d/m/Y g:i a]", $ts) . "</li>";
    }
    return $o;   // o original não fecha o <ul> (ldnotice.class.php:45-49)
}
// ldNotice::openNoticeId (?page=readNotice&id=100)
function noticeRead(): string
{
    [$subject, $ts] = sampleNotices()[0];
    $content = sampleNoticeContent();
    return "<h1>{$subject}</h1><br />{$content}<br /><br /><strong><em>" . NOTICE_ADD_DATE . "</em></strong> " . date("d/m/Y g:i a", $ts);
}
// ldAjax::loadNoticeSelectComments — $admin: ldPanelAdmin::checkPermission() (mostra IP e [Excluir])
function noticeComments(bool $admin): string
{
    global $RANK_CHARS;
    // os 3 ramos de $postBy: personagem sem foto (nophoto), conta sem personagem, personagem com foto enviada
    $rows = [ // char (null = conta sem personagem), account, comentário, timestamp, ip, Character.image
        ['Kira', 'kira01', "Boa! Finalmente o ranking de gens.\nParabéns pela atualização.", mktime(21, 5, 12, 9, 10, 2026), '187.10.22.1', ''],
        [null,   'novato7', 'O painel ainda dá erro pra mim ao resetar, alguém mais?', mktime(20, 48, 30, 9, 10, 2026), '201.55.9.87', ''],
        ['Zeus', 'zeus', 'Testado aqui, tudo ok. Valeu ADM.', mktime(20, 31, 2, 9, 10, 2026), '177.8.140.3', 'zeus_1757541234.jpg'],
    ];
    $o = ''; $count = 0;
    foreach ($rows as [$char, $account, $comment, $ts, $ip, $image]) {
        $postBy = $char === null
            ? "<strong>{$account}</strong><br /><img src='modules/uploads/photos/nophoto.jpg' style='border: none; width:100px; height:100px;' />"
            : "<a href='?page=rankings&type=6&name={$char}'><strong>{$char}</strong><br /><img src='modules/uploads/photos/" . (empty($image) ? "nophoto.jpg" : $image) . "' style='border: none; width:100px; height:100px;' /><br />{$RANK_CHARS[$char][4]} resets</a>";
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
function sampleNotices(): array
{
    return [
        ['Servidor atualizado para a versão 2.5.4', mktime(20, 15, 0, 9, 10, 2026)],
        ['Castle Siege neste domingo às 20h',       mktime(18, 40, 0, 9, 8, 2026)],
        ['Novo sistema de VIP disponível',          mktime(11, 2, 0, 9, 5, 2026)],
        ['Manutenção programada: sexta 03h',        mktime(21, 30, 0, 9, 2, 2026)],
        ['Evento de drop de Jewels ativado',        mktime(16, 12, 0, 8, 30, 2026)],
    ];
}
function sampleNoticeContent(): string   // conteúdo (HTML) da notícia 100
{
    return "Atualizamos o servidor para a versão 2.5.4 do MuSite.<br /><br />Principais mudanças:<br />"
         . "- Novo sistema de bônus no cadastro;<br />- Ranking de Gens;<br />- Correções no painel do usuário.<br /><br />"
         . "Qualquer problema, abra um ticket pelo painel.";
}
function sampleShots(): array   // id => [char, rate]
{
    return [1 => ['DarkLord', 4.5], 2 => ['Kira', 4.2], 3 => ['Zeus', 4.0], 4 => ['Elfa', 3.8], 5 => ['Mago', 3.5], 6 => ['Summoner', 2.0], 7 => ['Assassin', 0]];
}
// screenshots::viewAll (?page=loadModule&module=screenshots&action=viewAll) — 5 por linha
function screenshotsAll(): string
{
    $o = "<tr>"; $i = 0;
    foreach (sampleShots() as $id => [$char, $rate]) {
        $o .= "<td align=\"center\">
                                        <a href=\"?page=loadModule&module=screenshots&action=viewImage&id={$id}\">
                                        <img src=\"modules/uploads/screenshots/{$id}.small.jpg\" style=\"width: 90px; height: 68px;\" /><br />
                                        <strong>Quantidade de voto" . ($rate > 0 ? "s" : "") . ":</strong> {$rate}
                                        </a>
                                        </td>";
        if ((++$i % 5) == 0) $o .= "</tr><tr>";
    }
    return $o . "</tr>";
}
// screenshots::viewScreenshot (?page=loadModule&module=screenshots&action=viewImage&id=1); cw×cy limitado por $SCREENSHOTS['MAX_WIDTH']
function screenshotView(): string
{
    $id = 1; [$char, $rate] = sampleShots()[$id];
    return "<tr>
                                          <td align=\"center\">
                                            <img src=\"modules/uploads/screenshots/{$id}.common.jpg\" style=\"width: 640px; height: 480px;\" />
                                          </td>
                                      </tr>
                                      <tr>
                                          <td>
                                            <strong>Legenda:</strong> Castle Siege de domingo — Legends defendendo o castelo<br />
                                            <strong>Foto enviada por:</strong> {$char}<br />
                                            <strong>Enviada em:</strong> " . date("d/m/y \\a\\s G:i:s", mktime(21, 12, 40, 9, 6, 2026)) . "<br />
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
        'darklord', '••••••', 'Nome da sua primeira escola?', 'colégio central');
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
    foreach ([['DarkLord', 'Sep 23 2026 7:02PM', 412, 3, 'Server 1'], ['Kira', 'Sep 23 2026 7:40PM', 398, 2, 'Server 1'],
              ['Zeus', 'Sep 23 2026 8:05PM', 371, 0, 'Server 2'], ['Elfa', 'Sep 23 2026 8:11PM', 350, 1, 'Server 1'],
              ['Mago', 'Sep 23 2026 8:26PM', 96, 0, 'Server 2'], ['Summoner', 'Sep 23 2026 8:31PM', 22, 5, 'Server 1']] as [$char, $tm, $resets, $vip, $room]) {
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
    $rows = $type === 'accounts'
        ? [['vendedor01', mktime(0, 0, 0, 12, 31, 2030), 'Admin', 'Venda de conta'], ['bot_farm', mktime(12, 0, 0, 10, 15, 2026), 'GM_Kira', 'Uso de bot']]
        : [['H4ck3r', mktime(0, 0, 0, 12, 31, 2030), 'Admin', 'Uso de hack'], ['Flooder', mktime(18, 30, 0, 9, 30, 2026), 'GM_Zeus', 'Flood no chat global'],
           ['Trapaceiro', mktime(9, 0, 0, 10, 2, 2026), 'GM_Kira', 'Golpe em trade']];
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
        // A10. imagens geradas pelo servidor -> placeholders SVG
        $i = 0;
        $html = preg_replace_callback('~src="modules/classes/logoGuildDecode\.php\?decode=[^"]*"~', function () use (&$i, $SVG_GUILD) { return 'src="' . $SVG_GUILD[$i++ % 4] . '"'; }, $html);
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
        $html = preg_replace('~src=\'modules/classes/logoGuildDecode\.php\?decode=[^\']*\'~', "src='" . $SVG_GUILD[0] . "'", $html);
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
