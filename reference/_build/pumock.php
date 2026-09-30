<?php
/*
 * Executa os métodos ORIGINAIS de ldpaneluser.class.php (build-time) contra um banco falso.
 * O resultado de cada $ldTpl->set() vira tag da página: marcação e textos saem do código original,
 * só os dados vêm das tabelas de exemplo abaixo.
 *
 * FakeDB responde SELECTs lendo a lista de colunas e a tabela da própria query; filtra por igualdades
 * simples do WHERE (col='x' / col=n). UPDATE/INSERT/DELETE/EXEC não fazem nada e devolvem true.
 */

// ---------- polyfills PHP 5 usados pela classe ----------
if (!function_exists('eregi')) {
    function eregi($p, $s) { return preg_match('~' . str_replace('~', '\~', $p) . '~i', (string)$s) === 1; }
}
if (!function_exists('eregi_replace')) {
    function eregi_replace($p, $r, $s) { return preg_replace('~' . str_replace('~', '\~', $p) . '~i', $r, $s); }
}

final class FakeResult
{
    public array $rows;
    public int $i = 0;
    public function __construct(array $rows) { $this->rows = array_values($rows); }
}
function mssql_num_rows($r) { return $r instanceof FakeResult ? count($r->rows) : 0; }
// linha como objeto; colunas sem distinção de maiúsculas (como no SQL Server): SELECT * devolve as chaves minúsculas
// do banco falso e o código lê $obj->cLevel
#[AllowDynamicProperties]
final class FakeRow
{
    public function __construct(array $row) { foreach ($row as $k => $v) $this->$k = $v; }
    public function __get($name) { foreach (get_object_vars($this) as $k => $v) if (strcasecmp($k, $name) === 0) return $v; return null; }
    public function __isset($name) { return $this->__get($name) !== null; }
}
function mssql_fetch_object($r) { return ($r instanceof FakeResult && $r->i < count($r->rows)) ? new FakeRow($r->rows[$r->i++]) : false; }
function mssql_fetch_row($r) { return ($r instanceof FakeResult && $r->i < count($r->rows)) ? array_values($r->rows[$r->i++]) : false; }
function mssql_fetch_array($r)
{
    if (!($r instanceof FakeResult) || $r->i >= count($r->rows)) return false;
    $row = $r->rows[$r->i++];
    return $row + array_values($row);
}
function mssql_query($sql) { return FakeDB::query($sql); }

class ldMssql { public function query($cmd) { return FakeDB::query($cmd); } }
$ldMssql = new ldMssql();   // ldVault/ldItemMake usam o global

// exit() dos métodos (respostas de ajax, erros) vira exceção capturável — transformação só em tempo de build
final class PuExit extends Exception {}
function puNoExit(string $src): string { return preg_replace('~\bexit\s*\(~', 'throw new PuExit(', $src); }
// PHP 5.3: passagem por referência na chamada (f($a, &$b)) — erro de sintaxe desde o 5.4; só nas linhas de chamada
function puCompat(string $src): string
{
    $src = implode("\n", array_map(fn($l) => str_contains($l, 'function') ? $l : preg_replace('~([(,]\s*)&(\$\w)~', '$1$2', $l), explode("\n", $src)));
    return preg_replace('~\$(\w+)\{([^{}\n]*)\}~', '$$1[$2]', $src);   // PHP 8: $str{$i} (acesso a caractere) -> $str[$i]
}
class ldLanguage { public function __construct(...$a) {} }   // os .lang.php já foram carregados pelo harness

// captura o que o método passa para o template
final class TplCapture
{
    public array $tags = [];
    public ?string $opened = null;
    public function set($name, $value) { $this->tags[$name] = $value; }
    public function open($file) { $this->opened = $file; }
}

final class FakeDB
{
    public static array $tables = [];   // tabela (minúscula) => linhas com chaves minúsculas
    public static array $log = [];
    public static int $serial = 0x0003A1F0;
    public static ?string $fail = null;   // regex: comando que "falha" (devolve false), para mostrar a mensagem de erro

    public static function query(string $sql)
    {
        self::$log[] = $sql;
        $s = preg_replace('~^\s*use\s+\[?\w+\]?;\s*~i', '', trim($sql));
        if (preg_match('~WZ_GetItemSerial~i', $s)) return new FakeResult([['serial' => ++self::$serial]]);   // ldItemMake
        if (preg_match('~^exec\s+sp_databases~i', $s)) return new FakeResult(self::$tables['sp_databases'] ?? []);   // Gerar backup
        if (self::$fail !== null && preg_match(self::$fail, $s)) return false;                                   // erro simulado
        if (!preg_match('~^select\s~i', $s)) return true;
        if (!preg_match('~^select\s+(?:top\s+\S+\s+)?(.*?)\s+from\s+([\w\.\[\]]+)(.*)$~is', $s, $m)) return new FakeResult([]);
        $cols  = self::columns($m[1]);
        $table = strtolower(preg_replace('~^.*\.~', '', str_replace(['[', ']'], '', $m[2])));
        $rows  = self::$tables[$table] ?? [];
        if (preg_match('~\bwhere\b(.*?)(?:\border\s+by\b.*)?$~is', $m[3], $w)) {
            preg_match_all("~\[?(\w+)\]?\s*=\s*(?:'([^']*)'|(-?\d+))~", $w[1], $conds, PREG_SET_ORDER);
            foreach ($conds as $c) {
                $col = strtolower($c[1]);
                $val = isset($c[3]) && $c[3] !== '' ? $c[3] : $c[2];
                $rows = array_filter($rows, fn($r) => !array_key_exists($col, $r) || strcasecmp((string)$r[$col], (string)$val) === 0);
            }
        }
        // ORDER BY <coluna> [ASC|DESC] (só a primeira coluna)
        if (preg_match('~\border\s+by\s+\[?(\w+)\]?(?:\s+(asc|desc))?~i', $m[3], $o)) {
            $col = strtolower($o[1]);
            $dir = strtolower($o[2] ?? 'asc') === 'desc' ? -1 : 1;
            usort($rows, fn($x, $y) => $dir * (($x[$col] ?? 0) <=> ($y[$col] ?? 0)));
        }
        // agregados (count/sum) -> uma linha
        foreach ($cols as [$expr, $alias]) {
            if (preg_match('~^count\(~i', $expr)) return new FakeResult([[$alias => count($rows)]]);
        }
        $out = [];
        foreach ($rows as $r) {
            $o = [];
            foreach ($cols as [$expr, $alias]) {
                $key = strtolower(str_replace(['[', ']'], '', $expr));
                if ($key === '*') { $o += $r; continue; }
                if (!str_contains($key, '(')) $key = preg_replace('~^\w+\.~', '', $key);   // MEMB_STAT.memb___id (JOIN)
                if (str_contains($key, '(') && array_key_exists(strtolower($alias), $r)) $key = strtolower($alias);
                elseif (str_contains($key, '(') && preg_match('~(\w+)\s*\)+\s*$~', $key, $inner)) $key = $inner[1];
                $o[$alias] = preg_match('~^-?\d+$~', $key) ? (int)$key : ($r[$key] ?? null);
            }
            $out[] = $o;
        }
        return new FakeResult($out);
    }

    // "a, b as c, [d] [e], convert(...) x" -> [[expr, alias], ...]
    private static function columns(string $list): array
    {
        $parts = [];
        $depth = 0;
        $cur = '';
        foreach (str_split($list) as $ch) {
            if ($ch === '(') $depth++;
            if ($ch === ')') $depth--;
            if ($ch === ',' && $depth === 0) { $parts[] = $cur; $cur = ''; continue; }
            $cur .= $ch;
        }
        $parts[] = $cur;
        $cols = [];
        foreach ($parts as $p) {
            $p = trim($p);
            if (preg_match('~^(.*?)\s+as\s+\[?(\w+)\]?$~is', $p, $m)) $cols[] = [trim($m[1]), $m[2]];
            elseif (preg_match('~^(.+?)\s+\[(\w+)\]$~s', $p, $m)) $cols[] = [trim($m[1]), $m[2]];
            elseif (preg_match('~^(.+\))\s+(\w+)$~s', $p, $m)) $cols[] = [trim($m[1]), $m[2]];
            else $cols[] = [$p, preg_replace('~^.*\.~', '', str_replace(['[', ']'], '', $p))];
        }
        return $cols;
    }
}

// ---------- carrega a classe original ----------
$PU_SOURCE = latin1("$ROOT/modules/classes/ldpaneluser.class.php");
eval('?>' . puNoExit($PU_SOURCE));
// ldItemClass (baú, coletor): os métodos fazem require_once("ldItemClass/…") — cópias em UTF-8 e sem exit() num
// diretório temporário que vai na frente do include_path (o original não é tocado)
$PU_INCLUDE = sys_get_temp_dir() . '/sunonline-static-build';
@mkdir("$PU_INCLUDE/ldItemClass", 0777, true);
foreach (glob("$ROOT/modules/classes/ldItemClass/*.php") as $__f) {
    file_put_contents("$PU_INCLUDE/ldItemClass/" . basename($__f), puCompat(puNoExit(latin1($__f))));
}
// Os métodos rodam com o diretório atual num ESPELHO temporário da raiz do site, nunca no repositório: o código
// original grava arquivos (ldItemDatabase::createDatabase regrava o banco de itens; writeLog grava logs).
// No original, o fileHash do item.serialize.txt não bate com o md5 do item.txt, então o PHP regeneraria o banco
// na primeira visita; na cópia do espelho o hash é acertado para usar o banco serializado que veio com o site.
$PU_SITE = "$PU_INCLUDE/site";
@mkdir("$PU_SITE/modules/classes/ldItemClass/data", 0777, true);
copy("$ROOT/modules/item.txt", "$PU_SITE/modules/item.txt");
$__db = unserialize(file_get_contents("$ROOT/modules/classes/ldItemClass/data/item.serialize.txt"));
$__db['fileHash'] = md5_file("$ROOT/modules/item.txt");
file_put_contents("$PU_SITE/modules/classes/ldItemClass/data/item.serialize.txt", serialize($__db));
unset($__db);
$PANELUSER_MODULE['LOG']['Active'] = false;   // writeLog() não grava nada durante o build
$PANELADMIN_MODULE['LOG']['Active'] = false;

// ldpaneladmin.class.php: o painel do GM chama ldPanelAdmin::metodo() estaticamente (no PHP 5 o $this era o do
// chamador; no PHP 8 é Error) — aqui os métodos rodam numa instância de ldPanelAdmin, e as chamadas internas
// ldPanelAdmin::x() viram $this->x(). Idem ldPanelUser::classNameDefine() (Editar personagem): roda numa instância
// de ldPanelUser criada sem o construtor.
$PA_SOURCE = latin1("$ROOT/modules/classes/ldpaneladmin.class.php");
function puUserInstance(): ldPanelUser { return (new ReflectionClass('ldPanelUser'))->newInstanceWithoutConstructor(); }
// Verificar atualização baixa uma lista (e arquivos) do servidor do autor com o login/senha da settings: na cópia
// carregada, as duas chamadas remotas viram stubs locais — o build nunca acessa a rede.
function puFakeRemoteGet(string $url) { return $GLOBALS['PA_FAKE_UPDATE_LIST'] ?? ''; }             // '' = servidor fora do ar
function puFakeRemoteCopy(string $url, string $to) { return array_shift($GLOBALS['PA_FAKE_COPY']); } // resultado de cada download
$__pa = preg_replace(['~@file_get_contents\(\s*"http~', '~\bcopy\(\s*"http~'], ['puFakeRemoteGet("http', 'puFakeRemoteCopy("http'], puCompat(puNoExit($PA_SOURCE)), -1, $__n);
if ($__n !== 2 || preg_match('~file_get_contents\(\s*"http|\bcopy\(\s*"http|fsockopen|curl_~', $__pa)) exit("pumock: chamada remota não isolada no ldpaneladmin\n");
eval('?>' . str_replace('ldPanelUser::classNameDefine(', 'puUserInstance()->classNameDefine(',
    preg_replace('~\bldPanelAdmin::(\w+)\(~', '$this->$1(', $__pa)));
unset($__pa, $__n);
// PHP 5: constante inexistente virava o próprio nome (com um notice); no PHP 8 é Error. Emula o PHP 5 para
// as constantes que a classe usa e nenhum .lang.php define — e guarda a lista para o catálogo sinalizar.
$PU_UNDEFINED = [];
preg_match_all('~(?<![\w$\'"])([A-Z][A-Z0-9]*_[A-Za-z0-9_]+)(?![\w\'"(])~', preg_replace('~(["\'])(?:\\\\.|(?!\1).)*\1~s', '""', $PU_SOURCE . $PA_SOURCE), $__m);
foreach (array_unique($__m[1]) as $__c) {
    if (!defined($__c)) { define($__c, $__c); $PU_UNDEFINED[] = $__c; }
}

// ---------- dados de exemplo: o seed do app (seed.php), nas tabelas e colunas do MuSite/MuServer ----------
// Baús (warehouse / ExtWareHouseVirtual) são gravados em hexa pelo ldItemMake: puVaultHex / puVirtualVaultRows, abaixo,
// com os mesmos itens do seed (a durabilidade e o serial que o ldItemMake calcula são os que o seed guarda).
function puSeedDB(): void
{
    global $SEED;
    $lower = fn(array $rows) => array_map('array_change_key_case', $rows);
    $bySeed = fn(string $table, callable $fn) => array_values(array_map($fn, $SEED[$table]));
    // MEMB_STAT só existe para quem já conectou; o GameIDC vai junto para os JOINs com a AccountCharacter
    $stat = array_filter($SEED['accounts'], fn($a) => $a['connectTM'] !== '');
    FakeDB::$tables = [
        'character' => $lower($bySeed('characters', fn($c) => [
            'AccountID' => $c['account'], 'Name' => $c['name'], 'Class' => $c['class'], 'cLevel' => $c['level'], 'Experience' => $c['experience'],
            COLUMN_RESETS => $c['resets'], COLUMN_MASTER_RESETS => $c['masterResets'], COLUMN_RESETS_WEEK => $c['resetsWeek'], COLUMN_RESETS_MONTH => $c['resetsMonth'],
            'Money' => $c['money'], 'LevelUpPoint' => $c['points'], 'Strength' => $c['strength'], 'Dexterity' => $c['dexterity'], 'Vitality' => $c['vitality'],
            'Energy' => $c['energy'], 'Leadership' => $c['leadership'], 'PkCount' => $c['pkCount'], 'PkLevel' => $c['pkLevel'], 'PkTime' => $c['pkTime'],
            COLUMN_PK_RANKING => $c['pkCountWeb'], 'MapNumber' => $c['map'], 'MapPosX' => $c['x'], 'MapPosY' => $c['y'], 'CtlCode' => $c['ctlCode'], 'image' => $c['image'],
        ])),
        'memb_stat' => $lower(array_values(array_map(fn($a) => ['memb___id' => $a['login'], 'ConnectStat' => $a['online'] ? 1 : 0, 'ConnectTM' => $a['connectTM'],
            'IP' => $a['ip'], 'ServerName' => $a['server'], 'GameIDC' => $a['lastCharacter'] ?? null], $stat))),
        'memb_info' => $lower($bySeed('accounts', fn($a) => [
            'memb___id' => $a['login'], 'memb_name' => $a['name'], 'tel__numb' => $a['tel'], 'mail_addr' => $a['email'], 'fpas_ques' => $a['question'],
            'fpas_answ' => $a['answer'], 'sno__numb' => $a['personalId'], 'bloc_code' => $a['blocked'] ? 1 : 0, 'hashActivate' => $a['activationHash'],
            'resale' => $a['resale'] ? 1 : 0, 'memb__pwd' => $a['password'], 'data' => $a['createdAt'], 'aniversario' => $a['birthday'],
        ])),
        // webVips: só as contas que já tiveram vip
        'webvips' => $lower(array_values(array_map(fn($a) => ['username' => $a['login'], 'type' => $a['vip'], 'dateBegin' => seedTs($a['vipBegin']),
            'dateEnd' => seedTs($a['vipEnd']), 'dateEndInteger' => seedTs($a['vipEnd'])], array_filter($SEED['accounts'], fn($a) => $a['vip'] > 0 || $a['vipEnd'] !== 0)))),
        'webcash' => $bySeed('accounts', fn($a) => ['username' => $a['login'], 'amount' => $a['cash'], 'amount2' => $a['cash2'], 'points' => $a['points']]),
        'webprevilegy' => $bySeed('accounts', fn($a) => ['username' => $a['login'], 'previlegy' => $a['privilege']]),
        'accountcharacter' => $lower(array_values(array_map(fn($a) => ['Id' => $a['login'], 'GameIDC' => $a['lastCharacter']],
            array_filter($SEED['accounts'], fn($a) => isset($a['lastCharacter']))))),
        'guild' => $lower($bySeed('guilds', fn($g) => ['G_Name' => $g['name'], 'G_Master' => $g['master'], 'G_Score' => $g['score'], 'G_Mark' => "guild{$g['mark']}"])),
        'guildmember' => $lower($bySeed('guildMembers', fn($m) => ['G_Name' => $m['guild'], 'Name' => $m['name'], 'G_Level' => $m['level'], 'G_Status' => $m['status']])),
        'webbanneds' => $bySeed('bans', fn($b) => ['name' => $b['name'], 'type' => $b['type'], 'datebegin' => seedTs($b['begin']), 'dateend' => seedTs($b['end']),
            'bannedby' => $b['by'], 'description' => $b['description']]),
        // Ver Chat em tempo real (admin): mensagens cifradas como o GameServer grava (XOR + base64)
        'webchatserver' => puChatRows(),
        'sp_databases' => array_map(fn($n) => ['database_name' => $n, 'database_size' => 0, 'remarks' => null], ['master', 'model', 'msdb', 'MuOnline', 'tempdb']),
        'warehouse' => $lower($bySeed('warehouses', fn($w) => ['AccountID' => $w['account'], 'Money' => $w['money'], 'DbVersion' => $w['dbVersion'], 'Items' => hex2bin(puVaultHex())])),
        'syscolumns' => [['length' => 16 * 120]],
        'extwarehousevirtual' => puVirtualVaultRows(),
        // Ver Pedidos: assunto/descrição em base64, como o painel grava
        'webtickets' => $bySeed('tickets', fn($t) => ['id' => $t['id'], 'username' => $t['account'], 'character' => $t['character'], 'sector' => $t['sector'],
            'subject' => base64_encode($t['subject']), 'description' => base64_encode($t['description']), 'date' => seedTs($t['date']), 'ip' => $t['ip'], 'status' => $t['status']]),
        'webticketsanswers' => $bySeed('ticketAnswers', fn($r) => ['id' => $r['ticketId'], 'description' => base64_encode($r['description']), 'answerBy' => $r['answerBy'],
            'date' => seedTs($r['date'])]),
        // evento de perguntas ("Show do Milhão"): type A = fácil (prêmio da lista "A" da settings)
        'webquestions' => $bySeed('questions', fn($q) => ['number' => $q['number'], 'question' => $q['question'], 'answer1' => $q['answers'][0], 'answer2' => $q['answers'][1],
            'answer3' => $q['answers'][2], 'answer4' => $q['answers'][3], 'correct' => $q['correct'], 'type' => $q['type'], 'drawn' => 0, 'matter' => 'Jogo']),
        'webscreenshots' => $bySeed('screenshots', fn($sh) => ['id' => $sh['id'], 'image' => $sh['image'], 'uploadby' => $sh['account'], 'date' => seedTs($sh['date']),
            'rate' => $sh['rate'], 'legend' => $sh['legend'], 'sw' => $sh['sw'], 'sy' => $sh['sy'], 'cw' => $sh['cw'], 'cy' => $sh['cy']]),
        // leilões: fotos em images/auctions (placeholders)
        'webauctions' => $bySeed('auctions', fn($a) => ['id' => $a['id'], 'name' => $a['name'], 'premium' => $a['premium'], 'startdate' => seedTs($a['start']),
            'enddate' => seedTs($a['end']), 'active' => $a['active'] ? 1 : 0, 'minimumbid' => $a['minimumBid'], 'closed' => $a['closed'] ? 1 : 0,
            'winner' => $a['winner'], 'winnertext' => $a['winnerText']]),
        'webauctionsbids' => $bySeed('bids', fn($b) => ['auction' => $b['auction'], 'username' => $b['account'], 'amount' => $b['amount'],
            'lastbid' => implode('-', array_reverse(explode('/', $b['lastBidDay']))) . ' ' . $b['lastBidHour'], 'lastbidday' => $b['lastBidDay'], 'lastbidhour' => $b['lastBidHour']]),
        // Golden Archer: seriais (4E6C-9713-01F7 é o que o evento de perguntas dá como prêmio — Serpent Sword +5)
        'webgoldenarcher' => $bySeed('goldenArcher', fn($g) => puGaRow($g['id'], $g['account'], $g['serial'], $g['status'], $g['item']['cat'], $g['item']['idx'],
            $g['item']['level'], $g['item']['option'], (int)$g['item']['skill'], (int)$g['item']['luck'])),
        'webcomplaints' => $bySeed('complaints', fn($c) => ['id' => $c['id'], 'username' => $c['account'], 'image' => "modules/uploads/complaints/{$c['image']}",
            'description' => base64_encode($c['description']), 'date' => seedTs($c['date']), 'ip' => $c['ip'], 'status' => $c['status']]),
        'weblogbuycash' => $bySeed('deposits', fn($d) => puDeposit($d['id'], $d['account'], $d['cash'], $d['bank'], $d['fields'], $d['date'], $d['hour'], $d['paidAt'],
            $d['attachment'] === '' ? '' : "modules/uploads/confirmBuys/{$d['attachment']}", $d['comment'], $d['amount'], $d['adminComment'], $d['status'])),
        'weblogbuyvips' => $bySeed('vipPurchases', fn($v) => ['id' => $v['id'], 'username' => $v['account'], 'type' => $v['type'], 'days' => $v['days'],
            'cashamount' => $v['cashAmount'], 'date' => seedTs($v['date'])]),
        // notícias: conteúdo em base64, como o painel grava
        'webnotices' => $bySeed('notices', fn($n) => ['id' => $n['id'], 'subject' => $n['subject'], 'content' => base64_encode($n['content']), 'date' => seedTs($n['date'])]),
        'webpollquestions' => $bySeed('polls', fn($q) => ['id' => $q['id'], 'question' => $q['question'], 'active' => $q['active'] ? 1 : 0]),
        'webpollanswers' => $bySeed('pollAnswers', fn($a) => ['idpoll' => $a['idPoll'], 'idanswer' => $a['idAnswer'], 'answer' => $a['answer'], 'votes' => $a['votes']]),
    ];
}

// roda um método privado de ldPanelUser com $_GET/$_POST dados; devolve as tags que ele definiu
function puRun(string $method, array $get = [], array $post = [], array $session = [], ?callable $patchDB = null): array
{
    return puRunClass('ldPanelUser', $method, [], $get, $post, $session, $patchDB);
}

// executa $class->$method(...$args) (métodos privados inclusive), com o banco falso e o diretório no espelho temporário
function puRunClass(string $class, string $method, array $args = [], array $get = [], array $post = [], array $session = [], ?callable $patchDB = null): array
{
    global $ldTpl, $PU_ACCOUNT, $PU_SITE, $PU_INCLUDE;
    puSeedDB();
    if ($patchDB) $patchDB();
    $cwd = getcwd();
    chdir($PU_SITE);   // ldItemDatabase::setDatabases("modules/", …) é relativo à raiz do site -> espelho temporário
    $inc = set_include_path($PU_INCLUDE . PATH_SEPARATOR . get_include_path());
    $prev = [$ldTpl ?? null, $_GET, $_POST];
    $ldTpl = new TplCapture();
    $_GET = $get;
    $_POST = $post;
    $_SESSION = ['LOGIN' => $PU_ACCOUNT['login']] + $session;
    ob_start();   // respostas de ajax que saem por echo
    $obj = (new ReflectionClass($class))->newInstanceWithoutConstructor();
    try {
        (new ReflectionMethod($class, $method))->invokeArgs($obj, $args);
    } catch (PuExit $e) {
        $ldTpl->tags['__exit'] = $e->getMessage();   // o que o exit() imprimiria (resposta de ajax)
    }
    $ldTpl->tags['__echo'] = ob_get_clean();
    chdir($cwd);
    set_include_path($inc);
    $tags = $ldTpl->tags;
    [$ldTpl, $_GET, $_POST] = $prev;
    return $tags;
}

// webChatServer do seed: mensagem cifrada com a chave fixa de ldPanelAdmin::getChatText() (XOR + base64)
function puChatRows(): array
{
    global $SEED;
    $hash = 'c5ad7c480a66d30ef7b3ce0d3162d3ef';
    $enc = function (string $s) use ($hash) { $o = ''; for ($i = 0; $i < strlen($s); $i++) $o .= $s[$i] ^ $hash[$i % strlen($hash)]; return base64_encode($o); };
    return array_map(fn($m) => ['id' => $m['id'], 'datesayday' => $m['day'], 'datesayhour' => $m['hour'], 'server' => $m['server'], 'type' => $m['type'],
                                'name' => $m['name'], 'message' => $enc($m['message']), 'destiny' => $m['destiny'], 'saytime' => 0], $SEED['chat']);
}

// linha de webLogBuyCash (Confirmar Pagamento do painel): campos por banco em $bank
function puDeposit(int $id, string $user, int $cash, string $banco, array $bank, string $data, string $hora, string $pagoEm, string $anexo, string $comment, string $valor, string $commentAdm, int $status): array
{
    return $bank + ['id' => $id, 'username' => $user, 'cash' => $cash, 'banco' => $banco, 'nterminal' => '', 'ntransferencia' => '', 'agencia_acolhedora' => '',
        'nsequencia' => '', 'ctr' => '', 'caixa_eletronico' => '', 'nenvelope' => '', 'ndocumento' => '', 'ncontrole' => '', 'noperador' => '', 'data' => $data,
        'hora' => $hora, 'pago_em' => $pagoEm, 'anexo' => $anexo, 'comentario' => base64_encode($comment), 'valor' => $valor,
        'comentario_adm' => $commentAdm === '' ? '' : base64_encode($commentAdm), 'status' => $status];
}

// ---------- baús de exemplo ----------
// [categoria, índice, level, option (x4), skill, luck, excelentes (6 bools), durabilidade base]
function puItem(array $d): string
{
    [$cat, $idx, $lvl, $opt, $skill, $luck, $exc, $dur] = $d;
    ldItemMake::makeHexItem($hex, $idx, $cat, 3, [
        'Level' => $lvl, 'Option' => $opt, 'Skill' => $skill, 'Luck' => $luck, 'Serial' => true, 'Durability' => $dur,
        'Excellent' => $exc, 'Ancient' => 0, 'Refine' => false, 'HarmonyType' => 0, 'HarmonyLevel' => 0, 'SocketOption' => [255, 255, 255, 255, 255],
    ]);
    return $hex;
}
function puLoadItemClasses(): void
{
    global $PU_INCLUDE, $PU_SITE;
    static $done = false;
    if ($done) return;
    $cwd = getcwd();
    chdir($PU_SITE);
    foreach (['ldItemDatabase', 'ldItemOptionsDatabase', 'ldItemMake', 'ldItemParse', 'ldVault', 'ldInventory'] as $c) require_once "$PU_INCLUDE/ldItemClass/$c.class.php";
    ldItemDatabase::setDatabases("modules/", "item.txt", "classes/ldItemClass/data/item.serialize.txt");
    ldItemDatabase::checkDatabaseExists();
    chdir($cwd);
    $done = true;
}
// baú do jogo: 120 slots de 8x15; slot = y*8 + x; vazio = 32 "F"
function puVaultHex(): string
{
    static $hex = null;
    if ($hex !== null) return $hex;
    puLoadItemClasses();
    FakeDB::$serial = 0x0003A1F0;
    $no = [false, false, false, false, false, false];
    $slots = array_fill(0, 120, str_repeat('F', 32));
    foreach ([
        0  => [0, 19, 13, 4, true, true, [true, false, true, false, true, false], 60],   // Sword of Archangel 1x4
        1  => [7, 1, 11, 3, false, true, [false, true, false, true, false, false], 60],  // Dragon Helm 2x2
        3  => [8, 1, 11, 3, false, true, $no, 60],                                     // Dragon Armor 2x3
        5  => [12, 5, 9, 2, false, true, $no, 200],                                    // Wings of Dragon 3x3
        40 => [14, 13, 0, 0, false, false, $no, 1],                                    // Jewel of Bless
        41 => [14, 14, 0, 0, false, false, $no, 1],                                    // Jewel of Soul
        42 => [14, 16, 0, 0, false, false, $no, 1],                                    // Jewel of Life
        43 => [14, 16, 0, 0, false, false, $no, 1],                                    // Jewel of Life
        44 => [12, 15, 0, 0, false, false, $no, 1],                                    // Jewel of Chaos
        48 => [6, 14, 9, 2, true, false, $no, 60],                                     // Legendary Shield 2x3
        51 => [0, 0, 7, 1, true, false, $no, 20],                                      // Kris 1x2
    ] as $slot => $d) $slots[$slot] = puItem($d);
    return $hex = implode('', $slots);
}
// baú virtual (ExtWareHouseVirtual): um item por linha
function puVirtualVaultRows(): array
{
    static $rows = null;
    if ($rows !== null) return $rows;
    puLoadItemClasses();
    FakeDB::$serial = 0x0003B010;
    $no = [false, false, false, false, false, false];
    $rows = [];
    foreach ([
        7 => [8, 26, 7, 2, false, true, $no, 60],                                      // Adamantine Armor
        6 => [5, 10, 15, 4, true, true, [true, true, false, false, true, true], 60],   // Staff of Archangel
        3 => [14, 22, 0, 0, false, false, $no, 1],                                     // Jewel of Creation
    ] as $number => $d) {
        $rows[] = ['accountid' => 'kira01', 'number' => $number, 'item' => hex2bin(puItem($d)), 'dbversion' => 3];
    }
    return $rows;
}

// ---------- módulo screenshots (gerenciador do painel: ?page=loadModule&module=screenshots&action=panelManager) ----------
// O harness já tem uma classe "screenshots" (stub da home); o módulo original entra com a classe renomeada e sem a
// instância criada no fim do arquivo.
interface InterfaceModules {}
eval('?>' . puNoExit(str_replace(['class screenshots implements', '$screenshot = new screenshots();'], ['class screenshotsModuleOriginal implements', ''],
    latin1("$ROOT/modules/classes/modules/screenshots.module.php"))));
$PU_MODULE_SOURCE = latin1("$ROOT/modules/classes/modules/screenshots.module.php");

function puRunScreenshotsPanel(array $get = [], array $post = []): array
{
    global $ldTpl, $PU_ACCOUNT, $PU_SITE;
    puSeedDB();
    $cwd = getcwd();
    chdir($PU_SITE);
    $prev = [$ldTpl ?? null, $_GET, $_POST];
    $ldTpl = new TplCapture();
    $_GET = $get + ['page' => 'loadModule', 'module' => 'screenshots', 'action' => 'panelManager'];
    $_POST = $post;
    $_SESSION = ['LOGIN' => $PU_ACCOUNT['login']];
    ob_start();
    try {
        (new ReflectionClass('screenshotsModuleOriginal'))->newInstanceWithoutConstructor()->panelManager();
    } catch (PuExit $e) {
        $ldTpl->tags['__exit'] = $e->getMessage();
    }
    $ldTpl->tags['__echo'] = ob_get_clean();
    $ldTpl->tags['__opened'] = $ldTpl->opened;
    $tags = $ldTpl->tags;
    chdir($cwd);
    [$ldTpl, $_GET, $_POST] = $prev;
    return $tags;
}

function puGaRow(int $id, string $user, array $serial, int $status, int $cat, int $idx, int $lvl, int $opt, int $skill, int $luck): array
{
    $r = ['id' => $id, 'username' => $user, 'pserial1' => $serial[0], 'pserial2' => $serial[1], 'pserial3' => $serial[2], 'status' => $status,
          'itemcategorie' => $cat, 'itemindex' => $idx, 'itemlevel' => $lvl, 'itemoption' => $opt, 'itemskill' => $skill, 'itemluck' => $luck,
          'ancient' => 0, 'refine' => 0, 'harmonytype' => 0, 'harmonylevel' => 0];
    for ($i = 1; $i <= 6; $i++) $r["excellent{$i}"] = 0;
    for ($i = 1; $i <= 5; $i++) $r["socketop{$i}"] = 255;
    return $r;
}
