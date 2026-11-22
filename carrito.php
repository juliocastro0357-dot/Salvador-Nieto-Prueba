<?php
require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/partials.php';

if (is_post()) {
    csrf_verify();
    $act = post('action');
    if ($act === 'add') {
        [$ok, $msg] = cart_add((int)post('variant_id'), max(1, (int)post('qty', '1')));
        flash($ok ? 'ok' : 'error', $msg);
    } elseif ($act === 'update') {
        foreach ((array)($_POST['qty'] ?? []) as $vid => $q) {
            cart_set((int)$vid, (int)$q);
        }
        flash('ok', 'Carrito actualizado.');
    } elseif ($act === 'remove') {
        cart_set((int)post('variant_id'), 0);
    } elseif ($act === 'coupon') {
        [$ok, $msg] = cart_apply_coupon(post('code'));
        flash($ok ? 'ok' : 'error', $msg);
    } elseif ($act === 'coupon_remove') {
        unset($_SESSION['coupon']);
    }
    redirect('carrito.php');
}

$lines = cart_lines();
$t = cart_totals($lines);
$title = 'Carrito';
$active = 'tienda';
require __DIR__ . '/inc/header.php';
?>
<section class="page-head">
  <div class="wrap"><span class="eyebrow">Tienda oficial</span><h1>Tu <em>carrito.</em></h1></div>
</section>

<section class="section">
  <div class="wrap">
  <?php if (!$lines): ?>
    <div class="panel empty">
      <h2>Tu carrito está vacío</h2>
      <p class="muted">Explora la tienda y encuentra algo para presumir al Noble.</p>
      <a class="btn btn-gold" href="<?= e(url('tienda.php')) ?>">Ir a la tienda</a>
    </div>
  <?php else: ?>
    <div class="cart-grid">
      <form method="post" class="cart-list" id="cartForm">
        <?= csrf_field() ?><input type="hidden" name="action" value="update">
        <?php if ($t['free_min'] > 0): ?>
          <div class="ship-bar">
            <?php if ($t['to_free'] > 0): ?>
              <span>Te faltan <b><?= e(money($t['to_free'])) ?></b> para envío gratis</span>
              <i style="--p:<?= min(100, round(100 * ($t['subtotal'] - $t['discount']) / $t['free_min'])) ?>%"></i>
            <?php else: ?>
              <span><?= icon('check', 16) ?> ¡Tienes envío gratis!</span><i style="--p:100%"></i>
            <?php endif; ?>
          </div>
        <?php endif; ?>
        <?php foreach ($lines as $l): ?>
          <article class="cart-line">
            <a href="<?= e(url('producto.php?slug=' . urlencode($l['slug']))) ?>" class="cl-img"><img src="<?= e(media($l['image'], 'assets/img/products/p1.svg')) ?>" alt="" width="110" height="110"></a>
            <div class="cl-info">
              <a href="<?= e(url('producto.php?slug=' . urlencode($l['slug']))) ?>"><strong><?= e($l['name']) ?></strong></a>
              <span class="muted"><?= e($l['label']) ?> · <?= e(money($l['price'])) ?></span>
              <button class="link-btn" type="submit" form="rm-<?= $l['vid'] ?>">Quitar</button>
            </div>
            <div class="qty" data-qty>
              <button type="button" data-dec aria-label="Menos">−</button>
              <input type="number" name="qty[<?= $l['vid'] ?>]" value="<?= $l['qty'] ?>" min="1" max="<?= min(10, $l['stock']) ?>" aria-label="Cantidad de <?= e($l['name']) ?>">
              <button type="button" data-inc aria-label="Más">+</button>
            </div>
            <strong class="cl-total"><?= e(money($l['total'])) ?></strong>
          </article>
        <?php endforeach; ?>
        <button class="btn btn-outline btn-sm" type="submit">Actualizar cantidades</button>
      </form>
      <?php foreach ($lines as $l): ?>
        <form id="rm-<?= $l['vid'] ?>" method="post" hidden><?= csrf_field() ?><input type="hidden" name="action" value="remove"><input type="hidden" name="variant_id" value="<?= $l['vid'] ?>"></form>
      <?php endforeach; ?>

      <aside class="summary">
        <h2>Resumen</h2>
        <form method="post" class="coupon">
          <?= csrf_field() ?>
          <?php if ($t['coupon']): ?>
            <input type="hidden" name="action" value="coupon_remove">
            <span class="coupon-on"><?= icon('check', 16) ?> <?= e($t['coupon']) ?></span><button class="link-btn" type="submit">Quitar</button>
          <?php else: ?>
            <input type="hidden" name="action" value="coupon">
            <label class="sr" for="code">Código de descuento</label>
            <input id="code" name="code" placeholder="Código de descuento" autocomplete="off">
            <button class="btn btn-outline btn-sm" type="submit">Aplicar</button>
          <?php endif; ?>
        </form>
        <?php if ($t['coupon_msg']): ?><p class="fine err"><?= e($t['coupon_msg']) ?></p><?php endif; ?>
        <dl class="totals">
          <div><dt>Subtotal</dt><dd><?= e(money($t['subtotal'])) ?></dd></div>
          <?php if ($t['discount']): ?><div><dt>Descuento</dt><dd>−<?= e(money($t['discount'])) ?></dd></div><?php endif; ?>
          <div><dt>Envío</dt><dd><?= $t['shipping'] ? e(money($t['shipping'])) : 'Gratis' ?></dd></div>
          <div class="grand"><dt>Total</dt><dd><?= e(money($t['total'])) ?> <small>MXN</small></dd></div>
        </dl>
        <a class="btn btn-gold btn-block" href="<?= e(url('checkout.php')) ?>">Continuar al pago</a>
        <p class="fine">Precios en MXN con IVA incluido.</p>
      </aside>
    </div>
  <?php endif; ?>
  </div>
</section>
<?php require __DIR__ . '/inc/footer.php'; ?>
