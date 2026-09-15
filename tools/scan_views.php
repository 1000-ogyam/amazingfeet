<?php
$root = dirname(__DIR__) . '/app/Views';
$emoji = '/[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}]/u';
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
foreach ($it as $f) {
    if (!$f->isFile() || $f->getExtension() !== 'php') continue;
    $c = file_get_contents($f->getPathname());
    $rel = str_replace('\\', '/', substr($f->getPathname(), strlen(dirname(__DIR__)) + 1));
    $hasEmoji = preg_match_all($emoji, $c, $m);
    $hasArrow = str_contains($c, '→') || str_contains($c, '←') || str_contains($c, '✓') || str_contains($c, '✗') || str_contains($c, '☰') || str_contains($c, '⏻');
    if ($hasEmoji || $hasArrow) {
        echo $rel;
        if ($hasEmoji) echo ' EMOJI[' . implode(' ', array_unique($m[0])) . ']';
        if ($hasArrow) echo ' SYMBOLS';
        echo "\n";
    }
}
echo "DONE\n";
