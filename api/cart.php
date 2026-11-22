<?php
require dirname(__DIR__) . '/inc/bootstrap.php';

if (!is_post()) {
    json_out(['ok' => false, 'message' => 'Método no permitido'], 405);
}
csrf_verify();
[$ok, $msg] = cart_add((int)post('variant_id'), max(1, (int)post('qty', '1')));
json_out(['ok' => $ok, 'message' => $msg, 'count' => cart_count()], $ok ? 200 : 422);
