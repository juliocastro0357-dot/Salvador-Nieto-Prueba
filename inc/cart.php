<?php
declare(strict_types=1);

/* El carrito vive en la sesión: [variant_id => cantidad] */

function cart_raw(): array
{
    return $_SESSION['cart'] ?? [];
}

function cart_count(): int
{
    return (int)array_sum(cart_raw());
}

function cart_add(int $variantId, int $qty = 1): array
{
    $v = row('SELECT v.id, v.stock, p.active FROM variants v JOIN products p ON p.id = v.product_id WHERE v.id = ?', [$variantId]);
    if (!$v || !(int)$v['active']) {
        return [false, 'Ese producto ya no está disponible.'];
    }
    $current = $_SESSION['cart'][$variantId] ?? 0;
    $new = $current + max(1, $qty);
    if ($new > (int)$v['stock']) {
        $new = (int)$v['stock'];
        if ($new <= $current) {
            return [false, 'No hay más piezas disponibles de esta opción.'];
        }
    }
    if ($new < 1) {
        return [false, 'Esta opción está agotada.'];
    }
    $_SESSION['cart'][$variantId] = min($new, 10);
    return [true, 'Agregado al carrito.'];
}

function cart_set(int $variantId, int $qty): void
{
    if ($qty <= 0) {
        unset($_SESSION['cart'][$variantId]);
        return;
    }
    $stock = (int)val('SELECT stock FROM variants WHERE id = ?', [$variantId]);
    $_SESSION['cart'][$variantId] = max(1, min($qty, $stock, 10));
}

function cart_clear(): void
{
    unset($_SESSION['cart'], $_SESSION['coupon']);
}

/** Líneas detalladas del carrito, ya validadas contra el inventario actual. */
function cart_lines(): array
{
    $cart = cart_raw();
    if (!$cart) {
        return [];
    }
    $ids = array_map('intval', array_keys($cart));
    $in = implode(',', array_fill(0, count($ids), '?'));
    $data = rows("SELECT v.id vid, v.label, v.stock, p.id pid, p.slug, p.name, p.price_cents, p.images, p.active
                  FROM variants v JOIN products p ON p.id = v.product_id WHERE v.id IN ($in)", $ids);
    $by = [];
    foreach ($data as $d) {
        $by[(int)$d['vid']] = $d;
    }
    $lines = [];
    foreach ($cart as $vid => $qty) {
        $d = $by[(int)$vid] ?? null;
        if (!$d || !(int)$d['active'] || (int)$d['stock'] < 1) {
            unset($_SESSION['cart'][$vid]);
            continue;
        }
        $qty = min((int)$qty, (int)$d['stock']);
        $_SESSION['cart'][$vid] = $qty;
        $imgs = json_decode($d['images'], true) ?: [];
        $lines[] = [
            'vid' => (int)$d['vid'], 'pid' => (int)$d['pid'], 'slug' => $d['slug'], 'name' => $d['name'],
            'label' => $d['label'], 'price' => (int)$d['price_cents'], 'qty' => $qty, 'stock' => (int)$d['stock'],
            'image' => $imgs[0] ?? null, 'total' => (int)$d['price_cents'] * $qty,
        ];
    }
    return $lines;
}

function cart_apply_coupon(string $code): array
{
    $c = row('SELECT * FROM coupons WHERE code = ? AND active = 1', [strtoupper(trim($code))]);
    if (!$c) {
        return [false, 'Ese código no es válido.'];
    }
    $_SESSION['coupon'] = $c['code'];
    return [true, 'Código aplicado.'];
}

function cart_totals(array $lines): array
{
    $subtotal = 0;
    foreach ($lines as $l) {
        $subtotal += $l['total'];
    }
    $discount = 0;
    $couponCode = null;
    $couponMsg = null;
    if (!empty($_SESSION['coupon']) && $subtotal > 0) {
        $c = row('SELECT * FROM coupons WHERE code = ? AND active = 1', [$_SESSION['coupon']]);
        if ($c && $subtotal >= (int)$c['min_total']) {
            $discount = $c['type'] === 'pct' ? intdiv($subtotal * (int)$c['value'], 100) : min((int)$c['value'], $subtotal);
            $couponCode = $c['code'];
        } elseif ($c) {
            $couponMsg = 'El código ' . $c['code'] . ' requiere una compra mínima de ' . money((int)$c['min_total']) . '.';
        } else {
            unset($_SESSION['coupon']);
        }
    }
    $freeMin = (int)setting('free_shipping_min', '150000');
    $after = $subtotal - $discount;
    $shipping = ($subtotal === 0 || ($freeMin > 0 && $after >= $freeMin)) ? 0 : (int)setting('shipping_flat', '9900');
    return [
        'subtotal' => $subtotal, 'discount' => $discount, 'shipping' => $shipping,
        'total' => $after + $shipping, 'coupon' => $couponCode, 'coupon_msg' => $couponMsg,
        'free_min' => $freeMin, 'to_free' => max(0, $freeMin - $after),
    ];
}

function product_images(array $p): array
{
    $imgs = json_decode($p['images'] ?? '[]', true) ?: [];
    return $imgs ?: ['assets/img/products/p1.svg'];
}

function product_stock(int $productId): int
{
    return (int)val('SELECT COALESCE(SUM(stock),0) FROM variants WHERE product_id = ?', [$productId]);
}
