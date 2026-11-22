<?php
declare(strict_types=1);

/** Envío simple de correos. En modo demo solo registra en data/mail.log */
function send_mail(string $to, string $subject, string $html): bool
{
    $from = cfg('mail_from', 'no-reply@localhost');
    $name = cfg('artist_name', 'Salvador Nieto');
    $log = sprintf("[%s] A: %s | Asunto: %s\n", date('c'), $to, $subject);
    @file_put_contents(ROOT . '/data/mail.log', $log, FILE_APPEND);
    if (cfg('mail_log_only', true)) {
        return true;
    }
    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . mb_encode_mimeheader($name) . ' <' . $from . '>',
        'Reply-To: ' . $from,
    ];
    $body = '<div style="font-family:Arial,sans-serif;max-width:560px;margin:auto;color:#1b1410">'
        . '<h2 style="font-family:Georgia,serif;font-style:italic">' . e($name) . '</h2>' . $html
        . '<p style="color:#888;font-size:12px;margin-top:28px">Este es un mensaje automático de la tienda oficial.</p></div>';
    return @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, implode("\r\n", $headers));
}

function mail_order_created(array $order): void
{
    $html = '<p>Hola ' . e($order['name']) . ', recibimos tu pedido <strong>' . e($order['number']) . '</strong> por '
        . e(money((int)$order['total'])) . ' MXN.</p><p>Puedes consultar su estado en cualquier momento desde la sección '
        . '“Seguir mi pedido” de la tienda.</p>';
    send_mail($order['email'], 'Recibimos tu pedido ' . $order['number'], $html);
    send_mail(cfg('mail_provider', 'proveedor@ejemplo.com'), 'Nuevo pedido ' . $order['number'], '<p>Nuevo pedido por ' . e(money((int)$order['total'])) . ' MXN. Revísalo en el panel.</p>');
}

function mail_order_shipped(array $order): void
{
    $html = '<p>Tu pedido <strong>' . e($order['number']) . '</strong> va en camino.</p>'
        . '<p>Paquetería: <strong>' . e($order['carrier'] ?: 'Por confirmar') . '</strong><br>Guía: <strong>' . e($order['tracking'] ?: 'Por confirmar') . '</strong></p>';
    send_mail($order['email'], 'Tu pedido ' . $order['number'] . ' fue enviado', $html);
}
