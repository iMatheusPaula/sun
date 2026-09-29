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

// ---------- dados de exemplo (conta kira01, a mesma de paneluser.php) ----------
function puSeedDB(): void
{
    global $PU_ACCOUNT, $CLASS_CHARACTERS;
    $a = $PU_ACCOUNT;
    $cc = $CLASS_CHARACTERS['CLASSCODES'];
    $char = fn(array $f) => array_change_key_case($f + [
        'AccountID' => $a['login'], 'Experience' => 0, 'LevelUpPoint' => 0, 'MapNumber' => 0, 'MapPosX' => 125, 'MapPosY' => 125,
        'CtlCode' => 0, 'image' => '', 'PkLevel' => 3, 'PkTime' => 0, 'PkCountWeb' => 0, COLUMN_RESETS_WEEK => 0, COLUMN_RESETS_MONTH => 0,
    ]);
    FakeDB::$tables = [
        'character' => [
            $char(['Name' => 'Kira', 'Class' => $cc['BK'][0], 'cLevel' => 400, COLUMN_RESETS => 398, COLUMN_MASTER_RESETS => 5, 'Money' => 185000000,
                   'Strength' => 32767, 'Dexterity' => 18000, 'Vitality' => 25000, 'Energy' => 3000, 'Leadership' => 0, 'PkCount' => 640, 'PkLevel' => 6,
                   'LevelUpPoint' => 1200, COLUMN_RESETS_WEEK => 19, COLUMN_RESETS_MONTH => 70]),
            $char(['Name' => 'Kirinha', 'Class' => $cc['FE'][0], 'cLevel' => 180, COLUMN_RESETS => 12, COLUMN_MASTER_RESETS => 0, 'Money' => 2500000,
                   'Strength' => 1200, 'Dexterity' => 3500, 'Vitality' => 900, 'Energy' => 800, 'Leadership' => 0, 'PkCount' => 0,
                   'MapNumber' => 3, 'MapPosX' => 174, 'MapPosY' => 111]),
            $char(['Name' => 'KiraMG', 'Class' => $cc['MG'][0], 'cLevel' => 95, COLUMN_RESETS => 3, COLUMN_MASTER_RESETS => 0, 'Money' => 40000,
                   'Strength' => 400, 'Dexterity' => 300, 'Vitality' => 250, 'Energy' => 300, 'Leadership' => 0, 'PkCount' => 2, 'CtlCode' => 1]),
        ],
        'memb_stat' => [array_change_key_case(['memb___id' => $a['login'], 'ConnectStat' => 0, 'ConnectTM' => $a['connectTM'], 'IP' => $a['ip'], 'ServerName' => $a['server']]),
                        // online agora (ferramentas do jogo do admin); GameIDC vem do JOIN com AccountCharacter
                        array_change_key_case(['memb___id' => 'zeus', 'ConnectStat' => 1, 'ConnectTM' => 'Sep 23 2026 8:02PM', 'IP' => '177.8.140.3', 'ServerName' => 'Server 1', 'GameIDC' => 'Zeus']),
                        array_change_key_case(['memb___id' => 'elfa', 'ConnectStat' => 1, 'ConnectTM' => 'Sep 23 2026 8:15PM', 'IP' => '189.44.12.7', 'ServerName' => 'Server 2', 'GameIDC' => 'Elfa']),
                        array_change_key_case(['memb___id' => 'mago', 'ConnectStat' => 1, 'ConnectTM' => 'Sep 23 2026 8:31PM', 'IP' => '200.18.4.99', 'ServerName' => 'Server 1', 'GameIDC' => 'Mago'])],
        'memb_info' => [array_change_key_case(['memb___id' => $a['login'], 'memb_name' => $a['name'], 'tel__numb' => $a['tel'], 'mail_addr' => $a['mail'],
                        'fpas_ques' => $a['question'], 'fpas_answ' => 'Rex', 'bloc_code' => 0, 'hashActivate' => null, 'resale' => 0, 'memb__pwd' => '123456',
                        'data' => '14/03/2025 20:12', 'aniversario' => '9/7/1998']),
                        // outras contas (painel do admin): liberadas para transferir SGolds (resale = 1)
                        array_change_key_case(['memb___id' => 'zeus', 'memb_name' => 'Rafael', 'tel__numb' => '', 'mail_addr' => 'zeus@example.com', 'fpas_ques' => '', 'fpas_answ' => '',
                                               'bloc_code' => 0, 'hashActivate' => null, 'resale' => 1, 'memb__pwd' => '', 'data' => '', 'aniversario' => '']),
                        array_change_key_case(['memb___id' => 'elfa', 'memb_name' => 'Juliana', 'tel__numb' => '', 'mail_addr' => 'elfa@example.com', 'fpas_ques' => '', 'fpas_answ' => '',
                                               'bloc_code' => 0, 'hashActivate' => null, 'resale' => 1, 'memb__pwd' => '', 'data' => '', 'aniversario' => ''])],
        // início = compra de 30 dias no Log de Compras (23/09/2026 20:15)
        'webvips' => [array_change_key_case(['username' => $a['login'], 'type' => $a['vip'], 'dateBegin' => $a['vipEnd'] - 30 * 86400, 'dateEnd' => $a['vipEnd'], 'dateEndInteger' => $a['vipEnd']]),
                      // vencidos (Sincronizar VIPs do admin)
                      array_change_key_case(['username' => 'zeus', 'type' => 1, 'dateEnd' => mktime(20, 0, 0, 9, 10, 2026), 'dateEndInteger' => mktime(20, 0, 0, 9, 10, 2026)]),
                      array_change_key_case(['username' => 'elfa', 'type' => 2, 'dateEnd' => mktime(13, 30, 0, 9, 25, 2026), 'dateEndInteger' => mktime(13, 30, 0, 9, 25, 2026)])],
        // banimentos (Sincronizar banidos do admin): 2 vigentes (os da página pública) e 2 vencidos
        'webbanneds' => [
            ['name' => 'vendedor01', 'type' => 1, 'datebegin' => mktime(0, 0, 0, 9, 1, 2026), 'dateend' => mktime(0, 0, 0, 12, 31, 2030), 'bannedby' => 'Admin', 'description' => 'Venda de conta'],
            ['name' => 'spam_bot', 'type' => 1, 'datebegin' => mktime(10, 0, 0, 8, 25, 2026), 'dateend' => mktime(10, 0, 0, 9, 1, 2026), 'bannedby' => 'GM_Kira', 'description' => 'Spam'],
            ['name' => 'H4ck3r', 'type' => 2, 'datebegin' => mktime(0, 0, 0, 9, 1, 2026), 'dateend' => mktime(0, 0, 0, 12, 31, 2030), 'bannedby' => 'Admin', 'description' => 'Uso de hack'],
            ['name' => 'Ladrao', 'type' => 2, 'datebegin' => mktime(18, 0, 0, 9, 8, 2026), 'dateend' => mktime(18, 0, 0, 9, 15, 2026), 'bannedby' => 'GM_Zeus', 'description' => 'Golpe em trade'],
        ],
        'webcash' => [array_change_key_case(['username' => $a['login'], 'amount' => $a['cash'], 'amount2' => $a['cash2'], 'points' => $a['points']]),
                      ['username' => 'zeus', 'amount' => 800, 'amount2' => 0, 'points' => 210], ['username' => 'elfa', 'amount' => 150, 'amount2' => 40, 'points' => 320]],
        // Ver Chat em tempo real (admin): mensagens cifradas como o GameServer grava (XOR + base64)
        'webchatserver' => puChatRows(),
        'sp_databases' => array_map(fn($n) => ['database_name' => $n, 'database_size' => 0, 'remarks' => null], ['master', 'model', 'msdb', 'MuOnline', 'tempdb']),
        'accountcharacter' => [array_change_key_case(['Id' => $a['login'], 'GameIDC' => 'Kira'])],
        'warehouse' => [array_change_key_case(['AccountID' => $a['login'], 'Money' => 12000000, 'DbVersion' => 3, 'Items' => hex2bin(puVaultHex())])],
        'syscolumns' => [['length' => 16 * 120]],
        'extwarehousevirtual' => puVirtualVaultRows(),
        // Ver Pedidos: assunto/descrição em base64, como o painel grava
        'webtickets' => [
            ['id' => 14, 'username' => $a['login'], 'character' => 'Kira', 'sector' => 'Suporte', 'subject' => base64_encode('Personagem preso após o reset'),
             'description' => base64_encode("Depois do reset o Kira ficou preso numa parede em Lorencia.\nJá tentei mover pelo painel e não resolveu."),
             'date' => mktime(19, 5, 0, 9, 21, 2026), 'ip' => $a['ip'], 'status' => 0],
            ['id' => 9, 'username' => $a['login'], 'character' => 'Kirinha', 'sector' => 'Financeiro', 'subject' => base64_encode('Doação não creditada'),
             'description' => base64_encode('Fiz o depósito ontem e os SGolds ainda não entraram.'),
             'date' => mktime(10, 40, 0, 9, 2, 2026), 'ip' => $a['ip'], 'status' => 1],
            // de outras contas (aparecem só no painel do GM/admin)
            ['id' => 15, 'username' => 'zeus', 'character' => 'Zeus', 'sector' => 'Suporte', 'subject' => base64_encode('Item sumiu do baú'),
             'description' => base64_encode('Meu Dragon Helm +11 sumiu do baú depois da manutenção.'), 'date' => mktime(9, 12, 0, 9, 23, 2026), 'ip' => '177.8.140.3', 'status' => 0],
            ['id' => 12, 'username' => 'elfa', 'character' => 'Elfa', 'sector' => 'Financeiro', 'subject' => base64_encode('VIP não ativou'),
             'description' => base64_encode('Comprei o Vip Gold e a conta continua Free.'), 'date' => mktime(16, 45, 0, 9, 12, 2026), 'ip' => '189.44.12.7', 'status' => 1],
        ],
        // evento de perguntas ("Show do Milhão"): type A = fácil (prêmio da lista "A" da settings)
        'webquestions' => [['number' => 1, 'question' => 'Qual é o level máximo de um personagem no MU Online?', 'answer1' => '350', 'answer2' => '400',
                            'answer3' => '450', 'answer4' => '500', 'correct' => 2, 'type' => 'A', 'drawn' => 0, 'matter' => 'Jogo']],
        // screenshots enviadas pela conta (gerenciador do painel) e privilégio (0 = usuário comum)
        'webscreenshots' => [
            ['id' => 3, 'image' => 'a1b2c3', 'uploadby' => $a['login'], 'date' => mktime(21, 10, 0, 9, 14, 2026), 'rate' => 12, 'legend' => 'Castle Siege de domingo', 'sw' => 90, 'sy' => 68, 'cw' => 640, 'cy' => 480],
            ['id' => 5, 'image' => 'd4e5f6', 'uploadby' => $a['login'], 'date' => mktime(18, 2, 0, 9, 18, 2026), 'rate' => 4, 'legend' => 'Kira de set novo', 'sw' => 90, 'sy' => 68, 'cw' => 640, 'cy' => 480],
            ['id' => 7, 'image' => 'a7b8c9', 'uploadby' => $a['login'], 'date' => mktime(22, 40, 0, 9, 20, 2026), 'rate' => 0, 'legend' => 'Evento Blood Castle', 'sw' => 90, 'sy' => 68, 'cw' => 640, 'cy' => 480],
        ],
        'webprevilegy' => [['username' => $a['login'], 'previlegy' => 0]],
        // leilões: um por status (datas fixas: "em andamento" vale até 2030); fotos em images/auctions (placeholders)
        'webauctions' => [
            ['id' => 4, 'name' => 'Leilão de Wings', 'premium' => 'Wings of Satan +11 +16 +Luck, excelente (Absorve 5% do dano)', 'startdate' => mktime(20, 0, 0, 9, 1, 2026), 'enddate' => mktime(20, 0, 0, 12, 31, 2030), 'active' => 1, 'minimumbid' => 50, 'closed' => 0, 'winner' => '', 'winnertext' => ''],
            ['id' => 3, 'name' => 'Set Dragon completo', 'premium' => 'Dragon Helm, Armor, Pants, Gloves e Boots +9 +8', 'startdate' => mktime(20, 0, 0, 1, 10, 2030), 'enddate' => mktime(20, 0, 0, 1, 17, 2030), 'active' => 1, 'minimumbid' => 80, 'closed' => 0, 'winner' => '', 'winnertext' => ''],
            ['id' => 2, 'name' => 'Espada do Arcanjo', 'premium' => 'Sword of Archangel +13 +16 +Luck +Skill', 'startdate' => mktime(20, 0, 0, 9, 5, 2026), 'enddate' => mktime(20, 0, 0, 9, 20, 2026), 'active' => 1, 'minimumbid' => 70, 'closed' => 0, 'winner' => '', 'winnertext' => ''],
            ['id' => 1, 'name' => 'Jóias da semana', 'premium' => '10 Jewel of Bless + 10 Jewel of Soul', 'startdate' => mktime(20, 0, 0, 8, 20, 2026), 'enddate' => mktime(20, 0, 0, 8, 27, 2026), 'active' => 1, 'minimumbid' => 20, 'closed' => 1, 'winner' => $a['login'], 'winnertext' => 'As jóias foram entregues no baú da sua conta.'],
        ],
        'webauctionsbids' => [
            ['auction' => 4, 'username' => 'zeus', 'amount' => 75, 'lastbid' => '2026-09-22 21:14:05', 'lastbidday' => '22/09/2026', 'lastbidhour' => '21:14:05'],
            ['auction' => 4, 'username' => $a['login'], 'amount' => 60, 'lastbid' => '2026-09-21 19:02:44', 'lastbidday' => '21/09/2026', 'lastbidhour' => '19:02:44'],
            ['auction' => 2, 'username' => 'elfa', 'amount' => 140, 'lastbid' => '2026-09-19 23:50:10', 'lastbidday' => '19/09/2026', 'lastbidhour' => '23:50:10'],
            ['auction' => 1, 'username' => $a['login'], 'amount' => 45, 'lastbid' => '2026-08-26 18:31:00', 'lastbidday' => '26/08/2026', 'lastbidhour' => '18:31:00'],
        ],
        // Golden Archer: seriais (4E6C-9713-01F7 é o que o evento de perguntas dá como prêmio — Serpent Sword +5)
        'webgoldenarcher' => [
            puGaRow(1, $a['login'], ['4E6C', '9713', '01F7'], 0, 0, 8, 5, 3, 0, 1),
            puGaRow(2, $a['login'], ['7F21', 'B0C4', '9E3A'], 1, 14, 13, 0, 0, 0, 0),   // já registrado
            puGaRow(3, 'zeus', ['55D0', '1A2B', 'C3E9'], 0, 12, 15, 0, 0, 0, 0),         // de outra conta
        ],
        'webcomplaints' => [
            ['id' => 2, 'username' => 'elfa', 'image' => 'modules/uploads/complaints/elfa[21-09-2026~20-14-02].jpg', 'description' => base64_encode("Jogador usando speed hack em Lost Tower.\nPersonagem: H4ck3r."), 'date' => mktime(20, 14, 2, 9, 21, 2026), 'ip' => '189.44.12.7', 'status' => 0],
            ['id' => 1, 'username' => $a['login'], 'image' => 'modules/uploads/complaints/kira01[18-09-2026~22-30-10].jpg', 'description' => base64_encode('Flood no chat global o dia todo.'), 'date' => mktime(22, 30, 10, 9, 18, 2026), 'ip' => $a['ip'], 'status' => 0],
        ],
        // depósitos (painel do admin): os 3 do Log de Compras da kira01 + 1 da zeus sem comprovante e com valor zerado
        'weblogbuycash' => [
            puDeposit(32, 'zeus', 300, 'Loterica', ['ncontrole' => '778899', 'nterminal' => '12'], '23/09/2026', '10:05', 'Atendente', '', 'Paguei na lotérica perto de casa.', 'R$ 0,00', '', 0),
            puDeposit(31, $a['login'], 500, 'Banco Bradesco ou Banco Postal', ['nterminal' => '004512', 'ntransferencia' => '000123', 'agencia_acolhedora' => '1234', 'nsequencia' => '0098'],
                      '20/09/2026', '14:32', 'Auto Atendimento', 'modules/uploads/confirmBuys/kira01[20-09-2026~14-32-05].jpg', "Depósito no banco.\nComprovante número 000123.", 'R$ 50,00', '', 0),
            puDeposit(27, $a['login'], 1000, 'Banco Itau', ['ctr' => '5566', 'caixa_eletronico' => '123456/1234'],
                      '02/09/2026', '09:10', 'Trans. Eletrônica', 'modules/uploads/confirmBuys/kira01[02-09-2026~09-12-40].jpg', 'Transferência feita pelo app.', 'R$ 100,00', 'Pagamento confirmado.', 1),
            puDeposit(19, $a['login'], 200, 'Loterica', ['ncontrole' => '445566', 'nterminal' => '07'],
                      '15/08/2026', '21:47', 'Atendente', 'modules/uploads/confirmBuys/kira01[15-08-2026~21-50-13].jpg', 'Depósito em lotérica.', 'R$ 20,00', 'Comprovante ilegível, envie de novo.', 2),
        ],
        // notícias: as 5 da home (ids 100..96); conteúdo em base64, como o painel grava
        'webnotices' => array_map(fn($i, $n) => ['id' => 100 - $i, 'subject' => $n[0], 'content' => base64_encode($i === 0 ? sampleNoticeContent() : 'Confira os detalhes no fórum.'), 'date' => $n[1]],
                                  array_keys(sampleNotices()), sampleNotices()),
        // enquetes: a da home (id 1, ativa) e uma inativa
        'webpollquestions' => [
            ['id' => 2, 'question' => 'Qual o melhor horário para eventos?', 'active' => 0],
            ['id' => 1, 'question' => 'Qual evento você quer no fim de semana?', 'active' => 1],
        ],
        'webpollanswers' => array_merge(
            array_map(fn($id, $v) => ['idpoll' => 1, 'idanswer' => $id, 'answer' => $v[0], 'votes' => $v[1]], array_keys($GLOBALS['POLL_SAMPLE']), $GLOBALS['POLL_SAMPLE']),
            [['idpoll' => 2, 'idanswer' => 4, 'answer' => 'Manhã', 'votes' => 3], ['idpoll' => 2, 'idanswer' => 5, 'answer' => 'Tarde', 'votes' => 9], ['idpoll' => 2, 'idanswer' => 6, 'answer' => 'Noite', 'votes' => 22]]),
        'webticketsanswers' => [
            ['id' => 14, 'description' => base64_encode("Olá! Movemos o personagem para Lorencia.\nConfira e responda se continuar.") , 'answerBy' => 'Suporte', 'date' => mktime(21, 30, 0, 9, 21, 2026)],
            ['id' => 14, 'description' => base64_encode('Ainda aparece preso quando entro no jogo.'), 'answerBy' => 'Player', 'date' => mktime(8, 12, 0, 9, 22, 2026)],
        ],
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

// webChatServer: [id, dia, hora, server, tipo (0 chat … 8 post), nome, mensagem, destino]; mais novo primeiro no ORDER BY id DESC
function puChatRows(): array
{
    $hash = 'c5ad7c480a66d30ef7b3ce0d3162d3ef';   // chave fixa de ldPanelAdmin::getChatText()
    $enc = function (string $s) use ($hash) { $o = ''; for ($i = 0; $i < strlen($s); $i++) $o .= $s[$i] ^ $hash[$i % strlen($hash)]; return base64_encode($o); };
    $rows = [
        [1, '20:41:05', 0, 'DarkLord', 'alguém pra BC 7?', ''], [2, '20:42:18', 2, 'Kira', 'reunião da guild às 21h', ''],
        [3, '20:43:02', 8, 'Zeus', 'vendo Bone Blade +13 @ Lorencia', ''], [4, '20:44:40', 1, 'Elfa', 'bora Kalima 6', ''],
        [5, '20:45:11', 0, 'Mago', 'gg', ''], [6, '20:46:30', 5, 'Kira', 'passa o set depois?', 'Zeus'],
        [7, '20:47:52', 4, 'Kira', 'Legends: CS domingo, todos online', ''], [8, '20:48:09', 6, 'Zeus', 'aliança fechada com a Legends', ''],
        [9, '20:50:00', 3, 'Admin', 'Castle Siege começa em 10 minutos!', ''], [10, '20:51:33', 7, 'GM_Kira', 'Evento de drop no Lorencia agora', ''],
    ];
    return array_map(fn($r) => ['id' => $r[0], 'datesayday' => '23/09/2026', 'datesayhour' => $r[1], 'server' => 'Server 1', 'type' => $r[2],
                                'name' => $r[3], 'message' => $enc($r[4]), 'destiny' => $r[5], 'saytime' => 0], $rows);
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
