<?php
// ════════════════════════════════════════════════════════════
//  Amazing Feet POS — Single Route File
// ════════════════════════════════════════════════════════════
session_start();

define('ROOT',     dirname(__DIR__));
define('APP_ROOT', ROOT . '/app');

require ROOT . '/config/database.php';
require ROOT . '/app/icon_helper.php';

// Load combined files once
require APP_ROOT . '/Models/SupportModels.php';
require APP_ROOT . '/Controllers/AllControllers.php';

spl_autoload_register(function ($class) {
    foreach ([APP_ROOT.'/Controllers/'.$class.'.php', APP_ROOT.'/Models/'.$class.'.php'] as $f) {
        if (file_exists($f)) { require $f; return; }
    }
});

// ─── Global helpers ──────────────────────────────────────────
function redirect(string $path): void { header('Location: '.BASE_PATH.$path); exit; }
function view(string $tpl, array $data = []): void {
    extract($data);
    $f = APP_ROOT.'/Views/'.$tpl.'.php';
    if (!file_exists($f)) die("View not found: $tpl");
    require $f;
}
function isLoggedIn(): bool  {
    return isset($_SESSION['user_id']) && is_string($_SESSION['user_role'] ?? null) && $_SESSION['user_role'] !== '';
}
function isOwner(): bool     { return ($_SESSION['user_role'] ?? '') === 'owner'; }
function isStaff(): bool     { return ($_SESSION['user_role'] ?? '') === 'staff'; }
function requireLogin(): void { if (!isLoggedIn()) redirect('/login'); }
function requireOwner(): void { requireLogin(); if (!isOwner()) redirect('/unauthorized'); }
function requireStaff(): void { requireLogin(); if (!isStaff()) redirect('/unauthorized'); }
function csrf(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function verifyCsrf(): void {
    if (($_POST['csrf'] ?? '') !== ($_SESSION['csrf'] ?? '')) { http_response_code(403); die('Bad CSRF'); }
}
function flash(string $k, string $v = ''): string {
    if ($v) { $_SESSION['flash'][$k] = $v; return ''; }
    $r = $_SESSION['flash'][$k] ?? ''; unset($_SESSION['flash'][$k]); return $r;
}
function e(mixed $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function money(mixed $n): string { return 'GHS '.number_format((float)$n, 2); }
function formatMoney(mixed $n): string { return number_format((float)$n, 2); }

/** Versioned public asset URL (busts Hostinger/browser CSS cache after deploy). */
function asset(string $path): string {
    $path = '/' . ltrim($path, '/');
    $file = ROOT . '/public' . $path;
    $v = is_file($file) ? (string) filemtime($file) : (string) time();
    return BASE_PATH . $path . '?v=' . $v;
}

// ─── Request ─────────────────────────────────────────────────
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$base = BASE_PATH;

// Permanent redirect: /amazingfeet/public/... → /amazingfeet/...
$legacyPrefix = $base . '/public';
if ($base !== '' && (str_starts_with($uri, $legacyPrefix . '/') || $uri === $legacyPrefix)) {
    $clean = $base . substr($uri, strlen($legacyPrefix));
    if ($clean === '') $clean = $base . '/';
    $qs = $_SERVER['QUERY_STRING'] ?? '';
    header('Location: ' . $clean . ($qs !== '' ? '?' . $qs : ''), true, 301);
    exit;
}

$relative = $uri;
if ($base !== '' && str_starts_with($uri, $base)) {
    $relative = substr($uri, strlen($base));
}
$path = '/' . trim($relative, '/');
$method = $_SERVER['REQUEST_METHOD'];

// ─── Routes ──────────────────────────────────────────────────
$routes = [
    // Home + Auth
    ['GET',  '/',                         'HomeController',     'index',            []],
    ['GET',  '/login',                    'AuthController',     'showLogin',        []],
    ['POST', '/login',                    'AuthController',     'login',            []],
    ['GET',  '/logout',                   'AuthController',     'logout',           []],
    ['GET',  '/unauthorized',             'AuthController',     'unauthorized',     []],

    // ── Help / User guide (both roles) ────────────────────────
    ['GET',  '/help',                     'HelpController',     'manual',           ['login']],

    // ── Staff daily assessment (staff + owner) ────────────────
    ['GET',  '/assessment',               'AssessmentController','form',            ['login']],
    ['POST', '/assessment',               'AssessmentController','save',            ['login']],
    ['GET',  '/assessment/history',       'AssessmentController','myHistory',       ['login']],

    // ── POS (both roles) ──────────────────────────────────────
    ['GET',  '/pos',                      'PosController',      'index',            ['login']],
    ['POST', '/pos/sale',                 'PosController',      'processSale',      ['login']],
    ['GET',  '/pos/receipt/{id}',         'PosController',      'receipt',          ['login']],
    ['GET',  '/pos/product/search',       'PosController',      'searchProduct',    ['login']],
    ['GET',  '/pos/product/barcode/{bc}', 'PosController',      'byBarcode',        ['login']],

    // ── Owner: Dashboard ──────────────────────────────────────
    ['GET',  '/dashboard',                'DashboardController','index',            ['owner']],

    // ── Owner: Products ───────────────────────────────────────
    ['GET',  '/products',                 'ProductController',  'index',            ['owner']],
    ['GET',  '/products/create',          'ProductController',  'create',           ['owner']],
    ['POST', '/products/create',          'ProductController',  'store',            ['owner']],
    ['GET',  '/products/{id}/edit',       'ProductController',  'edit',             ['owner']],
    ['POST', '/products/{id}/edit',       'ProductController',  'update',           ['owner']],
    ['POST', '/products/{id}/sizes',      'ProductController',  'addSize',          ['owner']],
    ['POST', '/products/{id}/delete',     'ProductController',  'delete',           ['owner']],
    ['POST', '/products/{id}/stock',      'ProductController',  'adjustStock',      ['owner']],

    // ── Owner: Categories ─────────────────────────────────────
    ['GET',  '/categories',               'CategoryController', 'index',            ['owner']],
    ['POST', '/categories/create',        'CategoryController', 'store',            ['owner']],
    ['POST', '/categories/{id}/delete',   'CategoryController', 'delete',           ['owner']],

    // ── Owner: Locations ──────────────────────────────────────
    ['GET',  '/locations',                'LocationController', 'index',            ['owner']],
    ['POST', '/locations/create',         'LocationController', 'store',            ['owner']],
    ['POST', '/locations/{id}/delete',    'LocationController', 'delete',           ['owner']],

    // ── Owner: Sales / Reports ────────────────────────────────
    ['GET',  '/sales',                    'SalesController',    'index',            ['owner']],
    ['POST', '/sales/bulk',               'SalesController',    'bulk',             ['owner']],
    ['GET',  '/sales/{id}/edit',          'SalesController',    'edit',             ['owner']],
    ['POST', '/sales/{id}/edit',          'SalesController',    'update',           ['owner']],
    ['POST', '/sales/{id}/delete',        'SalesController',    'delete',           ['owner']],
    ['GET',  '/sales/{id}',               'SalesController',    'view',             ['owner']],
    ['GET',  '/reports/daily',            'ReportController',   'daily',            ['owner']],
    ['GET',  '/reports/weekly',           'ReportController',   'weekly',           ['owner']],
    ['GET',  '/reports/monthly',          'ReportController',   'monthly',          ['owner']],
    ['GET',  '/reports/staff',            'ReportController',   'staff',            ['owner']],
    ['GET',  '/reports/locations',        'ReportController',   'locations',        ['owner']],

    // ── Owner: Staff daily assessments ────────────────────────
    ['GET',  '/assessments',              'AssessmentController','index',           ['owner']],
    ['GET',  '/assessments/{id}',         'AssessmentController','show',            ['owner']],

    // ── Owner: Targets ────────────────────────────────────────
    ['GET',  '/targets',                  'TargetController',   'index',            ['owner']],
    ['POST', '/targets/create',           'TargetController',   'store',            ['owner']],
    ['POST', '/targets/{id}/delete',      'TargetController',   'delete',           ['owner']],

    // ── Owner: Staff ──────────────────────────────────────────
    ['GET',  '/staff',                    'StaffController',    'index',            ['owner']],
    ['GET',  '/staff/create',             'StaffController',    'create',           ['owner']],
    ['POST', '/staff/create',             'StaffController',    'store',            ['owner']],
    ['POST', '/staff/{id}/toggle',        'StaffController',    'toggle',           ['owner']],

    // ── Owner: Customers ──────────────────────────────────────
    ['GET',  '/customers',                'CustomerController', 'index',            ['owner']],
    ['POST', '/customers/create',         'CustomerController', 'store',            ['owner']],
    ['GET',  '/customers/export',         'CustomerController', 'export',           ['owner']],

    // ── Alerts (JSON) ─────────────────────────────────────────
    ['GET',  '/api/alerts',               'ApiController',      'alerts',           ['login']],
    ['POST', '/api/alerts/read',          'ApiController',      'markAlertsRead',   ['login']],
];

// ─── Match & dispatch ────────────────────────────────────────
$matched = false;
foreach ($routes as [$rm, $pattern, $ctrl, $action, $mw]) {
    $regex = '#^'.preg_replace('/\{[a-z_]+\}/', '([^/]+)', $pattern).'$#';
    if ($method === $rm && preg_match($regex, $path, $m)) {
        array_shift($m);
        foreach ($mw as $w) {
            if ($w === 'login') requireLogin();
            if ($w === 'owner') requireOwner();
        }
        (new $ctrl())->$action(...$m);
        $matched = true;
        break;
    }
}
if (!$matched) { http_response_code(404); view('layouts/404'); }
