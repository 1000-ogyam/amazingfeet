<?php
/**
 * Convert af_icon() and common emoji patterns to Font Awesome in views.
 */
$faCdn = '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">';

$mapAf = [
    "af_icon('shoe', 'icon icon-lg logo-mark-svg')" => '<i class="fa-solid fa-shoe-prints logo-mark-svg" aria-hidden="true"></i>',
    "af_icon('layout-dashboard', 'icon')" => '<i class="fa-solid fa-chart-pie" aria-hidden="true"></i>',
    "af_icon('shopping-cart', 'icon')" => '<i class="fa-solid fa-cart-shopping" aria-hidden="true"></i>',
    "af_icon('clipboard-list', 'icon')" => '<i class="fa-solid fa-clipboard-list" aria-hidden="true"></i>',
    "af_icon('package', 'icon')" => '<i class="fa-solid fa-box" aria-hidden="true"></i>',
    "af_icon('tag', 'icon')" => '<i class="fa-solid fa-tag" aria-hidden="true"></i>',
    "af_icon('target', 'icon')" => '<i class="fa-solid fa-bullseye" aria-hidden="true"></i>',
    "af_icon('calendar', 'icon')" => '<i class="fa-solid fa-calendar-day" aria-hidden="true"></i>',
    "af_icon('calendar-days', 'icon')" => '<i class="fa-solid fa-calendar-week" aria-hidden="true"></i>',
    "af_icon('calendar-range', 'icon')" => '<i class="fa-solid fa-calendar-days" aria-hidden="true"></i>',
    "af_icon('user', 'icon')" => '<i class="fa-solid fa-user" aria-hidden="true"></i>',
    "af_icon('users', 'icon')" => '<i class="fa-solid fa-users" aria-hidden="true"></i>',
    "af_icon('contact', 'icon')" => '<i class="fa-solid fa-address-book" aria-hidden="true"></i>',
    "af_icon('map-pin', 'icon')" => '<i class="fa-solid fa-location-dot" aria-hidden="true"></i>',
    "af_icon('store', 'icon')" => '<i class="fa-solid fa-store" aria-hidden="true"></i>',
    "af_icon('log-out', 'icon')" => '<i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i>',
    "af_icon('menu', 'icon')" => '<i class="fa-solid fa-bars" aria-hidden="true"></i>',
    "af_icon('triangle-alert', 'icon')" => '<i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>',
    "af_icon('arrow-left', 'icon')" => '<i class="fa-solid fa-arrow-left" aria-hidden="true"></i>',
    "af_icon('arrow-right', 'icon')" => '<i class="fa-solid fa-arrow-right" aria-hidden="true"></i>',
    "af_icon('check', 'icon')" => '<i class="fa-solid fa-check" aria-hidden="true"></i>',
    "af_icon('circle-x', 'icon')" => '<i class="fa-solid fa-circle-xmark" aria-hidden="true"></i>',
    "af_icon('trophy', 'icon')" => '<i class="fa-solid fa-trophy" aria-hidden="true"></i>',
    "af_icon('line-chart', 'icon')" => '<i class="fa-solid fa-chart-line" aria-hidden="true"></i>',
    "af_icon('clock', 'icon')" => '<i class="fa-solid fa-clock" aria-hidden="true"></i>',
    "af_icon('plus', 'icon')" => '<i class="fa-solid fa-plus" aria-hidden="true"></i>',
    "af_icon('pencil', 'icon')" => '<i class="fa-solid fa-pen" aria-hidden="true"></i>',
    "af_icon('trash', 'icon')" => '<i class="fa-solid fa-trash" aria-hidden="true"></i>',
    "af_icon('download', 'icon')" => '<i class="fa-solid fa-download" aria-hidden="true"></i>',
    "af_icon('printer', 'icon')" => '<i class="fa-solid fa-print" aria-hidden="true"></i>',
    "af_icon('wallet', 'icon')" => '<i class="fa-solid fa-wallet" aria-hidden="true"></i>',
    "af_icon('list', 'icon')" => '<i class="fa-solid fa-list" aria-hidden="true"></i>',
    "af_icon('search', 'icon')" => '<i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>',
    "af_icon('filter', 'icon')" => '<i class="fa-solid fa-filter" aria-hidden="true"></i>',
    "af_icon('eye', 'icon')" => '<i class="fa-solid fa-eye" aria-hidden="true"></i>',
    "af_icon('ban', 'icon')" => '<i class="fa-solid fa-ban" aria-hidden="true"></i>',
    "af_icon('save', 'icon')" => '<i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>',
    "af_icon('x', 'icon')" => '<i class="fa-solid fa-xmark" aria-hidden="true"></i>',
    "af_icon('rotate-ccw', 'icon')" => '<i class="fa-solid fa-rotate-left" aria-hidden="true"></i>',
    "af_icon('phone', 'icon')" => '<i class="fa-solid fa-phone" aria-hidden="true"></i>',
    "af_icon('shoe', 'icon icon-xl')" => '<i class="fa-solid fa-shoe-prints fa-2x" aria-hidden="true"></i>',
    "af_icon('phone', 'icon icon-inline')" => '<i class="fa-solid fa-phone" aria-hidden="true"></i>',
];

// Also handle <?= af_icon(...) ?> wrappers via regex
$root = dirname(__DIR__) . '/app/Views';
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
$changed = 0;
foreach ($it as $f) {
    if (!$f->isFile() || $f->getExtension() !== 'php') continue;
    $path = $f->getPathname();
    $c = file_get_contents($path);
    $orig = $c;

    // Replace <?= af_icon('name', 'class') ?>
    $c = preg_replace_callback(
        "/<\?=\s*af_icon\('([^']+)'(?:,\s*'([^']*)')?\)\s*\?>/",
        function ($m) {
            $name = $m[1];
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
            ];
            $fa = $map[$name] ?? 'fa-circle';
            $extra = '';
            $cls = $m[2] ?? '';
            if (str_contains($cls, 'icon-xl') || str_contains($cls, 'icon-lg')) $extra = ' fa-lg';
            if (str_contains($cls, 'logo-mark')) $extra .= ' logo-mark-svg';
            return '<i class="fa-solid ' . $fa . $extra . '" aria-hidden="true"></i>';
        },
        $c
    );

    // af_icon concatenated in PHP expressions
    $c = preg_replace_callback(
        "/af_icon\('([^']+)'(?:,\s*'([^']*)')?\)/",
        function ($m) {
            $name = $m[1];
            $map = [
                'pencil' => 'fa-pen', 'plus' => 'fa-plus', 'save' => 'fa-floppy-disk',
                'ban' => 'fa-ban', 'check' => 'fa-check', 'arrow-right' => 'fa-arrow-right',
            ];
            $fa = $map[$name] ?? 'fa-circle';
            return "'<i class=\"fa-solid {$fa}\" aria-hidden=\"true\"></i>'";
        },
        $c
    );

    // Common text arrows
    $c = str_replace(' →</button>', ' <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>', $c);
    $c = str_replace(' →</a>', ' <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>', $c);
    $c = str_replace('View →', 'View <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>', $c);
    $c = str_replace('Manage →', 'Manage <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>', $c);
    $c = str_replace('All →', 'All <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>', $c);
    $c = str_replace('Set one →', 'Set one <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>', $c);
    $c = str_replace('← ', '<i class="fa-solid fa-arrow-left" aria-hidden="true"></i> ', $c);

    // Ensure FA CDN in layout/standalone heads if missing and has fa-solid already or will need it
    if (str_contains($c, '<link rel="stylesheet" href="<?= BASE_PATH ?>/assets/css/app.css">')
        && !str_contains($c, 'font-awesome')) {
        $c = str_replace(
            '<link rel="stylesheet" href="<?= BASE_PATH ?>/assets/css/app.css">',
            '<link rel="stylesheet" href="<?= BASE_PATH ?>/assets/css/app.css">' . "\n  " . $faCdn,
            $c
        );
    }

    if ($c !== $orig) {
        file_put_contents($path, $c);
        $changed++;
        echo "updated: " . str_replace('\\', '/', substr($path, strlen(dirname(__DIR__)) + 1)) . "\n";
    }
}
echo "files: $changed\n";
