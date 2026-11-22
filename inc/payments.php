<?php
declare(strict_types=1);

const ORDER_STATUSES = [
    'pendiente' => 'Pendiente de pago',
    'pagado'    => 'Pagado · por enviar',
    'enviado'   => 'Enviado',
    'entregado' => 'Entregado',
    'cancelado' => 'Cancelado',
];

function stripe_enabled(): bool
{
    return (string)cfg('stripe_secret', '') !== '';
}

function new_order_number(): string
{
    do {
        $n = 'SN-' . date('ymd') . '-' . random_int(1000, 9999);
    } while (val('SELECT 1 FROM orders WHERE number = ?', [$n]));
    return $n;
}

/** Crea el pedido y descuenta inventario dentro de una transacción. */
function create_order(array $c, array $lines, array $t): array
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        foreach ($lines as $l) {
            $stock = (int)val('SELECT stock FROM variants WHERE id = ?', [$l['vid']]);
            if ($stock < $l['qty']) {
                throw new RuntimeException('Ya no hay suficiente inventario de “' . $l['name'] . ' (' . $l['label'] . ')”. Actualiza tu carrito.');
            }
        }
        $number = new_order_number();
        q('INSERT INTO orders (number, email, name, phone, address1, address2, city, state, zip, subtotal, shipping, discount, total, coupon, status, payment_method)
           VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)', [
            $number, $c['email'], $c['name'], $c['phone'], $c['address1'], $c['address2'], $c['city'], $c['state'], $c['zip'],
            $t['subtotal'], $t['shipping'], $t['discount'], $t['total'], $t['coupon'], 'pendiente', stripe_enabled() ? 'stripe' : 'demo',
        ]);
        $oid = (int)$pdo->lastInsertId();
        foreach ($lines as $l) {
            q('INSERT INTO order_items (order_id, product_id, variant_id, name, variant_label, price_cents, qty) VALUES (?,?,?,?,?,?,?)',
                [$oid, $l['pid'], $l['vid'], $l['name'], $l['label'], $l['price'], $l['qty']]);
            q('UPDATE variants SET stock = stock - ? WHERE id = ?', [$l['qty'], $l['vid']]);
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
    return row('SELECT * FROM orders WHERE id = ?', [$oid]);
}

function order_mark_paid(int $orderId, string $method, string $ref): void
{
    q("UPDATE orders SET status = 'pagado', payment_method = ?, payment_ref = ?, paid_at = CURRENT_TIMESTAMP WHERE id = ? AND status = 'pendiente'", [$method, $ref, $orderId]);
}

/** Regresa al inventario las piezas de un pedido cancelado. */
function order_restock(int $orderId): void
{
    foreach (rows('SELECT variant_id, qty FROM order_items WHERE order_id = ?', [$orderId]) as $i) {
        if ($i['variant_id']) {
            q('UPDATE variants SET stock = stock + ? WHERE id = ?', [$i['qty'], $i['variant_id']]);
        }
    }
}

function absolute_url(string $path): string
{
    $scheme = IS_HTTPS ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . url($path);
}

/** Crea una sesión de Stripe Checkout y devuelve su URL. */
function stripe_checkout_url(array $order): string
{
    $params = [
        'mode' => 'payment',
        'client_reference_id' => $order['number'],
        'customer_email' => $order['email'],
        'success_url' => absolute_url('pedido.php?n=' . urlencode($order['number']) . '&e=' . urlencode($order['email']) . '&ok=1'),
        'cancel_url' => absolute_url('carrito.php'),
        'line_items' => [[
            'quantity' => 1,
            'price_data' => [
                'currency' => 'mxn',
                'unit_amount' => (int)$order['total'],
                'product_data' => ['name' => 'Pedido ' . $order['number'] . ' · ' . cfg('artist_name')],
            ],
        ]],
        'metadata' => ['order_number' => $order['number']],
    ];
    $ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($params),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_USERPWD => cfg('stripe_secret') . ':',
    ]);
    $res = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $data = json_decode((string)$res, true);
    if ($code !== 200 || empty($data['url'])) {
        throw new RuntimeException('No pudimos iniciar el pago con tarjeta. Intenta de nuevo en unos minutos.');
    }
    q('UPDATE orders SET payment_ref = ? WHERE id = ?', [$data['id'], $order['id']]);
    return $data['url'];
}

function stripe_verify_signature(string $payload, string $header, string $secret, int $tolerance = 300): bool
{
    $ts = null;
    $sigs = [];
    foreach (explode(',', $header) as $part) {
        [$k, $v] = array_pad(explode('=', trim($part), 2), 2, '');
        if ($k === 't') {
            $ts = (int)$v;
        } elseif ($k === 'v1') {
            $sigs[] = $v;
        }
    }
    if (!$ts || abs(time() - $ts) > $tolerance) {
        return false;
    }
    $expected = hash_hmac('sha256', $ts . '.' . $payload, $secret);
    foreach ($sigs as $s) {
        if (hash_equals($expected, $s)) {
            return true;
        }
    }
    return false;
}
