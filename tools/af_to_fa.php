<?php
/**
 * Safely convert <?= af_icon('name', 'class') ?> to Font Awesome <i> tags.
 * Also injects FA CDN after app.css when missing.
 */
$map = [
    'shoe' => 'fa-shoe-prints',
    'layout-dashboard' => 'fa-chart-pie',
    'shopping-cart' => 'fa-cart-shopping',
    'clipboard-list' => 'fa-clipboard-list',
    'package' => 'fa-box',
    'tag' => 'fa-tag',
    'target' => 'fa-bullseye',
    'calendar' => 'fa-calendar-day',
    'calendar-days' => 'fa-calendar-week',
    'calendar-range' => 'fa-calendar-days',
    'user' => 'fa-user',
    'users' => 'fa-users',
    'contact' => 'fa-address-book',
    'map-pin' => 'fa-location-dot',
    'store' => 'fa-store',
    'log-out' => 'fa-right-from-bracket',
    'menu' => 'fa-bars',
    'triangle-alert' => 'fa-triangle-exclamation',
    'arrow-left' => 'fa-arrow-left',
    'arrow-right' => 'fa-arrow-right',
    'check' => 'fa-check',
    'circle-x' => 'fa-circle-xmark',
    'trophy' => 'fa-trophy',
    'line-chart' => 'fa-chart-line',
    'clock' => 'fa-clock',
    'plus' => 'fa-plus',
    'pencil' => 'fa-pen',
    'trash' => 'fa-trash',
    'download' => 'fa-download',
    'printer' => 'fa-print',
    'wallet' => 'fa-wallet',
    'list' => 'fa-list',
    'search' => 'fa-magnifying-glass',
    'filter' => 'fa-filter',
    'eye' => 'fa-eye',
    'ban' => 'fa-ban',
    'save' => 'fa-floppy-disk',
    'x' => 'fa-xmark',
    'rotate-ccw' => 'fa-rotate-left',
    'phone' => 'fa-phone',
    'power' => 'fa-power-off',
    'scan-line' => 'fa-barcode',
    'credit-card' => 'fa-credit-card',
    'banknote' => 'fa-money-bill',
    'smartphone' => 'fa-mobile-screen',
    'chevron-left' => 'fa-chevron-left',
    'chevron-right' => 'fa-chevron-right',
    'search-x' => 'fa-magnifying-glass',
];

$faCdn = '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">';

$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__DIR__) . '/app/Views'));
foreach ($it as $f) {
    if (!$f->isFile() || $f->getExtension() !== 'php') continue;
    $path = $f->getPathname();
    $c = file_get_contents($path);
    $orig = $c;

    $c = preg_replace_callback(
        "/<\?=\s*af_icon\(\s*'([^']+)'\s*(?:,\s*'([^']*)')?\s*\)\s*\?>/",
        function ($m) use ($map) {
            $fa = $map[$m[1]] ?? 'fa-circle';
            $cls = $m[2] ?? 'icon';
            $extra = '';
            if (str_contains($cls, 'icon-xl')) $extra .= ' fa-2x';
            elseif (str_contains($cls, 'icon-lg') || str_contains($cls, 'icon-auth')) $extra .= ' fa-lg';
            if (str_contains($cls, 'logo-mark')) $extra .= ' logo-mark-svg';
            if (str_contains($cls, 'pos-brand')) $extra .= ' pos-brand-ic';
            return '<i class="fa-solid ' . $fa . $extra . '" aria-hidden="true"></i>';
        },
        $c
    );

    // PHP string concat forms: af_icon('x', 'icon').' Text'
    $c = preg_replace_callback(
        "/af_icon\(\s*'([^']+)'\s*(?:,\s*'([^']*)')?\s*\)\s*\.\s*'([^']*)'/",
        function ($m) use ($map) {
            $fa = $map[$m[1]] ?? 'fa-circle';
            return "'<i class=\"fa-solid {$fa}\" aria-hidden=\"true\"></i>{$m[3]}'";
        },
        $c
    );

    // json_encode(af_icon(...)) in JS
    $c = preg_replace_callback(
        "/json_encode\(\s*af_icon\(\s*'([^']+)'\s*(?:,\s*'([^']*)')?\s*\)\s*\)/",
        function ($m) use ($map) {
            $fa = $map[$m[1]] ?? 'fa-circle';
            $html = '<i class="fa-solid ' . $fa . '" aria-hidden="true"></i>';
            return json_encode($html);
        },
        $c
    );

    if (str_contains($c, 'assets/css/app.css') && !str_contains($c, 'font-awesome') && (str_contains($c, 'fa-solid') || str_contains($orig, 'af_icon'))) {
        $c = preg_replace(
            '/(<link rel="stylesheet" href="<?= BASE_PATH \?>\/assets\/css\/app\.css">)/',
            "$1\n  $faCdn",
            $c,
            1
        );
    }

    // Replace favicon helper if present with static SVG (keep af_favicon if function exists - leave it)
    // Actually keep af_favicon_data_uri - icon_helper still loaded

    if ($c !== $orig) {
        file_put_contents($path, $c);
        echo 'ok ' . basename(dirname($path)) . '/' . basename($path) . "\n";
    }
}
echo "done\n";
