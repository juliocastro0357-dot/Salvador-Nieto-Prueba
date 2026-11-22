<?php
require dirname(__DIR__) . '/inc/bootstrap.php';

if (!is_post()) {
    json_out(['ok' => false, 'message' => 'Método no permitido'], 405);
}
csrf_verify();
$email = mb_strtolower(post('email'));
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 160) {
    json_out(['ok' => false, 'message' => 'Escribe un correo válido.'], 422);
}
$_SESSION['sub'] = array_filter($_SESSION['sub'] ?? [], fn($t) => $t > time() - 3600);
if (count($_SESSION['sub']) >= 5) {
    json_out(['ok' => false, 'message' => 'Demasiados intentos. Inténtalo más tarde.'], 429);
}
$_SESSION['sub'][] = time();
q('INSERT OR IGNORE INTO subscribers (email) VALUES (?)', [$email]);
json_out(['ok' => true, 'message' => '¡Listo! Ya eres parte del Círculo del Noble.']);
