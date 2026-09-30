<?php
/*
 * Dados de exemplo do kit: os MESMOS do app (src/data/seed.ts), lidos de seed.json — gerado por `bun run kit:seed`.
 * Aqui ficam as consultas das páginas públicas montadas à mão em render.php, com as regras das classes originais
 * (filtros, ORDER BY, JOINs); o painel lê o mesmo seed pelo banco falso (pumock.php: puSeedDB).
 * Consultas sem ORDER BY saem na ordem da tabela (a do seed); empates de ORDER BY também (usort é estável).
 */

$SEED_FILE = __DIR__ . '/seed.json';
if (!is_file($SEED_FILE)) exit("seed.json não encontrado: rode `bun run kit:seed` em sunonline-static\n");
if (filemtime($SEED_FILE) < filemtime(dirname(__DIR__, 2) . '/src/data/seed.ts')) {
    fwrite(STDERR, "AVISO: seed.json é mais antigo que src/data/seed.ts — rode `bun run kit:seed`\n");
}
$SEED = json_decode(file_get_contents($SEED_FILE), true, 512, JSON_THROW_ON_ERROR);

// datas do seed: "AAAA-MM-DD HH:MM:SS" (horário local de quem gerou) -> timestamp; 0 = sem data
function seedTs($v): int
{
    return is_string($v) ? strtotime($v) : (int)$v;
}
// nomes e logins no SQL Server: comparação sem diferenciar maiúsculas
function seedSame(string $a, string $b): bool
{
    return strcasecmp($a, $b) === 0;
}
function seedFind(string $table, string $key, string $value): ?array
{
    global $SEED;
    foreach ($SEED[$table] as $row) if (seedSame((string)$row[$key], $value)) return $row;
    return null;
}
function seedAccount(string $login): ?array { return seedFind('accounts', 'login', $login); }
function seedCharacter(string $name): ?array { return seedFind('characters', 'name', $name); }
// webVips.type da conta (sem conferir a validade, como o original)
function seedAccountVip(string $login): int { return (int)(seedAccount($login)['vip'] ?? 0); }

// ---------- rankings (ldRankings; tabelas do cron = Character sem a equipe, CtlCode < 8) ----------
// ORDER BY de cada gerateRanking*: [colunas desc]
function seedRanking(int $type, int $period, int $top): array
{
    global $SEED;
    $keys = match ($type) {
        1 => [['resets', 'level'], ['resetsWeek', 'resets', 'level'], ['resetsMonth', 'resets', 'level']][$period],
        2 => ['level', 'resets'], 4 => ['pkCountWeb', 'resets'], 5 => ['masterResets', 'resets'],
    };
    $rows = array_values(array_filter($SEED['characters'], fn($c) => $c['ctlCode'] < 8));
    usort($rows, function ($a, $b) use ($keys) {
        foreach ($keys as $k) if ($a[$k] != $b[$k]) return $b[$k] <=> $a[$k];
        return 0;
    });
    return array_slice($rows, 0, $top);
}
// ORDER BY G_Score desc
function seedGuildRanking(int $top): array
{
    global $SEED;
    $rows = $SEED['guilds'];
    usort($rows, fn($a, $b) => $b['score'] <=> $a['score']);
    return array_slice($rows, 0, $top);
}
function seedGuildMembers(string $guild): array
{
    global $SEED;
    return array_values(array_filter($SEED['guildMembers'], fn($m) => seedSame($m['guild'], $guild)));
}
function seedGuildOf(string $name): ?string { return seedFind('guildMembers', 'name', $name)['guild'] ?? null; }
function seedMemberWithStatus(string $guild, int $status): string
{
    foreach (seedGuildMembers($guild) as $m) if ($m['status'] == $status) return $m['name'];
    return '';
}
// Gens_Rank (X-Team): WHERE Family = N ORDER BY Contribution DESC, Name ASC
function seedGens(int $family, int $top): array
{
    global $SEED;
    $rows = array_values(array_filter($SEED['gens'], fn($g) => $g['family'] == $family));
    usort($rows, fn($a, $b) => ($b['contribution'] <=> $a['contribution']) ?: strcasecmp($a['name'], $b['name']));
    return array_slice($rows, 0, $top);
}

// ---------- ldOnlines: MEMB_STAT (ConnectStat = 1) JOIN AccountCharacter (GameIDC) JOIN Character ----------
function seedOnlines(string $room = ''): array
{
    global $SEED;
    $out = [];
    foreach ($SEED['accounts'] as $a) {
        if (!$a['online'] || (!empty($room) && $room != 'all' && !seedSame($a['server'], $room))) continue;
        $c = isset($a['lastCharacter']) ? seedCharacter($a['lastCharacter']) : null;
        if ($c) $out[] = ['character' => $a['lastCharacter'], 'connectTM' => $a['connectTM'], 'resets' => $c['resets'], 'vip' => $a['vip'], 'server' => $a['server']];
    }
    return $out;
}

// ---------- ldBanned: SELECT * FROM webBanneds WHERE type = X ORDER BY name DESC ----------
function seedBans(int $type): array
{
    global $SEED;
    $rows = array_values(array_filter($SEED['bans'], fn($b) => $b['type'] == $type));
    usort($rows, fn($a, $b) => strcasecmp($b['name'], $a['name']));
    return $rows;
}

// ---------- ldAjax ----------
// membersstaff: Character (CtlCode 8 ou 32) JOIN MEMB_STAT
function seedStaff(): array
{
    global $SEED;
    $out = [];
    foreach ($SEED['characters'] as $c) {
        if ($c['ctlCode'] != 8 && $c['ctlCode'] != 32) continue;
        $a = seedAccount($c['account']);
        if ($a) $out[] = [$c['name'], $a['online'] ? 1 : 0];
    }
    return $out;
}
// chatrealtime: TOP 50 … WHERE type IN ($CHAT_REAL_TIME['TYPES']) ORDER BY id DESC
function seedChat(int $top): array
{
    global $SEED, $CHAT_REAL_TIME;
    $rows = array_values(array_filter($SEED['chat'], fn($m) => in_array($m['type'], $CHAT_REAL_TIME['TYPES'])));
    usort($rows, fn($a, $b) => $b['id'] <=> $a['id']);
    return array_slice($rows, 0, $top);
}
// enquete da lateral (ldGeneral::loadPoll): a ativa mais nova (WHERE active = 1 ORDER BY id DESC); idAnswer => [resposta, votos]
function seedPoll(): array
{
    global $SEED;
    $polls = array_values(array_filter($SEED['polls'], fn($p) => $p['active']));
    usort($polls, fn($a, $b) => $b['id'] <=> $a['id']);
    $poll = $polls[0];
    $answers = [];
    foreach ($SEED['pollAnswers'] as $a) if ($a['idPoll'] == $poll['id']) $answers[$a['idAnswer']] = [$a['answer'], $a['votes']];
    return [$poll, $answers];
}

// ---------- ldGeneral::__construct: contadores ----------
function seedCounters(): array
{
    global $SEED;
    $vips = [];
    for ($t = 1; $t <= 5; $t++) $vips[$t] = count(array_filter($SEED['accounts'], fn($a) => $a['vip'] == $t));
    return [
        'accounts' => count($SEED['accounts']), 'characters' => count($SEED['characters']), 'vips' => $vips,
        'bannedAccounts' => count(array_filter($SEED['accounts'], fn($a) => $a['blocked'] && $a['activationHash'] === null)),
        'bannedCharacters' => count(array_filter($SEED['characters'], fn($c) => $c['ctlCode'] == 1)),
        'online' => count(array_filter($SEED['accounts'], fn($a) => $a['online'])),
        'record' => $SEED['record']['online'],
    ];
}

// ---------- notícias (webNotices: ORDER BY date DESC) ----------
function seedNotices(): array
{
    global $SEED;
    $rows = $SEED['notices'];
    usort($rows, fn($a, $b) => seedTs($b['date']) <=> seedTs($a['date']));
    return $rows;
}

// ---------- módulo screenshots (webScreenshots: ORDER BY rate DESC) ----------
function seedScreenshotsRanked(): array
{
    global $SEED;
    $rows = $SEED['screenshots'];
    usort($rows, fn($a, $b) => $b['rate'] <=> $a['rate']);
    return $rows;
}
// quem enviou: AccountCharacter.GameIDC de uploadBy; sem ele (ou com menos de 2 letras), o login
function seedScreenshotAuthor(array $s): string
{
    $name = seedAccount($s['account'])['lastCharacter'] ?? '';
    return empty($name) || strlen($name) < 2 ? $s['account'] : $name;
}
