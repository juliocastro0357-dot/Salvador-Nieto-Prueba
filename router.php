<?php
/**
 * Solo para pruebas locales:  php -S localhost:8080 router.php
 * (En hosting real con Apache se usa .htaccess y este archivo no se necesita.)
 */
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$blocked = ['#^/(data|inc)(/|$)#', '#\.(sqlite|log|md)$#', '#^/uploads/.*\.php$#'];
foreach ($blocked as $re) {
    if (preg_match($re, $path)) {
        http_response_code(403);
        exit('Acceso denegado');
    }
}
return false;
