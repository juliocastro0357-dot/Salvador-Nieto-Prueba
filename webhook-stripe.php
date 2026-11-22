<?php
/**
 * Webhook de Stripe: confirma el pago de forma segura (no depende de que el cliente regrese al sitio).
 * En el panel de Stripe, apunta el evento "checkout.session.completed" a esta URL.
 */
require __DIR__ . '/inc/bootstrap.php';

$payload = (string)file_get_contents('php://input');
$sig = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
$secret = (string)cfg('stripe_webhook_secret', '');

if ($secret === '' || !stripe_verify_signature($payload, $sig, $secret)) {
    http_response_code(400);
    exit('firma inválida');
}
$event = json_decode($payload, true);
if (($event['type'] ?? '') === 'checkout.session.completed') {
    $s = $event['data']['object'] ?? [];
    $number = $s['client_reference_id'] ?? ($s['metadata']['order_number'] ?? '');
    $order = row('SELECT * FROM orders WHERE number = ?', [$number]);
    if ($order && ($s['payment_status'] ?? '') === 'paid' && (int)($s['amount_total'] ?? 0) === (int)$order['total']) {
        order_mark_paid((int)$order['id'], 'stripe', (string)($s['payment_intent'] ?? $s['id'] ?? ''));
        send_mail($order['email'], 'Pago confirmado · pedido ' . $order['number'], '<p>Recibimos tu pago. Estamos preparando tu pedido <b>' . e($order['number']) . '</b>.</p>');
    }
}
http_response_code(200);
echo 'ok';
