<?php
define('DB_HOST',    'localhost');
define('DB_NAME',    'amazingfeet');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

// Arkesel SMS (for low stock alerts + receipts)
define('ARKESEL_API_KEY',  'YOUR_ARKESEL_API_KEY');
define('ARKESEL_SENDER',   'AmazingFeet');

// App
define('APP_NAME',  'Amazing Feet');
define('APP_URL',   'http://localhost/amazingfeet');
define('BASE_PATH', '/amazingfeet');
define('UPLOAD_PATH', __DIR__ . '/../public/uploads/products/');
define('UPLOAD_URL',  APP_URL . '/uploads/products/');

// Low stock default threshold
define('LOW_STOCK_THRESHOLD', 3);

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}
