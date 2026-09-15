<?php
/**
 * App + database config.
 *
 * Local XAMPP defaults live here. On the server, create config/local.php
 * (copy from local.php.example) with your hosting DB credentials.
 * BASE_PATH and APP_URL auto-detect from the request unless overridden.
 */

$afConfig = [
    'DB_HOST'    => 'localhost',
    'DB_NAME'    => 'amazingfeet',
    'DB_USER'    => 'root',
    'DB_PASS'    => '',
    'DB_CHARSET' => 'utf8mb4',

    // Arkesel SMS (low stock alerts + receipts)
    'ARKESEL_API_KEY' => 'YOUR_ARKESEL_API_KEY',
    'ARKESEL_SENDER'  => 'AmazingFeet',

    'APP_NAME' => 'Amazing Feet',
    // Leave null to auto-detect from the current request
    'APP_URL'   => null,
    'BASE_PATH' => null,

    'LOW_STOCK_THRESHOLD' => 3,
];

if (is_file(__DIR__ . '/local.php')) {
    $local = require __DIR__ . '/local.php';
    if (is_array($local)) {
        $afConfig = array_merge($afConfig, $local);
    }
}

/** Detect URL base when the app is in a subfolder (e.g. /amazingfeet) or at domain root. */
function af_detect_base_path(): string {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
    $dir = dirname($script);
    if ($dir === '/' || $dir === '.' || $dir === '\\') {
        return '';
    }
    // Docroot is often .../public — strip that segment from the public URL base
    $dir = preg_replace('#/public$#', '', $dir) ?? $dir;
    if ($dir === '/' || $dir === '') {
        return '';
    }
    return rtrim($dir, '/');
}

function af_detect_app_url(string $basePath): string {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || (($_SERVER['SERVER_PORT'] ?? null) == 443);
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . $basePath;
}

$basePath = $afConfig['BASE_PATH'];
if ($basePath === null || $basePath === false) {
    $basePath = af_detect_base_path();
} else {
    $basePath = rtrim((string)$basePath, '/');
    if ($basePath === '/') {
        $basePath = '';
    }
}

$appUrl = $afConfig['APP_URL'];
if ($appUrl === null || $appUrl === false || $appUrl === '') {
    $appUrl = af_detect_app_url($basePath);
} else {
    $appUrl = rtrim((string)$appUrl, '/');
}

define('DB_HOST',    (string)$afConfig['DB_HOST']);
define('DB_NAME',    (string)$afConfig['DB_NAME']);
define('DB_USER',    (string)$afConfig['DB_USER']);
define('DB_PASS',    (string)$afConfig['DB_PASS']);
define('DB_CHARSET', (string)$afConfig['DB_CHARSET']);

define('ARKESEL_API_KEY', (string)$afConfig['ARKESEL_API_KEY']);
define('ARKESEL_SENDER',  (string)$afConfig['ARKESEL_SENDER']);

define('APP_NAME',  (string)$afConfig['APP_NAME']);
define('APP_URL',   $appUrl);
define('BASE_PATH', $basePath);
define('UPLOAD_PATH', __DIR__ . '/../public/uploads/products/');
define('UPLOAD_URL',  APP_URL . '/uploads/products/');
define('LOW_STOCK_THRESHOLD', (int)$afConfig['LOW_STOCK_THRESHOLD']);

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = 'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset='.DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            $hint = 'Check DB_HOST / DB_NAME / DB_USER / DB_PASS in config/local.php on the server.';
            if (str_contains($_SERVER['HTTP_HOST'] ?? '', 'localhost')) {
                $hint .= ' Detail: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            }
            die('<h1>Database connection failed</h1><p>'.$hint.'</p>');
        }
    }
    return $pdo;
}
