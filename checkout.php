<?php
require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/partials.php';

const MX_STATES = ['Aguascalientes', 'Baja California', 'Baja California Sur', 'Campeche', 'Chiapas', 'Chihuahua', 'Ciudad de México', 'Coahuila', 'Colima', 'Durango', 'Estado de México', 'Guanajuato', 'Guerrero', 'Hidalgo', 'Jalisco', 'Michoacán', 'Morelos', 'Nayarit', 'Nuevo León', 'Oaxaca', 'Puebla', 'Querétaro', 'Quintana Roo', 'San Luis Potosí', 'Sinaloa', 'Sonora', 'Tabasco', 'Tamaulipas', 'Tlaxcala', 'Veracruz', 'Yucatán', 'Zacatecas'];

/* Libera pedidos abandonados (sin pago en 3 h) */
foreach (rows("SELECT id FROM orders WHERE status = 'pendiente' AND notes IS NULL AND created_at < datetime('now','-3 hours')") as $o) {
    order_restock((int)$o['id']);
    q("UPDATE orders SET status = 'cancelado', notes = 'Cancelado automáticamente por falta de pago' WHERE id = ?", [$o['id']]);
}

$lines = cart_lines();
if (!$lines) {
    flash('error', 'Tu carrito está vacío.');
    redirect('tienda.php');
}
$t = cart_totals($lines);
$f = ['name' => '', 'email' => '', 'phone' => '', 'address1' => '', 'address2' => '', 'city' => '', 'state' => '', 'zip' => ''];
$errors = [];

if (is_post()) {
    csrf_verify();
    foreach ($f as $k => $_) {
        $f[$k] = mb_substr(post($k), 0, 200);
    }
    if (mb_strlen($f['name']) < 3) $errors['name'] = 'Escribe tu nombre completo.';
    if (!filter_var($f['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Escribe un correo válido.';
    if (!preg_match('/^[0-9+\-\s()]{8,20}$/', $f['phone'])) $errors['phone'] = 'Escribe un teléfono de 10 dígitos.';
    if (mb_strlen($f['address1']) < 5) $errors['address1'] = 'Escribe calle y número.';
    if (mb_strlen($f['city']) < 2) $errors['city'] = 'Escribe tu ciudad.';
    if (!in_array($f['state'], MX_STATES, true)) $errors['state'] = 'Selecciona tu estado.';
    if (!preg_match('/^\d{5}$/', $f['zip'])) $errors['zip'] = 'El código postal debe tener 5 dígitos.';

    if (!$errors) {
        try {
            // Si había un pedido pendiente de un intento anterior, se cancela y se libera su inventario.
            if (!empty($_SESSION['pending_order'])) {
                $prev = row("SELECT id FROM orders WHERE number = ? AND status = 'pendiente'", [$_SESSION['pending_order']]);
                if ($prev) {
                    order_restock((int)$prev['id']);
                    q("UPDATE orders SET status = 'cancelado', notes = 'Reemplazado por un nuevo intento de pago' WHERE id = ?", [$prev['id']]);
                }
            }
            $order = create_order($f, $lines, $t);
            $_SESSION['pending_order'] = $order['number'];
            mail_order_created($order);
            if (stripe_enabled()) {
                redirect(stripe_checkout_url($order));
            }
            redirect('pago-demo.php?n=' . urlencode($order['number']));
        } catch (RuntimeException $ex) {
            $errors['general'] = $ex->getMessage();
        }
    }
}

$title = 'Finalizar compra';
$active = 'tienda';
require __DIR__ . '/inc/header.php';
$err = fn(string $k) => isset($errors[$k]) ? '<span class="field-err">' . e($errors[$k]) . '</span>' : '';
?>
<section class="page-head"><div class="wrap"><span class="eyebrow">Tienda oficial</span><h1>Finalizar <em>compra.</em></h1></div></section>

<section class="section">
  <div class="wrap cart-grid">
    <form method="post" class="form checkout" novalidate>
      <?= csrf_field() ?>
      <?php if (isset($errors['general'])): ?><div class="flash flash-error"><?= e($errors['general']) ?></div><?php endif; ?>
      <h2 class="h3">Contacto</h2>
      <div class="grid-2">
        <label>Nombre completo*<input name="name" value="<?= e($f['name']) ?>" autocomplete="name" required><?= $err('name') ?></label>
        <label>Correo electrónico*<input type="email" name="email" value="<?= e($f['email']) ?>" autocomplete="email" required><?= $err('email') ?></label>
        <label>Teléfono*<input type="tel" name="phone" value="<?= e($f['phone']) ?>" autocomplete="tel" required><?= $err('phone') ?></label>
      </div>
      <h2 class="h3 mt">Dirección de envío (México)</h2>
      <div class="grid-2">
        <label class="span-2">Calle y número*<input name="address1" value="<?= e($f['address1']) ?>" autocomplete="address-line1" required><?= $err('address1') ?></label>
        <label class="span-2">Colonia, referencias (opcional)<input name="address2" value="<?= e($f['address2']) ?>" autocomplete="address-line2"></label>
        <label>Ciudad*<input name="city" value="<?= e($f['city']) ?>" autocomplete="address-level2" required><?= $err('city') ?></label>
        <label>Estado*
          <select name="state" autocomplete="address-level1" required>
            <option value="">Selecciona…</option>
            <?php foreach (MX_STATES as $s): ?><option <?= $f['state'] === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?>
          </select><?= $err('state') ?>
        </label>
        <label>Código postal*<input name="zip" value="<?= e($f['zip']) ?>" inputmode="numeric" maxlength="5" autocomplete="postal-code" required><?= $err('zip') ?></label>
      </div>
      <div class="panel pay-note">
        <?= icon('shield', 22) ?>
        <?php if (stripe_enabled()): ?>
          <p><b>Pago seguro con tarjeta.</b> Al continuar te llevaremos a la pasarela de pago segura de Stripe.</p>
        <?php else: ?>
          <p><b>Modo demostración.</b> La pasarela de pagos real se conecta al entregar el sitio. Aquí podrás simular el pago sin ningún cargo.</p>
        <?php endif; ?>
      </div>
      <button class="btn btn-gold btn-block" type="submit">Continuar al pago · <?= e(money($t['total'])) ?></button>
    </form>

    <aside class="summary">
      <h2>Tu pedido</h2>
      <ul class="mini-lines">
        <?php foreach ($lines as $l): ?>
          <li><img src="<?= e(media($l['image'], 'assets/img/products/p1.svg')) ?>" alt="" width="56" height="56">
            <span><b><?= e($l['name']) ?></b><small><?= e($l['label']) ?> × <?= $l['qty'] ?></small></span>
            <em><?= e(money($l['total'])) ?></em></li>
        <?php endforeach; ?>
      </ul>
      <dl class="totals">
        <div><dt>Subtotal</dt><dd><?= e(money($t['subtotal'])) ?></dd></div>
        <?php if ($t['discount']): ?><div><dt>Descuento (<?= e($t['coupon']) ?>)</dt><dd>−<?= e(money($t['discount'])) ?></dd></div><?php endif; ?>
        <div><dt>Envío</dt><dd><?= $t['shipping'] ? e(money($t['shipping'])) : 'Gratis' ?></dd></div>
        <div class="grand"><dt>Total</dt><dd><?= e(money($t['total'])) ?> <small>MXN</small></dd></div>
      </dl>
      <a class="link-arrow" href="<?= e(url('carrito.php')) ?>">← Editar carrito</a>
    </aside>
  </div>
</section>
<?php require __DIR__ . '/inc/footer.php'; ?>
