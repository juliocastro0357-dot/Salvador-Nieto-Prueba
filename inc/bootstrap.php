<?php
declare(strict_types=1);

define('ROOT', dirname(__DIR__));
$CONFIG = require __DIR__ . '/config.php';

function cfg(string $key, $default = null)
{
    global $CONFIG;
    return $CONFIG[$key] ?? $default;
}

date_default_timezone_set('America/Tijuana');
mb_internal_encoding('UTF-8');

if (!is_dir(ROOT . '/data')) {
    @mkdir(ROOT . '/data', 0775, true);
}
error_reporting(E_ALL);
ini_set('display_errors', cfg('debug') ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', ROOT . '/data/php-error.log');

$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
define('IS_HTTPS', $https);

if (PHP_SAPI !== 'cli') {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    session_name(cfg('session_name', 'sn_sess'));
    session_set_cookie_params([
        'lifetime' => 0, 'path' => '/', 'httponly' => true,
        'secure' => $https, 'samesite' => 'Lax',
    ]);
    session_start();
}

// Carpeta base (por si el sitio vive en un subdirectorio).
$docroot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
$base = '';
if ($docroot && strpos(ROOT, $docroot) === 0) {
    $base = str_replace('\\', '/', substr(ROOT, strlen($docroot)));
}
define('BASE', rtrim($base, '/'));

require __DIR__ . '/helpers.php';
require __DIR__ . '/db.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/cart.php';
require __DIR__ . '/payments.php';
require __DIR__ . '/mail.php';
