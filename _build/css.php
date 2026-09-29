<?php
/*
 * css/common.css = _build/common.header.css (bloco do kit) + common.css original (ISO-8859-1 -> UTF-8) podado:
 * cada seletor é testado contra todas as páginas geradas (Dom\HTMLDocument::querySelector, PHP 8.4+), com as respostas
 * pré-renderizadas em <template> injetadas no alvo onde o main.js as coloca. Seletor sem nenhuma página sai; regra sem
 * seletor sai; comentário de seção sem regra e regra comentada (código morto) saem. O resto fica como no original.
 * Roda só no build completo (a poda depende de todas as páginas).
 */

// alvo de cada <template> (A17 enquete, A19 login, A24 evento de perguntas, A25 Golden Archer; A23 usa data-kit-target)
const CSS_TEMPLATE_TARGETS = ['loginError' => 'ResultAjaxLogin', 'loginFill' => 'ResultAjaxLogin', 'pollVote' => 'pollResult',
    'pollInvalid' => 'pollResult', 'pollResults' => 'pollResult', 'qe-' => 'reponseSubmit', 'ga-' => 'serialResponse'];

function cssPageDocs(string $dir): array
{
    $docs = [];
    foreach (glob("$dir/*.html") as $f) {
        $doc = Dom\HTMLDocument::createFromFile($f, LIBXML_NOERROR);
        foreach (iterator_to_array($doc->querySelectorAll('template')) as $t) {
            $id = $t->getAttribute('id');
            $trigger = $doc->querySelector('[data-kit-tpl="' . $id . '"]');
            $target = $trigger ? $doc->getElementById($trigger->getAttribute('data-kit-target')) : null;
            foreach (CSS_TEMPLATE_TARGETS as $prefix => $tid) if (!$target && str_starts_with($id, $prefix)) $target = $doc->getElementById($tid);
            if (!$target) exit("css.php: <template id=\"{$id}\"> sem alvo em " . basename($f) . "\n");
            $target->innerHTML = $target->innerHTML . $t->innerHTML;   // o conteúdo do <template> não é descendente no DOM
        }
        $docs[basename($f)] = $doc;
    }
    return $docs;
}

// [tipo, texto] — tipos: ws, comment, at, rule (texto do seletor + bloco em [2])
function cssItems(string $css): array
{
    $items = [];
    $n = strlen($css);
    $skip = function (int $j) use ($css) { $e = strpos($css, '*/', $j + 2); return $e === false ? strlen($css) : $e + 2; };
    for ($i = 0; $i < $n;) {
        if (ctype_space($css[$i])) {
            $j = $i; while ($j < $n && ctype_space($css[$j])) $j++;
            $items[] = ['ws', substr($css, $i, $j - $i)]; $i = $j; continue;
        }
        if (substr($css, $i, 2) === '/*') { $j = $skip($i); $items[] = ['comment', substr($css, $i, $j - $i)]; $i = $j; continue; }
        // seletor (pode ter comentário no meio) até '{'; bloco até a '}' correspondente
        $j = $i;
        while ($j < $n && $css[$j] !== '{' && $css[$j] !== ';') $j = substr($css, $j, 2) === '/*' ? $skip($j) : $j + 1;
        if ($j >= $n || $css[$j] === ';') { $items[] = ['at', substr($css, $i, $j + 1 - $i)]; $i = $j + 1; continue; }   // @charset
        $k = $j; $depth = 0;
        for (; $k < $n; $k++) {
            if (substr($css, $k, 2) === '/*') { $k = $skip($k) - 1; continue; }
            if ($css[$k] === '{') $depth++;
            if ($css[$k] === '}' && --$depth === 0) break;
        }
        if ($css[$i] === '@') exit("css.php: at-rule com bloco não suportada: " . substr($css, $i, 40) . "\n");
        $items[] = ['rule', substr($css, $i, $j - $i), substr($css, $j, $k + 1 - $j)];
        $i = $k + 1;
    }
    return $items;
}

// seletor como o teste enxerga: sem pseudo-elemento nem estado do usuário (a:hover vale se existir um <a>)
function cssTestSelector(string $sel): string
{
    $s = preg_replace('~::?(before|after|first-line|first-letter|selection|-moz-[\w-]+|-webkit-[\w-]+)\b~i', '', $sel);
    $s = trim(preg_replace('~:(hover|active|focus|visited|link|focus-within|focus-visible|target)\b~i', '', $s));
    return ($s === '' || preg_match('/[>+~]$/', $s)) ? $s . '*' : $s;
}

function buildCss(string $root, string $out): void
{
    $docs = cssPageDocs($out);
    $css = mb_convert_encoding(file_get_contents("$root/templates/sunonline/_Css/common.css"), 'UTF-8', 'ISO-8859-1');
    $items = cssItems($css);
    $cache = [];
    $used = function (string $sel) use ($docs, &$cache): bool {
        $t = cssTestSelector($sel);
        if (!isset($cache[$t])) {
            $cache[$t] = false;
            try { foreach ($docs as $doc) if ($doc->querySelector($t) !== null) { $cache[$t] = true; break; } }
            catch (Throwable $e) { fwrite(STDERR, "css.php: seletor inválido (sai, o navegador também o ignora): {$sel}\n"); }
        }
        return $cache[$t];
    };

    // 1. regras: seletores que ficam
    $stats = ['rules' => 0, 'rulesKept' => 0, 'sels' => 0, 'selsKept' => 0];
    foreach ($items as $i => $it) {
        if ($it[0] !== 'rule') continue;
        $sels = array_values(array_filter(array_map('trim', explode(',', preg_replace('~/\*.*?\*/~s', '', $it[1]))), 'strlen'));
        $keep = array_values(array_filter($sels, $used));
        $stats['rules']++; $stats['sels'] += count($sels); $stats['selsKept'] += count($keep);
        if (!$keep) { $items[$i][0] = 'drop'; continue; }
        $stats['rulesKept']++;
        if (count($keep) < count($sels)) $items[$i][1] = implode(', ', array_map(fn($s) => preg_replace('~\s+~', ' ', $s), $keep)) . ' ';
    }
    // 2. comentários: o que fica na mesma linha de uma regra acompanha a regra; regra comentada (tem '{') sai;
    //    comentário de seção sai se nenhuma regra da seção (até o próximo comentário de seção) ficou
    $prevRule = null;
    foreach ($items as $i => $it) {
        if ($it[0] === 'rule' || $it[0] === 'drop') { $prevRule = $i; continue; }
        if ($it[0] !== 'comment') continue;
        $sameLine = $prevRule !== null && ($prevRule === $i - 1 || ($prevRule === $i - 2 && $items[$i - 1][0] === 'ws' && !str_contains($items[$i - 1][1], "\n")));
        if ($sameLine) { if ($items[$prevRule][0] === 'drop') $items[$i][0] = 'drop'; continue; }
        if (str_contains($it[1], '{')) { $items[$i][0] = 'drop'; continue; }
        $alive = false;
        for ($j = $i + 1; $j < count($items); $j++) {
            if ($items[$j][0] === 'comment') {
                $jSame = $items[$j - 1][0] === 'ws' && !str_contains($items[$j - 1][1], "\n");
                if (!$jSame && !str_contains($items[$j][1], '{')) break;   // próximo comentário de seção
            }
            if ($items[$j][0] === 'rule') { $alive = true; break; }
        }
        if (!$alive) $items[$i][0] = 'drop';
    }
    // 3. texto: item removido leva junto a indentação antes dele; linhas em branco repetidas viram uma
    $text = '';
    foreach ($items as $it) {
        if ($it[0] === 'drop') { $text = preg_replace('~[ \t]+$~', '', $text); continue; }
        $text .= $it[0] === 'rule' ? $it[1] . $it[2] : $it[1];
    }
    $text = preg_replace("~\n[ \t]*(?:\n[ \t]*)+\n~", "\n\n", preg_replace("~[ \t]+\n~", "\n", $text));
    file_put_contents("$out/css/common.css", file_get_contents(__DIR__ . '/common.header.css') . "\n" . ltrim($text));
    printf("css/common.css: %d de %d regras, %d de %d seletores (original podado)\n", $stats['rulesKept'], $stats['rules'], $stats['selsKept'], $stats['sels']);
}
